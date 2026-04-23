<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessConversationJob;
use App\Models\Conversation;
use App\Services\PatientResolverService;
use App\Services\TenantResolverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    private const DEBOUNCE_SECONDS = 2;

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
        TenantResolverService $tenantResolver,
        PatientResolverService $patientResolver,
    ) {
        try {
            $payload = $r->all();
            $entry = data_get($payload, 'entry.0.changes.0.value');
            $msg = data_get($entry, 'messages.0');

            if (! is_array($msg)) {
                return response()->json(['ok' => true]);
            }

            $from = data_get($msg, 'from');
            $businessNumber = data_get($entry, 'metadata.display_phone_number');
            $text = data_get($msg, 'text.body') ?? '[Contenido no textual]';

            Log::channel('api')->info('WA inbound', ['from' => $from, 'business_number' => $businessNumber]);

            $org = $tenantResolver->resolve($businessNumber ?? '');
            if (! $org) {
                Log::channel('api')->warning('Unknown business number', ['number' => $businessNumber]);

                return response()->json(['ok' => true]);
            }

            $patient = $patientResolver->resolve($org, $from ?? 'unknown');

            $conversation = Conversation::firstOrCreate(
                ['phone_number' => $from ?? 'unknown', 'organization_id' => $org->id],
                [
                    'patient_id' => $patient->id,
                    'conversation_status' => 'active',
                    'handoff_to_human' => false,
                ]
            );

            if ($conversation->handoff_to_human) {
                Log::channel('api')->info('Conversation in handoff — skipping', ['from' => $from]);

                return response()->json(['ok' => true]);
            }

            // Add message to the pending batch for this conversation window
            $pendingKey = "whatsapp.pending.{$conversation->id}";
            $pending = Cache::get($pendingKey, []);
            $pending[] = $text;
            Cache::put($pendingKey, $pending, now()->addSeconds(60));

            // Each message dispatches a delayed job — only the last one will find messages
            ProcessConversationJob::dispatch($conversation->id, $from)
                ->delay(now()->addSeconds(self::DEBOUNCE_SECONDS));

            Log::channel('api')->info('Message queued', [
                'conversation_id' => $conversation->id,
                'pending_count' => count($pending),
            ]);

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            Log::channel('api')->error('WA webhook ERROR', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['ok' => false], 200);
        }
    }
}
