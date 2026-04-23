<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    public function sendText(string $to, string $text): array
    {
        $url = 'https://graph.facebook.com/v22.0/'.config('services.waba.phone_id').'/messages';

        // In local/dev, optionally force all outbound messages to a test number.
        $to = app()->environment('local', 'development')
            ? (config('services.waba.test_to') ?: $to)
            : $to;
        Log::channel('api')->info('to:'.$to);
        $response = Http::withToken(config('services.waba.token'))
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => ['body' => $text],
            ]);

        $res = $response->json() ?? [];

        if ($response->failed()) {
            Log::channel('api')->error('WhatsApp send failed', [
                'to' => $to,
                'status' => $response->status(),
                'response' => $res,
            ]);
        }

        return $res;
    }
}
