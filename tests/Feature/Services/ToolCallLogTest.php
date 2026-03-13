<?php

namespace Tests\Feature\Services;

use App\Models\Organization;
use App\Models\ToolCallLog;
use App\Services\AgendaToolsService;
use App\Services\AnthropicService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ToolCallLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.anthropic.key' => 'test-key']);
        config(['services.anthropic.model' => 'claude-sonnet-4-20250514']);
    }

    public function test_tool_call_is_persisted_to_database(): void
    {
        $org = Organization::create([
            'name' => 'Test Clinic',
            'wa_phone_number' => '593991111111',
            'timezone' => 'America/Guayaquil',
        ]);

        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push([
                    'stop_reason' => 'tool_use',
                    'content' => [
                        [
                            'type' => 'tool_use',
                            'id' => 'toolu_log1',
                            'name' => 'get_services',
                            'input' => [],
                        ],
                    ],
                ], 200)
                ->push([
                    'stop_reason' => 'end_turn',
                    'content' => [
                        ['type' => 'text', 'text' => 'Tenemos consulta general.'],
                    ],
                ], 200),
        ]);

        $mockTools = $this->createMock(AgendaToolsService::class);
        $mockTools->method('getServices')->willReturn([
            ['id' => 1, 'name' => 'Consulta General'],
        ]);

        $service = new AnthropicService($mockTools);
        $service->reply('593991234567', 'Servicios?', collect(), $org->id, 1);

        $this->assertDatabaseCount('tool_call_logs', 1);

        $log = ToolCallLog::first();
        $this->assertEquals($org->id, $log->organization_id);
        $this->assertEquals('get_services', $log->tool_name);
        $this->assertTrue($log->success);
        $this->assertIsArray($log->result);
        $this->assertGreaterThanOrEqual(0, $log->duration_ms);
    }

    public function test_failed_tool_call_logs_error(): void
    {
        $org = Organization::create([
            'name' => 'Test Clinic',
            'wa_phone_number' => '593991111111',
            'timezone' => 'America/Guayaquil',
        ]);

        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push([
                    'stop_reason' => 'tool_use',
                    'content' => [
                        [
                            'type' => 'tool_use',
                            'id' => 'toolu_err1',
                            'name' => 'get_availability',
                            'input' => [
                                'professional_id' => 999,
                                'service_id' => 999,
                                'date_local' => '2026-03-15',
                            ],
                        ],
                    ],
                ], 200)
                ->push([
                    'stop_reason' => 'end_turn',
                    'content' => [
                        ['type' => 'text', 'text' => 'Hubo un error.'],
                    ],
                ], 200),
        ]);

        $mockTools = $this->createMock(AgendaToolsService::class);
        $mockTools->method('getAvailability')->willThrowException(new \RuntimeException('DB error'));

        $service = new AnthropicService($mockTools);
        $service->reply('593991234567', 'Disponibilidad?', collect(), $org->id, 1);

        $this->assertDatabaseCount('tool_call_logs', 1);

        $log = ToolCallLog::first();
        $this->assertFalse($log->success);
        $this->assertEquals('Tool execution failed', $log->error_message);
    }

    public function test_tool_call_log_scoped_by_organization(): void
    {
        $org1 = Organization::create([
            'name' => 'Clinic A',
            'wa_phone_number' => '593991111111',
            'timezone' => 'America/Guayaquil',
        ]);

        $org2 = Organization::create([
            'name' => 'Clinic B',
            'wa_phone_number' => '593992222222',
            'timezone' => 'America/Guayaquil',
        ]);

        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push([
                    'stop_reason' => 'tool_use',
                    'content' => [
                        [
                            'type' => 'tool_use',
                            'id' => 'toolu_a',
                            'name' => 'get_services',
                            'input' => [],
                        ],
                    ],
                ], 200)
                ->push([
                    'stop_reason' => 'end_turn',
                    'content' => [['type' => 'text', 'text' => 'Ok']],
                ], 200)
                ->push([
                    'stop_reason' => 'tool_use',
                    'content' => [
                        [
                            'type' => 'tool_use',
                            'id' => 'toolu_b',
                            'name' => 'get_services',
                            'input' => [],
                        ],
                    ],
                ], 200)
                ->push([
                    'stop_reason' => 'end_turn',
                    'content' => [['type' => 'text', 'text' => 'Ok']],
                ], 200),
        ]);

        $mockTools = $this->createMock(AgendaToolsService::class);
        $mockTools->method('getServices')->willReturn([]);

        $service = new AnthropicService($mockTools);
        $service->reply('593991234567', 'Servicios?', collect(), $org1->id, 1);
        $service->reply('593992345678', 'Servicios?', collect(), $org2->id, 2);

        $this->assertEquals(1, ToolCallLog::where('organization_id', $org1->id)->count());
        $this->assertEquals(1, ToolCallLog::where('organization_id', $org2->id)->count());
    }

    public function test_no_log_persisted_without_org_id(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'stop_reason' => 'end_turn',
                'content' => [['type' => 'text', 'text' => 'Hola']],
            ], 200),
        ]);

        $service = new AnthropicService(new AgendaToolsService());
        $service->reply('593991234567', 'Hola', collect());

        $this->assertDatabaseCount('tool_call_logs', 0);
    }
}
