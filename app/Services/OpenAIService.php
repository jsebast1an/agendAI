<?php
// app/Services/OpenAIService.php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    // guarda 6 turnos por usuario en cache (simple)
    protected function historyKey(string $user): string { return "wa_hist_".$user; }

    public function replyBasic(string $user, string $userText) : string
    {
        // $history = Cache::get($this->historyKey($user), []);

        $resp = Http::withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/responses', [
                "model" => "gpt-4.1",
                "input" => $userText,
                "instructions" => "Eres AgendAI, recepcionista amable y un poco 'closer'. Responde en 1-3 líneas, claro, cordial y útil. Si el usuario pregunta por precios/horarios, explica breve y ofrece seguir conversando. No inventes datos específicos del negocio todavía.",
                "temperature" => 0.5
            ])->json();

        $answer = $resp['output'][0]['content'][0]['text'] ?? "¡Hola! ¿En qué puedo ayudarte? 🙂";

        // actualiza historial (máx 6 mensajes)
        // $history[] = ["role"=>"user","content"=>$userText];
        // $history[] = ["role"=>"assistant","content"=>$answer];
        // $history = array_slice($history, -6);
        // Cache::put($this->historyKey($user), $history, now()->addHours(6));
        // Log::channel('api')->info("ANSWER: {$answer}");

        return $answer;
    }
}
