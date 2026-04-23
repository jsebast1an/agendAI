<?php

namespace Tests\Unit\Services;

use App\Models\Conversation;
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

        $service = new AnthropicService(new AgendaToolsService);
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

        $service = new AnthropicService(new AgendaToolsService);
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

        $service = new AnthropicService(new AgendaToolsService);
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

        $service = new AnthropicService(new AgendaToolsService);
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

        $service = new AnthropicService(new AgendaToolsService);
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

    // --- Context tests ---

    public function test_reply_injects_context_into_system_prompt(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'stop_reason' => 'end_turn',
                'content' => [
                    ['type' => 'text', 'text' => 'Respuesta'],
                ],
            ], 200),
        ]);

        $contextData = [
            'selected_service_id' => 5,
            'selected_professional_id' => 3,
            'preferred_date' => '2026-03-15',
        ];

        $conversation = $this->getMockBuilder(Conversation::class)
            ->onlyMethods(['update'])
            ->getMock();
        $conversation->context = $contextData;
        $conversation->expects($this->once())->method('update');

        $service = new AnthropicService(new AgendaToolsService);
        $service->reply('593991234567', 'Hola', collect(), 1, 1, $conversation);

        Http::assertSent(function ($request) {
            $system = $request['system'] ?? '';

            return str_contains($system, 'CONTEXTO DE ESTA CONVERSACION')
                && str_contains($system, 'Servicio seleccionado (ID): 5')
                && str_contains($system, 'Profesional seleccionado (ID): 3')
                && str_contains($system, 'Fecha preferida: 2026-03-15');
        });
    }

    public function test_reply_does_not_inject_context_when_empty(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'stop_reason' => 'end_turn',
                'content' => [
                    ['type' => 'text', 'text' => 'Respuesta'],
                ],
            ], 200),
        ]);

        $service = new AnthropicService(new AgendaToolsService);
        $service->reply('593991234567', 'Hola', collect());

        Http::assertSent(function ($request) {
            $system = $request['system'] ?? '';

            return ! str_contains($system, 'CONTEXTO DE ESTA CONVERSACION');
        });
    }

    public function test_context_updated_after_get_availability_tool_call(): void
    {
        $availabilitySlots = [
            ['start' => '09:00', 'end' => '09:30'],
            ['start' => '10:00', 'end' => '10:30'],
        ];

        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push([
                    'stop_reason' => 'tool_use',
                    'content' => [
                        [
                            'type' => 'tool_use',
                            'id' => 'toolu_avail',
                            'name' => 'get_availability',
                            'input' => [
                                'professional_id' => 3,
                                'service_id' => 5,
                                'date_local' => '2026-03-15',
                            ],
                        ],
                    ],
                ], 200)
                ->push([
                    'stop_reason' => 'end_turn',
                    'content' => [
                        ['type' => 'text', 'text' => 'Hay dos horarios disponibles.'],
                    ],
                ], 200),
        ]);

        $mockTools = $this->createMock(AgendaToolsService::class);
        $mockTools->method('getAvailability')->willReturn($availabilitySlots);

        $savedContext = null;
        $conversation = $this->getMockBuilder(Conversation::class)
            ->onlyMethods(['update'])
            ->getMock();
        $conversation->context = [];
        $conversation->expects($this->once())
            ->method('update')
            ->with($this->callback(function ($data) use (&$savedContext) {
                $savedContext = $data['context'];

                return true;
            }));

        $service = new AnthropicService($mockTools);
        $service->reply('593991234567', 'Disponibilidad?', collect(), 1, 1, $conversation);

        $this->assertEquals(5, $savedContext['selected_service_id']);
        $this->assertEquals(3, $savedContext['selected_professional_id']);
        $this->assertEquals('2026-03-15', $savedContext['preferred_date']);
        $this->assertEquals($availabilitySlots, $savedContext['last_availability_result']);
    }

    public function test_context_updated_after_get_professionals_with_service_id(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push([
                    'stop_reason' => 'tool_use',
                    'content' => [
                        [
                            'type' => 'tool_use',
                            'id' => 'toolu_prof',
                            'name' => 'get_professionals',
                            'input' => ['service_id' => 7],
                        ],
                    ],
                ], 200)
                ->push([
                    'stop_reason' => 'end_turn',
                    'content' => [
                        ['type' => 'text', 'text' => 'Tenemos al Dr. Lopez.'],
                    ],
                ], 200),
        ]);

        $mockTools = $this->createMock(AgendaToolsService::class);
        $mockTools->method('getProfessionals')->willReturn([
            ['id' => 3, 'name' => 'Dr. Lopez'],
        ]);

        $savedContext = null;
        $conversation = $this->getMockBuilder(Conversation::class)
            ->onlyMethods(['update'])
            ->getMock();
        $conversation->context = [];
        $conversation->expects($this->once())
            ->method('update')
            ->with($this->callback(function ($data) use (&$savedContext) {
                $savedContext = $data['context'];

                return true;
            }));

        $service = new AnthropicService($mockTools);
        $service->reply('593991234567', 'Que doctores hay?', collect(), 1, 1, $conversation);

        $this->assertEquals(7, $savedContext['selected_service_id']);
    }

    public function test_context_not_saved_when_no_conversation(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'stop_reason' => 'end_turn',
                'content' => [
                    ['type' => 'text', 'text' => 'Respuesta'],
                ],
            ], 200),
        ]);

        $service = new AnthropicService(new AgendaToolsService);
        // Should not throw - no conversation means no save
        $result = $service->reply('593991234567', 'Hola', collect());

        $this->assertEquals('Respuesta', $result);
    }

    public function test_reply_sends_confirm_and_cancel_tool_definitions(): void
    {
        $captured = null;

        Http::fake(function ($request) use (&$captured) {
            $captured = $request->data();

            return Http::response([
                'stop_reason' => 'end_turn',
                'content' => [['type' => 'text', 'text' => 'Ok']],
            ], 200);
        });

        $service = new AnthropicService(new AgendaToolsService);
        $service->reply('593991234567', 'Quiero cancelar mi cita', collect(), 1, 1);

        $toolNames = collect($captured['tools'])->pluck('name')->all();
        $this->assertContains('confirm_appointment', $toolNames);
        $this->assertContains('cancel_appointment', $toolNames);
    }

    public function test_reply_sends_reschedule_tool_definition(): void
    {
        $captured = null;

        Http::fake(function ($request) use (&$captured) {
            $captured = $request->data();

            return Http::response([
                'stop_reason' => 'end_turn',
                'content' => [['type' => 'text', 'text' => 'Ok']],
            ], 200);
        });

        $service = new AnthropicService(new AgendaToolsService);
        $service->reply('593991234567', 'Quiero reprogramar', collect(), 1, 1);

        $toolNames = collect($captured['tools'])->pluck('name')->all();
        $this->assertContains('reschedule_appointment', $toolNames);
    }

    public function test_reply_handles_reschedule_appointment_tool_call(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push([
                    'stop_reason' => 'tool_use',
                    'content' => [[
                        'type' => 'tool_use',
                        'id' => 'toolu_reschedule1',
                        'name' => 'reschedule_appointment',
                        'input' => [
                            'appointment_id' => 10,
                            'new_professional_id' => 1,
                            'new_service_id' => 1,
                            'new_start_local' => '2026-04-15 09:00',
                        ],
                    ]],
                ], 200)
                ->push([
                    'stop_reason' => 'end_turn',
                    'content' => [['type' => 'text', 'text' => 'Cita reprogramada.']],
                ], 200),
        ]);

        $mockTools = $this->createMock(AgendaToolsService::class);
        $mockTools->expects($this->once())
            ->method('rescheduleAppointment')
            ->willReturn(['appointment_id' => 99, 'start_local' => '2026-04-15 09:00', 'end_local' => '2026-04-15 09:30', 'professional' => 'Dr. Test', 'service' => 'Consulta']);

        $service = new AnthropicService($mockTools);
        $result = $service->reply('593991234567', 'Reprogramar', collect(), 1, 1);

        $this->assertEquals('Cita reprogramada.', $result);
    }

    public function test_reply_handles_confirm_appointment_tool_call(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push([
                    'stop_reason' => 'tool_use',
                    'content' => [[
                        'type' => 'tool_use',
                        'id' => 'toolu_confirm1',
                        'name' => 'confirm_appointment',
                        'input' => [
                            'professional_id' => 1,
                            'service_id' => 1,
                            'start_local' => '2026-04-10 10:00',
                        ],
                    ]],
                ], 200)
                ->push([
                    'stop_reason' => 'end_turn',
                    'content' => [['type' => 'text', 'text' => 'Cita confirmada.']],
                ], 200),
        ]);

        $mockTools = $this->createMock(AgendaToolsService::class);
        $mockTools->expects($this->once())
            ->method('confirmAppointment')
            ->willReturn(['appointment_id' => 42, 'start_local' => '2026-04-10 10:00', 'end_local' => '2026-04-10 10:30', 'professional' => 'Dr. Test', 'service' => 'Consulta']);

        $service = new AnthropicService($mockTools);
        $result = $service->reply('593991234567', 'Confirmar cita', collect(), 1, 1);

        $this->assertEquals('Cita confirmada.', $result);
    }

    public function test_reply_triggers_handoff_when_tool_fails_repeatedly(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push([
                    'stop_reason' => 'tool_use',
                    'content' => [[
                        'type' => 'tool_use',
                        'id' => 'toolu_fail1',
                        'name' => 'confirm_appointment',
                        'input' => ['professional_id' => 1, 'service_id' => 1, 'start_local' => '2026-04-10 10:00'],
                    ]],
                ], 200)
                ->push([
                    'stop_reason' => 'tool_use',
                    'content' => [[
                        'type' => 'tool_use',
                        'id' => 'toolu_fail2',
                        'name' => 'confirm_appointment',
                        'input' => ['professional_id' => 1, 'service_id' => 1, 'start_local' => '2026-04-10 10:00'],
                    ]],
                ], 200),
        ]);

        $mockTools = $this->createMock(AgendaToolsService::class);
        $mockTools->method('confirmAppointment')
            ->willThrowException(new \RuntimeException('DB down'));

        $conversation = $this->createMock(Conversation::class);
        $conversation->method('__get')->willReturnMap([
            ['context', []],
            ['handoff_to_human', false],
        ]);
        $conversation->expects($this->atLeastOnce())
            ->method('update')
            ->with($this->arrayHasKey('handoff_to_human'));

        $service = new AnthropicService($mockTools);
        $result = $service->reply('593991234567', 'Confirmar cita', collect(), 1, 1, $conversation);

        $this->assertStringContainsString('consultorio', $result);
    }
}
