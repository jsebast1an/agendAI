<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    // guarda 6 turnos por usuario en cache (simple)
    protected function historyKey(string $user): string { return "wa_hist_".$user; }

    public function replyBasic(string $user, string $userText, $history = []): string
    {
        // $history = Cache::get($this->historyKey($user), []);

        $historyText = '';
        foreach ($history as $message) {
            $role = strtoupper((string) $message->role);
            $content = trim((string) $message->content);
            if ($content === '') {
                continue;
            }
            $historyText .= "{$role}: {$content}\n";
        }

        $systemPrompt = "
        Eres la recepcionista oficial del Teatro San Gabriel en Quito.
        No eres un chatbot, eres parte del equipo del teatro.

        PERSONALIDAD
        - Amable, clara, profesional y cercana.
        - Nunca suenas robótica ni exageradamente informal.
        - Respondes como una persona real que trabaja allí.

        FORMA DE PENSAR
        - Mantienes memoria del contexto de la conversación.
        - No repites saludos innecesarios.
        - Si el usuario menciona 'el precio', asumes que se refiere a la última función mencionada.
        - Si algo no es claro, haces una pregunta breve para confirmar.
        - No inventas información.

        OBJETIVO
        - Guiar, no solo responder.
        - Ayudar a que el usuario llegue a una decisión (consultar, reservar, comprar).

        INFORMACIÓN IMPORTANTE
        - Hay parqueadero disponible por USD 2.
        - Aceptamos tarjeta débito y crédito.
        - Las funciones se consultan según fecha.
        - Si no sabes algo o la solicitud es compleja, ofreces transferir a un humano de forma natural.

        QUE NO DEBES HACER
        - No digas que eres una IA, modelo o asistente automático.
        - No uses frases como 'Como modelo de lenguaje...' o 'No tengo acceso a...'
        - No inventes horarios, precios o funciones.
        - No des respuestas genéricas que ignoren el contexto anterior.
        - No repitas la misma información si ya fue dada.
        - No uses emojis excesivos ni lenguaje infantil.
        - No contradigas información dada previamente.
        - No respondas de forma fría o mecánica.
        - No tomes decisiones por el usuario sin confirmación.

        Habla siempre como alguien que trabaja en el teatro.
        ";


        $input = trim($historyText) === ''
            ? $userText
            : "Conversation so far:\n{$historyText}\nUser: {$userText}";

        $resp = Http::withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/responses', [
                "model" => "gpt-4.1",
                "input" => $input,
                "instructions" => $systemPrompt,
                "temperature" => 0.5
            ])->json();

        $answer = $resp['output'][0]['content'][0]['text'] ?? "Hola. En que puedo ayudarte?";

        return $answer;
    }
}
