<?php

namespace Tests\Feature;

use App\Services\OpenAIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;


class OpenAIServiceTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_reply_basic_hits_openai_and_returns_text(): void
    {
        // Si no hay key, saltar para no fallar en CI sin credenciales
        $key = config('services.openai.key');
        if (empty($key) || !Str::startsWith($key, 'sk-')) {
            $this->markTestSkipped('OPENAI_API_KEY no configurado o inválido.');
        }


        // Cache en memoria para no ensuciar otros drivers
        // config(['cache.default' => 'array']);
        // Cache::flush();

        // Instancia real del service
        $svc = app(OpenAIService::class);

        // User “falso” de prueba (por ej. un número de WhatsApp)
        $userId   = 'live-test-user-12345';
        $userText = 'Hola, ¿puedes confirmarme que el endpoint de OpenAI está funcionando?';

        // Llamada real
        $answer = $svc->replyBasic($userId, $userText);
        Log::channel('api')->info('OpenAI test reply', ['answer'=>$answer]);
        // Aserciones mínimas pero robustas (evitan flakiness)
        $this->assertIsString($answer, 'La respuesta debe ser string');
        $this->assertNotEmpty(trim($answer), 'La respuesta no debe venir vacía');

        // // Verifica que el historial se guarde y se trunque a 6
        // $hist = Cache::get('wa_hist_' . $userId, []);
        // $this->assertNotEmpty($hist, 'Debe existir historial en cache');
        // $this->assertLessThanOrEqual(6, count($hist), 'El historial debe truncarse a 6 mensajes como máximo');

    }
}
