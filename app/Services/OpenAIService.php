<?php
// app/Services/OpenAIService.php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class OpenAIService
{
    // guarda 6 turnos por usuario en cache (simple)
    protected function historyKey(string $user): string { return "wa_hist_".$user; }

    public function replyBasic(string $user, string $userText): string
    {
        $history = Cache::get($this->historyKey($user), []);

        $messages = array_merge([
            ["role"=>"system","content" =>
                "Eres AgendAI, recepcionista amable y un poco 'closer'. Responde en 1-3 líneas, claro, cordial y útil. Si el usuario pregunta por precios/horarios, explica breve y ofrece seguir conversando. No inventes datos específicos del negocio todavía."
            ],
        ], $history, [
            ["role"=>"user","content"=>$userText]
        ]);

        $resp = Http::withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/chat/completions', [
                "model" => "gpt-4o-mini",
                "messages" => $messages,
                "temperature" => 0.5
            ])->json();

        $answer = $resp['choices'][0]['message']['content'] ?? "¡Hola! ¿En qué puedo ayudarte? 🙂";

        // actualiza historial (máx 6 mensajes)
        $history[] = ["role"=>"user","content"=>$userText];
        $history[] = ["role"=>"assistant","content"=>$answer];
        $history = array_slice($history, -6);
        Cache::put($this->historyKey($user), $history, now()->addHours(6));

        return $answer;
    }
}
