<?php

namespace App\Http\Controllers;

use App\Services\AnthropicService;
use App\Services\PatientResolverService;
use App\Services\TenantResolverService;
use App\Services\WhatsappService;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    public function verify(Request $r)
    {
        $token = $r->query('hub_verify_token') ?? $r->query('hub.verify_token');
        if ($token === config('services.waba.verify')) {
            return $r->get('hub_challenge') ?? $r->get('hub.challenge');
        }
        return response('Invalid verify token', 403);
    }

    public function handle(
        Request $r,
        WhatsappService $wa,
        AnthropicService $anthropic,
        TenantResolverService $tenantResolver,
        PatientResolverService $patientResolver,
    ) {
        try {
            $payload = $r->all();
            $entry = data_get($payload, 'entry.0.changes.0.value');
            $msg = data_get($entry, 'messages.0');

            if (!is_array($msg)) {
                Log::info('WA inbound sin messages (status/delivery u otro). ACK.');
                return response()->json(['ok' => true]);
            }

            $from = data_get($msg, 'from');
            $businessNumber = data_get($entry, 'metadata.display_phone_number');
            $text = data_get($msg, 'text.body') ?? '[Contenido no textual]';

            Log::channel('api')->info('WA inbound', [
                'from' => $from,
                'business_number' => $businessNumber,
            ]);

            // Resolve tenant (organization)
            $org = $tenantResolver->resolve($businessNumber ?? '');
            if (!$org) {
                Log::channel('api')->warning('Unknown business number', ['number' => $businessNumber]);
                return response()->json(['ok' => true]);
            }

            // Resolve or auto-register patient
            $patient = $patientResolver->resolve($org, $from ?? 'unknown');

            $conversation = Conversation::firstOrCreate(
                ['phone_number' => $from ?? 'unknown', 'organization_id' => $org->id],
                [
                    'patient_id' => $patient->id,
                    'conversation_status' => 'active',
                    'handoff_to_human' => false,
                ]
            );

            $memoryLimit = (int) config('services.waba.memory_limit', 10);
            $history = $conversation->messages()
                ->orderBy('created_at', 'desc')
                ->take($memoryLimit)
                ->get()
                ->reverse()
                ->values();

            $conversation->messages()->create([
                'role' => 'user',
                'content' => $text,
            ]);

            $responseAI = $anthropic->reply($from ?? 'unknown', $text, $history, $org->id, $patient->id, $conversation);
            Log::channel('api')->info("ANSWER: {$responseAI}");

            $res = $wa->sendText($from ?? 'unknown', $responseAI);

            $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $responseAI,
            ]);

            return response()->json(['ok' => $res]);
        } catch (\Throwable $e) {
            Log::channel('api')->error('WA webhook ERROR', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['ok' => false], 200);
        }
    }
}
