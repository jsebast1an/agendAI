<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Services\AnthropicService;
use App\Services\WhatsappService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessConversationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $conversationId,
        private readonly string $from,
        private readonly int $nonce,
    ) {}

    public function handle(AnthropicService $anthropic, WhatsappService $wa): void
    {
        // If a newer message arrived after this job was dispatched, abort
        $currentNonce = Cache::get("whatsapp.nonce.{$this->conversationId}", 0);
        if ($currentNonce !== $this->nonce) {
            Log::channel('api')->info('Job superseded by newer message — skipping', [
                'conversation_id' => $this->conversationId,
                'job_nonce' => $this->nonce,
                'current_nonce' => $currentNonce,
            ]);
            return;
        }

        $conversation = Conversation::find($this->conversationId);
        if (!$conversation || $conversation->handoff_to_human) {
            return;
        }

        // Grab all pending messages for this conversation window
        $pendingMessages = Cache::pull("whatsapp.pending.{$this->conversationId}", []);
        if (empty($pendingMessages)) {
            return;
        }

        // Combine all messages into one text
        $combinedText = implode("\n", $pendingMessages);

        Log::channel('api')->info('Processing batched messages', [
            'conversation_id' => $this->conversationId,
            'count' => count($pendingMessages),
            'text' => $combinedText,
        ]);

        $memoryLimit = (int) config('services.waba.memory_limit', 10);
        $history = $conversation->messages()
            ->orderBy('created_at', 'desc')
            ->take($memoryLimit)
            ->get()
            ->reverse()
            ->values();

        $conversation->messages()->create([
            'role' => 'user',
            'content' => $combinedText,
        ]);

        $responseAI = $anthropic->reply(
            $this->from,
            $combinedText,
            $history,
            $conversation->organization_id,
            $conversation->patient_id,
            $conversation,
        );

        Log::channel('api')->info("ANSWER: {$responseAI}");

        $wa->sendText($this->from, $responseAI);

        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $responseAI,
        ]);
    }
}
