<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnthropicService
{
    private string $apiKey;
    private string $model;
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';
    private const MAX_TOOL_ROUNDS = 5;

    public function __construct(private AgendaToolsService $tools)
    {
        $this->apiKey = config('services.anthropic.key');
        $this->model = config('services.anthropic.model', 'claude-sonnet-4-20250514');
    }

    public function reply(
        string $from,
        string $userText,
        Collection $history,
        ?int $orgId = null,
        ?int $patientId = null,
    ): string {
        try {
            $messages = $this->buildMessages($history, $userText);
            $context = ['org_id' => $orgId, 'patient_id' => $patientId];

            for ($round = 0; $round < self::MAX_TOOL_ROUNDS; $round++) {
                $response = $this->callApi($messages, $context);

                if ($response->failed()) {
                    Log::channel('api')->error('Anthropic API error', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'from' => $from,
                    ]);
                    return $this->fallbackMessage();
                }

                $data = $response->json();
                $stopReason = $data['stop_reason'] ?? 'end_turn';

                if ($stopReason !== 'tool_use') {
                    return $this->extractText($data);
                }

                // Handle tool calls
                $messages[] = ['role' => 'assistant', 'content' => $data['content']];

                $toolResults = $this->executeTools($data['content'], $context);
                $messages[] = ['role' => 'user', 'content' => $toolResults];
            }

            return $this->extractText($response->json());
        } catch (\Throwable $e) {
            Log::channel('api')->error('Anthropic exception', [
                'message' => $e->getMessage(),
                'from' => $from,
            ]);
            return $this->fallbackMessage();
        }
    }

    private function callApi(array $messages, array $context): \Illuminate\Http\Client\Response
    {
        $payload = [
            'model' => $this->model,
            'max_tokens' => 1024,
            'system' => $this->systemPrompt(),
            'messages' => $messages,
        ];

        if ($context['org_id']) {
            $payload['tools'] = $this->toolDefinitions();
        }

        return Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->post($this->apiUrl, $payload);
    }

    private function executeTools(array $content, array $context): array
    {
        $results = [];

        foreach ($content as $block) {
            if (($block['type'] ?? '') !== 'tool_use') {
                continue;
            }

            $name = $block['name'];
            $input = $block['input'] ?? [];
            $toolId = $block['id'];

            Log::channel('api')->info('Tool call', ['name' => $name, 'input' => $input]);
            $startTime = microtime(true);

            $result = $this->dispatchTool($name, $input, $context);

            Log::channel('api')->info('Tool result', [
                'name' => $name,
                'duration_ms' => round((microtime(true) - $startTime) * 1000),
                'result_count' => is_array($result) ? count($result) : 1,
            ]);

            $results[] = [
                'type' => 'tool_result',
                'tool_use_id' => $toolId,
                'content' => json_encode($result),
            ];
        }

        return $results;
    }

    private function dispatchTool(string $name, array $input, array $context): mixed
    {
        try {
            return match ($name) {
                'get_services' => $this->tools->getServices($context['org_id']),
                'get_professionals' => $this->tools->getProfessionals(
                    $context['org_id'],
                    $input['service_id'] ?? null,
                ),
                'get_availability' => $this->tools->getAvailability(
                    $input['professional_id'],
                    $input['service_id'],
                    $input['date_local'],
                ),
                'list_upcoming_appointments' => $this->tools->listUpcomingAppointments($context['patient_id']),
                default => ['error' => "Unknown tool: {$name}"],
            };
        } catch (\Throwable $e) {
            Log::channel('api')->error('Tool execution failed', ['name' => $name, 'error' => $e->getMessage()]);
            return ['error' => 'Tool execution failed'];
        }
    }

    private function extractText(array $data): string
    {
        foreach ($data['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text' && !empty($block['text'])) {
                return $block['text'];
            }
        }
        return $this->fallbackMessage();
    }

    private function buildMessages(Collection $history, string $userText): array
    {
        $messages = [];

        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg->role,
                'content' => $msg->content,
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $userText,
        ];

        return $messages;
    }

    private function toolDefinitions(): array
    {
        return [
            [
                'name' => 'get_services',
                'description' => 'Get available services for this medical office',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => (object) [],
                    'required' => [],
                ],
            ],
            [
                'name' => 'get_professionals',
                'description' => 'Get available professionals, optionally filtered by service',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'service_id' => [
                            'type' => 'integer',
                            'description' => 'Filter by service ID',
                        ],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'get_availability',
                'description' => 'Get available time slots for a professional on a specific date',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'professional_id' => [
                            'type' => 'integer',
                            'description' => 'The professional ID',
                        ],
                        'service_id' => [
                            'type' => 'integer',
                            'description' => 'The service ID (determines slot duration)',
                        ],
                        'date_local' => [
                            'type' => 'string',
                            'description' => 'Date in YYYY-MM-DD format (Ecuador local time)',
                        ],
                    ],
                    'required' => ['professional_id', 'service_id', 'date_local'],
                ],
            ],
            [
                'name' => 'list_upcoming_appointments',
                'description' => 'List upcoming confirmed appointments for the current patient',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => (object) [],
                    'required' => [],
                ],
            ],
        ];
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
        Eres la recepcionista digital de un consultorio médico.
        No eres un chatbot, eres parte del equipo del consultorio.

        PERSONALIDAD
        - Amable, clara, profesional y cercana.
        - Nunca suenas robótica ni exageradamente informal.
        - Respondes como una persona real que trabaja allí.

        FORMA DE PENSAR
        - Mantienes memoria del contexto de la conversación.
        - No repites saludos innecesarios.
        - Si el usuario menciona 'el precio', asumes que se refiere al último servicio mencionado.
        - Si algo no es claro, haces una pregunta breve para confirmar.
        - No inventas información. Usa las tools para obtener datos reales.

        OBJETIVO
        - Guiar, no solo responder.
        - Ayudar a que el paciente llegue a una decisión (consultar, agendar, cancelar).
        - Usar las tools disponibles para consultar servicios, profesionales y disponibilidad.

        QUE NO DEBES HACER
        - No digas que eres una IA, modelo o asistente automático.
        - No uses frases como 'Como modelo de lenguaje...' o 'No tengo acceso a...'
        - No inventes horarios, precios o disponibilidad.
        - No des respuestas genéricas que ignoren el contexto anterior.
        - No repitas la misma información si ya fue dada.
        - No uses emojis excesivos ni lenguaje infantil.
        - No contradigas información dada previamente.
        - No tomes decisiones por el usuario sin confirmación.

        Habla siempre como alguien que trabaja en el consultorio.
        PROMPT;
    }

    private function fallbackMessage(): string
    {
        return 'Disculpa, tengo un problema técnico en este momento. ¿Podrías intentar de nuevo en unos minutos?';
    }
}
