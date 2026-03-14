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
    ) {}

    public function handle(AnthropicService $anthropic, WhatsappService $wa): void
    {
        // Only one job processes per conversation at a time
        $lock = Cache::lock("whatsapp.processing.{$this->conversationId}", 30);
        if (!$lock->get()) {
            return;
        }

        try {
            // Pull pending messages atomically — empty means another job already handled them
            $pendingMessages = Cache::pull("whatsapp.pending.{$this->conversationId}", []);
            if (empty($pendingMessages)) {
                return;
            }

            $conversation = Conversation::find($this->conversationId);
            if (!$conversation || $conversation->handoff_to_human) {
                return;
            }

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

            $conversation->messages()->create(['role' => 'user', 'content' => $combinedText]);

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

            $conversation->messages()->create(['role' => 'assistant', 'content' => $responseAI]);
        } finally {
            $lock->release();
        }
    }
}
