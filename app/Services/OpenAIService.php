<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    // guarda 6 turnos por usuario en cache (simple)
    protected function historyKey(string $user): string { return "wa_hist_".$user; }

    public function replyBasic(string $user, string $userText): string
    {
        // $history = Cache::get($this->historyKey($user), []);

        $systemPrompt = "
                    Eres el asistente digital de un teatro en Quito.
                    Respondes de forma amable.
                    Hay parqueadero disponible por USD 2.
                    Aceptamos tarjeta debito y credito.
                    Las funciones se consultan segun fecha.
                    Si no sabes algo, ofrece transferir a humano.
                    ";

        $resp = Http::withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/responses', [
                "model" => "gpt-4.1",
                "input" => $userText,
                "instructions" => $systemPrompt,
                "temperature" => 0.5
            ])->json();

        $answer = $resp['output'][0]['content'][0]['text'] ?? "Hola. En que puedo ayudarte?";

        // $history[] = ["role"=>"user","content"=>$userText];
        // $history[] = ["role"=>"assistant","content"=>$answer];
        // $history = array_slice($history, -6);
        // Cache::put($this->historyKey($user), $history, now()->addHours(6));
        Log::channel('api')->info("ANSWER: {$answer}");

        return $answer;
    }
}
