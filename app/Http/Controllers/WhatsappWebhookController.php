<?php

namespace App\Http\Controllers;

use App\Services\OpenAIService;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    public function verify(Request $r)
    {
        $token     = $r->query('hub_verify_token') ?? $r->query('hub.verify_token');
        if ($token === config('services.waba.verify')) {
            return $r->get('hub_challenge');
        }
        return response('Invalid verify token', 403);
    }

    public function handle(Request $r, \App\Services\WhatsappService $wa)
    {
        try {
            $payload = $r->all();
            $entry = data_get($payload, 'entry.0.changes.0.value');
            $msg   = data_get($entry, 'messages.0');

            if (!is_array($msg)) {
                Log::info('WA inbound sin messages (status/delivery u otro). ACK.');
                return response()->json(['ok' => true]);
            }

            $from = data_get($msg, 'from');
            $text =
                data_get($msg, 'text.body') ??
                data_get($msg, 'button.text') ??
                data_get($msg, 'interactive.button_reply.title') ??
                data_get($msg, 'interactive.list_reply.title') ??
                data_get($msg, 'image.caption') ??
                '[Contenido no textual]';

            Log::info('FROM: ', $msg);
            Log::info('WA inbound RAW: '.$r->getContent());

            // Respuesta fija para confirmar ida y vuelta
            $res = $wa->sendText($from, "Recibí tu mensaje ✔️: {$text}");

            return response()->json(['ok' => $res]);
        } catch (\Throwable $e) {
            Log::error('WA webhook ERROR', ['msg'=>$e->getMessage(), 'trace'=>$e->getTraceAsString()]);
            // 200 para que Meta no reintente en bucle mientras depuras
            return response()->json(['ok' => false], 200);
        }
    }

}
