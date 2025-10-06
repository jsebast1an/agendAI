<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    public function sendText(string $to, string $text): array
    {
        $url = "https://graph.facebook.com/v22.0/".config('services.waba.phone_id')."/messages";

        $res = Http::withToken(config('services.waba.token'))
            ->post($url, [
                "messaging_product" => "whatsapp",
                "to" => '541126546323',
                "type" => "text",
                "text" => ["body" => $text],
            ])->json();

        return $res ?? [];
    }
}
