<?php

namespace Tests\Unit\Services;

use App\Services\AgendaToolsService;
use App\Services\AnthropicService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AnthropicServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.anthropic.key' => 'test-key']);
        config(['services.anthropic.model' => 'claude-sonnet-4-20250514']);
    }

    public function test_reply_returns_text_from_claude_api(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'stop_reason' => 'end_turn',
                'content' => [
                    ['type' => 'text', 'text' => 'Hola, en qué puedo ayudarte?'],
                ],
            ], 200),
        ]);

        $service = new AnthropicService(new AgendaToolsService());
        $result = $service->reply('593991234567', 'Quiero agendar una cita', collect());

        $this->assertEquals('Hola, en qué puedo ayudarte?', $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.anthropic.com/v1/messages'
                && $request->header('x-api-key')[0] === 'test-key'
                && $request->header('anthropic-version')[0] === '2023-06-01'
                && $request['model'] === 'claude-sonnet-4-20250514'
                && count($request['messages']) === 1
                && $request['messages'][0]['role'] === 'user'
                && $request['messages'][0]['content'] === 'Quiero agendar una cita';
        });
    }

    public function test_reply_includes_conversation_history(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'stop_reason' => 'end_turn',
                'content' => [
                    ['type' => 'text', 'text' => 'El Dr. López tiene disponibilidad mañana.'],
                ],
            ], 200),
        ]);

        $history = collect([
            (object) ['role' => 'user', 'content' => 'Hola'],
            (object) ['role' => 'assistant', 'content' => 'Hola! Bienvenido.'],
        ]);

        $service = new AnthropicService(new AgendaToolsService());
        $service->reply('593991234567', 'Hay disponibilidad?', $history);

        Http::assertSent(function ($request) {
            $messages = $request['messages'];
            return count($messages) === 3
                && $messages[0]['role'] === 'user'
                && $messages[0]['content'] === 'Hola'
                && $messages[1]['role'] === 'assistant'
                && $messages[1]['content'] === 'Hola! Bienvenido.'
                && $messages[2]['role'] === 'user'
                && $messages[2]['content'] === 'Hay disponibilidad?';
        });
    }

    public function test_reply_returns_fallback_on_api_error(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response(['error' => 'server error'], 500),
        ]);

        $service = new AnthropicService(new AgendaToolsService());
        $result = $service->reply('593991234567', 'Hola', collect());

        $this->assertEquals(
            'Disculpa, tengo un problema técnico en este momento. ¿Podrías intentar de nuevo en unos minutos?',
            $result
        );
    }

    public function test_reply_sends_system_prompt(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'stop_reason' => 'end_turn',
                'content' => [
                    ['type' => 'text', 'text' => 'Respuesta'],
                ],
            ], 200),
        ]);

        $service = new AnthropicService(new AgendaToolsService());
        $service->reply('593991234567', 'Hola', collect());

        Http::assertSent(function ($request) {
            return isset($request['system'])
                && str_contains($request['system'], 'recepcionista');
        });
    }

    public function test_reply_sends_tool_definitions(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'stop_reason' => 'end_turn',
                'content' => [
                    ['type' => 'text', 'text' => 'Respuesta'],
                ],
            ], 200),
        ]);

        $service = new AnthropicService(new AgendaToolsService());
        $service->reply('593991234567', 'Qué servicios tienen?', collect(), 1, 1);

        Http::assertSent(function ($request) {
            $tools = $request['tools'] ?? [];
            $toolNames = array_column($tools, 'name');
            return in_array('get_services', $toolNames)
                && in_array('get_professionals', $toolNames)
                && in_array('get_availability', $toolNames)
                && in_array('list_upcoming_appointments', $toolNames);
        });
    }

    public function test_reply_handles_tool_use_response(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push([
                    'stop_reason' => 'tool_use',
                    'content' => [
                        ['type' => 'text', 'text' => 'Voy a consultar los servicios.'],
                        [
                            'type' => 'tool_use',
                            'id' => 'toolu_123',
                            'name' => 'get_services',
                            'input' => ['org_id' => 1],
                        ],
                    ],
                ], 200)
                ->push([
                    'stop_reason' => 'end_turn',
                    'content' => [
                        ['type' => 'text', 'text' => 'Tenemos Consulta General disponible.'],
                    ],
                ], 200),
        ]);

        $mockTools = $this->createMock(AgendaToolsService::class);
        $mockTools->method('getServices')->willReturn([
            ['id' => 1, 'name' => 'Consulta General', 'description' => 'Consulta médica'],
        ]);

        $service = new AnthropicService($mockTools);
        $result = $service->reply('593991234567', 'Qué servicios tienen?', collect(), 1, 1);

        $this->assertEquals('Tenemos Consulta General disponible.', $result);

        // Verify second call includes tool_result
        Http::assertSent(function ($request) {
            $messages = $request['messages'] ?? [];
            foreach ($messages as $msg) {
                if ($msg['role'] === 'user' && is_array($msg['content'])) {
                    foreach ($msg['content'] as $block) {
                        if (($block['type'] ?? '') === 'tool_result') {
                            return true;
                        }
                    }
                }
            }
            return false;
        });
    }
}
