<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\ToolCallLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnthropicService
{
    private string $apiKey;
    private string $model;
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';
    private const MAX_TOOL_ROUNDS = 5;
    private const MAX_CONSECUTIVE_TOOL_ERRORS = 2;

    public function __construct(private AgendaToolsService $tools)
    {
        $this->apiKey = config('services.anthropic.key');
        $this->model = config('services.anthropic.model', 'claude-haiku-4-5-20251001');
    }

    public function reply(
        string $from,
        string $userText,
        Collection $history,
        ?int $orgId = null,
        ?int $patientId = null,
        ?Conversation $conversation = null,
    ): string {
        $flowContext = [
            'from' => $from,
            'org_id' => $orgId,
            'patient_id' => $patientId,
            'conversation_id' => $conversation?->id,
        ];

        try {
            $messages = $this->buildMessages($history, $userText);
            $context = ['org_id' => $orgId, 'patient_id' => $patientId];
            $conversationContext = $conversation?->context ?? [];

            // Load existing patient data into context if not already present
            if ($patientId && empty($conversationContext['patient_name'])) {
                $patient = \App\Models\Patient::find($patientId);
                if ($patient?->name && $patient->name !== $patient->wa_id) {
                    $conversationContext['patient_name'] = $patient->name;
                }
                if ($patient?->cedula) {
                    $conversationContext['patient_cedula'] = $patient->cedula;
                }
            }

            $consecutiveToolErrors = 0;

            for ($round = 0; $round < self::MAX_TOOL_ROUNDS; $round++) {
                $response = $this->callApi($messages, $context, $conversationContext);

                if ($response->failed()) {
                    Log::channel('api')->error('Anthropic API error', [
                        ...$flowContext,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    return $this->fallbackMessage();
                }

                $data = $response->json();
                $stopReason = $data['stop_reason'] ?? 'end_turn';

                if ($stopReason !== 'tool_use') {
                    $this->saveContext($conversation, $conversationContext);
                    return $this->extractText($data);
                }

                // Handle tool calls
                // Cast empty tool inputs from [] to {} so Anthropic accepts them on re-send
                $assistantContent = array_map(function ($block) {
                    if (($block['type'] ?? '') === 'tool_use' && ($block['input'] ?? null) === []) {
                        $block['input'] = (object) [];
                    }
                    return $block;
                }, $data['content']);
                $messages[] = ['role' => 'assistant', 'content' => $assistantContent];

                $toolResults = $this->executeTools($data['content'], $context, $conversationContext, $flowContext);
                $messages[] = ['role' => 'user', 'content' => $toolResults];

                $allFailed = collect($toolResults)->every(
                    fn($r) => str_contains($r['content'] ?? '', '"error"')
                );

                if ($allFailed) {
                    $consecutiveToolErrors++;
                    if ($consecutiveToolErrors >= self::MAX_CONSECUTIVE_TOOL_ERRORS) {
                        Log::channel('api')->error('Handoff triggered after repeated tool failures', $flowContext);
                        $this->triggerHandoff($conversation);
                        return $this->handoffMessage();
                    }
                } else {
                    $consecutiveToolErrors = 0;
                }
            }

            Log::channel('api')->warning('Max tool rounds reached', $flowContext);
            $this->saveContext($conversation, $conversationContext);
            return $this->extractText($response->json());
        } catch (\Throwable $e) {
            Log::channel('api')->error('Anthropic exception', [
                ...$flowContext,
                'error' => $e->getMessage(),
            ]);
            return $this->fallbackMessage();
        }
    }

    private function callApi(array $messages, array $context, array $conversationContext): \Illuminate\Http\Client\Response
    {
        $payload = [
            'model' => $this->model,
            'max_tokens' => 1024,
            'system' => [
                [
                    'type' => 'text',
                    'text' => $this->systemPrompt($conversationContext),
                    'cache_control' => ['type' => 'ephemeral'],
                ],
            ],
            'messages' => $this->applyMessageCaching($messages),
        ];

        if ($context['org_id']) {
            $payload['tools'] = $this->toolDefinitions();
        }

        return Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'anthropic-beta' => 'prompt-caching-2024-07-31',
            'content-type' => 'application/json',
        ])->post($this->apiUrl, $payload);
    }

    private function applyMessageCaching(array $messages): array
    {
        // Cache the stable history boundary: the message just before the last user turn.
        // This lets Anthropic reuse the prefix on every round of the tool loop.
        $count = count($messages);
        if ($count < 2) {
            return $messages;
        }

        $targetIdx = $count - 2;
        $msg = $messages[$targetIdx];

        if (is_string($msg['content'])) {
            $messages[$targetIdx]['content'] = [
                ['type' => 'text', 'text' => $msg['content'], 'cache_control' => ['type' => 'ephemeral']],
            ];
        } elseif (is_array($msg['content'])) {
            $last = count($msg['content']) - 1;
            $messages[$targetIdx]['content'][$last]['cache_control'] = ['type' => 'ephemeral'];
        }

        return $messages;
    }

    private function executeTools(array $content, array $context, array &$conversationContext, array $flowContext = []): array
    {
        $results = [];

        foreach ($content as $block) {
            if (($block['type'] ?? '') !== 'tool_use') {
                continue;
            }

            $name = $block['name'];
            $input = $block['input'] ?? [];
            $toolId = $block['id'];

            Log::channel('api')->info('Tool call', [
                ...$flowContext,
                'tool' => $name,
                'input' => $input,
            ]);
            $startTime = microtime(true);

            $result = $this->dispatchTool($name, $input, $context);

            $durationMs = round((microtime(true) - $startTime) * 1000);
            $isError = is_array($result) && isset($result['error']);

            Log::channel('api')->log($isError ? 'error' : 'info', 'Tool result', [
                ...$flowContext,
                'tool' => $name,
                'duration_ms' => $durationMs,
                'result' => $result,
                'success' => !$isError,
            ]);

            $this->persistToolLog($flowContext, $name, $input, $result, $durationMs, $isError);

            $this->updateContext($conversationContext, $name, $input, $result);

            $results[] = [
                'type' => 'tool_result',
                'tool_use_id' => $toolId,
                'content' => json_encode($result),
            ];
        }

        return $results;
    }

    private function updateContext(array &$context, string $toolName, array $input, mixed $result): void
    {
        match ($toolName) {
            'get_availability' => $this->updateAvailabilityContext($context, $input, $result),
            'get_professionals' => $this->updateProfessionalsContext($context, $input),
            'update_patient' => $this->updatePatientContext($context, $input),
            default => null,
        };
    }

    private function updateAvailabilityContext(array &$context, array $input, mixed $result): void
    {
        $context['selected_service_id'] = $input['service_id'] ?? $context['selected_service_id'] ?? null;
        $context['selected_professional_id'] = $input['professional_id'] ?? $context['selected_professional_id'] ?? null;
        $context['preferred_date'] = $input['date_local'] ?? $context['preferred_date'] ?? null;
        $context['last_availability_result'] = $result;
    }

    private function updateProfessionalsContext(array &$context, array $input): void
    {
        if (isset($input['service_id'])) {
            $context['selected_service_id'] = $input['service_id'];
        }
    }

    private function updatePatientContext(array &$context, array $input): void
    {
        if (isset($input['name'])) {
            $context['patient_name'] = $input['name'];
        }
        if (isset($input['cedula'])) {
            $context['patient_cedula'] = $input['cedula'];
        }
    }

    private function saveContext(?Conversation $conversation, array $context): void
    {
        if ($conversation && !empty($context)) {
            $conversation->update(['context' => $context]);
        }
    }

    private function triggerHandoff(?Conversation $conversation): void
    {
        if ($conversation) {
            $conversation->update(['handoff_to_human' => true]);
        }
    }

    private function handoffMessage(): string
    {
        return 'Lo siento, estoy teniendo dificultades para completar tu solicitud en este momento. '
            . 'Por favor, comunícate directamente con el consultorio para que puedan ayudarte. '
            . 'Disculpa los inconvenientes.';
    }

    private function persistToolLog(array $flowContext, string $toolName, array $input, mixed $result, float $durationMs, bool $isError): void
    {
        if (!$flowContext['org_id']) {
            return;
        }

        try {
            ToolCallLog::create([
                'organization_id' => $flowContext['org_id'],
                'conversation_id' => $flowContext['conversation_id'] ?? null,
                'patient_id' => $flowContext['patient_id'] ?? null,
                'tool_name' => $toolName,
                'input' => $input,
                'result' => $result,
                'duration_ms' => (int) $durationMs,
                'success' => !$isError,
                'error_message' => $isError ? ($result['error'] ?? null) : null,
            ]);
        } catch (\Throwable $e) {
            Log::channel('api')->warning('Failed to persist tool call log', [
                'error' => $e->getMessage(),
            ]);
        }
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
                'confirm_appointment' => $this->tools->confirmAppointment(
                    organizationId: $context['org_id'],
                    patientId: $context['patient_id'],
                    professionalId: $input['professional_id'],
                    serviceId: $input['service_id'],
                    startLocal: $input['start_local'],
                ),
                'cancel_appointment' => $this->tools->cancelAppointment(
                    appointmentId: $input['appointment_id'],
                    patientId: $context['patient_id'],
                    reason: $input['reason'],
                ),
                'reschedule_appointment' => $this->tools->rescheduleAppointment(
                    appointmentId: $input['appointment_id'],
                    patientId: $context['patient_id'],
                    newProfessionalId: $input['new_professional_id'],
                    newServiceId: $input['new_service_id'],
                    newStartLocal: $input['new_start_local'],
                ),
                'update_patient' => $this->tools->updatePatient(
                    patientId: $context['patient_id'],
                    name: $input['name'] ?? null,
                    cedula: $input['cedula'] ?? null,
                ),
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
            [
                'name' => 'confirm_appointment',
                'description' => 'Book and confirm an appointment for the patient. Use only after patient has explicitly confirmed the details.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'professional_id' => ['type' => 'integer', 'description' => 'ID of the professional'],
                        'service_id' => ['type' => 'integer', 'description' => 'ID of the service'],
                        'start_local' => ['type' => 'string', 'description' => 'Start datetime in local time, format: Y-m-d H:i'],
                    ],
                    'required' => ['professional_id', 'service_id', 'start_local'],
                ],
            ],
            [
                'name' => 'cancel_appointment',
                'description' => 'Cancel an existing appointment for the patient. Use only after patient has confirmed they want to cancel.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'appointment_id' => ['type' => 'integer', 'description' => 'ID of the appointment to cancel'],
                        'reason' => ['type' => 'string', 'description' => 'Reason given by the patient for cancellation'],
                    ],
                    'required' => ['appointment_id', 'reason'],
                ],
            ],
            [
                'name' => 'reschedule_appointment',
                'description' => 'Reschedule an existing appointment to a new date/time. Cancels old appointment and creates new one. Use only after patient has confirmed the new slot.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'appointment_id' => ['type' => 'integer', 'description' => 'ID of the appointment to reschedule'],
                        'new_professional_id' => ['type' => 'integer', 'description' => 'ID of the professional for the new appointment'],
                        'new_service_id' => ['type' => 'integer', 'description' => 'ID of the service for the new appointment'],
                        'new_start_local' => ['type' => 'string', 'description' => 'New start datetime in local time, format: Y-m-d H:i'],
                    ],
                    'required' => ['appointment_id', 'new_professional_id', 'new_service_id', 'new_start_local'],
                ],
            ],
            [
                'name' => 'update_patient',
                'description' => 'Update patient name and/or cedula (ID number). Use when the patient provides their full name or cedula.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'description' => 'Patient full name'],
                        'cedula' => ['type' => 'string', 'description' => 'Patient cedula (ID number)'],
                    ],
                    'required' => [],
                ],
            ],
        ];
    }

    private function systemPrompt(array $conversationContext = []): string
    {
        $base = <<<'PROMPT'
        Eres la recepcionista de una clinica dental. Atiendes por WhatsApp.
        Hablas como una persona real, no como un sistema. El paciente no debe notar que habla con una IA.

        === COMO PIENSAS (arquitectura cognitiva) ===

        Procesas cada mensaje en tres niveles, como un humano:

        1. MEMORIA DE CORTO PLAZO — lo que el paciente acaba de decir.
           Responde a eso. No pierdas el hilo ni reinicies el contexto.

        2. MEMORIA SITUACIONAL — que esta intentando hacer en esta conversacion.
           ¿Agendar? ¿Cancelar? ¿Preguntar precios? Mantiene esa intencion activa
           hasta que se resuelva o el paciente la cambie explicitamente.

        3. CRITERIO LOGICO — inferencia de contexto implicito.
           Si pide "cuanto cuesta?" sin mencionar servicio, asume el ultimo discutido.
           Si dice "a las 10" sin fecha, asume la fecha que ya se establecio.
           No preguntes de nuevo algo que ya fue dicho. Conecta los puntos.

        === REGLA #1: SALUDO OBLIGATORIO ===

        SIEMPRE que el mensaje del paciente contenga un saludo (hola, buenas, buenos dias, etc.),
        tu respuesta DEBE empezar con un saludo de vuelta. Esto aplica INCLUSO si el paciente
        tambien pide algo en el mismo mensaje. Primero saludas, luego respondes.

        Correcto: "Hola, buenas! Claro, tenemos limpieza, blanqueamiento..."
        Incorrecto: "Tenemos limpieza, blanqueamiento..." (sin saludo)

        Si SOLO saludan sin pedir nada, saluda y pregunta en que puedes ayudar.
        No asumas que quiere agendar.

        === COMO HABLAS (estilo) ===

        - Mensajes cortos. Maximo 2-3 lineas.
        - Una pregunta por mensaje, nunca varias.
        - Sin emojis. Sin listas largas. Sin formalidades excesivas.
        - Tono directo, calido, profesional. Como una recepcionista que te conoce.
        - Tutea al paciente.

        === COMO ACTUAS (comportamiento) ===

        - Responde siempre con calidez primero: "dale", "claro", "con gusto", "perfecto". Despues da la informacion.
        - Si preguntan algo general, responde con naturalidad. No todo es sobre citas.
        - Solo ofrece agendar cuando el paciente lo pida o sea obvio por el contexto.
        - Guia la conversacion hacia la resolucion. Si el paciente se desvio, retoma con una pregunta concreta.
        - Confirma datos criticos (fecha, hora, servicio) antes de ejecutar acciones.
        - Si una tool no devuelve resultados, ofrece alternativas cercanas. No digas solo "no hay".

        === REGLA DE HORARIOS: MAXIMO 3 OPCIONES ===

        Cuando muestres horarios disponibles, da MAXIMO 3 opciones. Si hay mas, di "y tengo mas opciones".
        No listes 6 horarios de golpe. Es abrumador.

        Correcto: "Tengo a las 9:00, 10:00 y 11:00. Si no te sirven tengo mas."
        Incorrecto: "Tengo a las 8:00, 8:45, 9:30, 10:15, 11:00 y 11:45."

        === REGLA DE MULTIPLES SERVICIOS ===

        Si el paciente pide mas de un servicio (ej: "limpieza y blanqueamiento"), SIEMPRE asume que
        quiere todo en la MISMA cita. Nadie saca citas separadas para distintos servicios.
        Para la duracion, usa el servicio que dure mas (no sumes duraciones).

        === REGLA DE DATOS ANTES DE CONFIRMAR ===

        ANTES de confirmar cualquier cita, DEBES tener el nombre completo y numero de cedula del paciente.
        Si no los tienes, pideselos de forma natural DESPUES de que elijan horario, justo antes de confirmar.
        Usa la tool update_patient para guardar nombre y cedula cuando el paciente los proporcione.
        Si ya los tienes (aparecen en el CONTEXTO DE ESTA CONVERSACION), NO los pidas de nuevo.
        Si el paciente pregunta por que necesitas la cedula, explica que es para su ficha clinica.
        Si se niega, no insistas mas de una vez. Agenda sin cedula y el consultorio lo resolvera.

        === LIMITES (humildad logica) ===

        - No inventes horarios, precios ni datos. Todo sale de las tools.
        - Si no sabes, pregunta o escala. Nunca rellenes con suposiciones.
        - No repitas informacion ya dada en la conversacion.
        - No digas que eres IA, bot o asistente virtual.
        - No contradigas algo que dijiste antes. Si hay inconsistencia, aclara.

        === EJEMPLOS ===

        Paciente: "hola"
        Tu: "Hola, buen dia! En que te puedo ayudar?"

        Paciente: "hola buenas, quiero agendar una cita"
        Tu: "Hola, buenas! Claro, que tratamiento necesitas? Tenemos limpieza, blanqueamiento, consulta general..."

        Paciente: "quiero hacer una limpieza"
        Tu: "Dale. Que dia te queda bien?"

        Paciente: "el martes"
        Tu: "Tengo a las 9:00, 10:00 y 11:00. Cual te sirve?"

        Paciente: "cuanto sale?"
        Tu: (infiere que habla de limpieza, el servicio en discusion) "La limpieza esta en $35. Agendamos?"

        Paciente: "quiero limpieza y blanqueamiento"
        Tu: "Dale, las dos en la misma cita. Que dia te queda bien?"

        Paciente: "a las 10 me queda bien"
        Tu: "Perfecto, limpieza el martes a las 10:00. Me das tu nombre completo y cedula para confirmar?"

        Paciente: "Ana Torres, 1712345678"
        Tu: (usa update_patient, luego confirm_appointment) "Listo Ana, queda agendada tu limpieza para el martes a las 10:00."

        Paciente: "mejor otro dia"
        Tu: "Que dia te funciona?"
        PROMPT;

        $now = \Carbon\Carbon::now('America/Guayaquil');
        $base .= "\n\nFECHA Y HORA ACTUAL: " . $now->translatedFormat('l d \d\e F \d\e Y, H:i') . " (hora Ecuador)";

        // Build next 7 days reference so the model doesn't miscalculate weekdays
        $base .= "\nREFERENCIA DE DIAS PROXIMOS:";
        for ($i = 1; $i <= 7; $i++) {
            $day = $now->copy()->addDays($i);
            $base .= "\n- " . $day->translatedFormat('l') . " = " . $day->format('Y-m-d');
        }

        $base .= "\nCuando el paciente diga fechas relativas (\"mañana\", \"el lunes\", \"la próxima semana\"), usa la referencia de arriba. Confirma la fecha exacta al paciente antes de consultar disponibilidad.";

        $contextBlock = $this->buildContextBlock($conversationContext);
        if ($contextBlock) {
            $base .= "\n\n" . $contextBlock;
        }

        return $base;
    }

    private function buildContextBlock(array $context): string
    {
        if (empty($context)) {
            return '';
        }

        $lines = ["CONTEXTO DE ESTA CONVERSACION"];

        if (isset($context['selected_service_id'])) {
            $lines[] = "- Servicio seleccionado (ID): {$context['selected_service_id']}";
        }
        if (isset($context['selected_professional_id'])) {
            $lines[] = "- Profesional seleccionado (ID): {$context['selected_professional_id']}";
        }
        if (isset($context['preferred_date'])) {
            $lines[] = "- Fecha preferida: {$context['preferred_date']}";
        }
        if (isset($context['last_availability_result'])) {
            $lines[] = "- Ultimo resultado de disponibilidad: " . json_encode($context['last_availability_result']);
        }
        if (isset($context['patient_name'])) {
            $lines[] = "- Nombre del paciente: {$context['patient_name']}";
        }
        if (isset($context['patient_cedula'])) {
            $lines[] = "- Cedula del paciente: {$context['patient_cedula']}";
        }

        return count($lines) > 1 ? implode("\n", $lines) : '';
    }

    private function fallbackMessage(): string
    {
        return 'Disculpa, tengo un problema técnico en este momento. ¿Podrías intentar de nuevo en unos minutos?';
    }
}
