<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $orgId = $request->user()->organization_id;

        $conversations = Conversation::where('organization_id', $orgId)
            ->with(['patient:id,name', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->latest('updated_at')
            ->paginate(20)
            ->through(fn ($conv) => [
                'id' => $conv->id,
                'patient' => $conv->patient?->name ?? 'Sin nombre',
                'phone' => $conv->phone_number,
                'last_message' => $conv->messages->first()?->content ?? '',
                'last_message_role' => $conv->messages->first()?->role ?? '',
                'handoff' => $conv->handoff_to_human,
                'updated_at' => $conv->updated_at->setTimezone('America/Guayaquil')->format('d/m H:i'),
            ]);

        return Inertia::render('Admin/Conversations/Index', [
            'conversations' => $conversations,
        ]);
    }

    public function show(Request $request, Conversation $conversation)
    {
        $orgId = $request->user()->organization_id;

        if ($conversation->organization_id !== $orgId) {
            abort(403);
        }

        return Inertia::render('Admin/Conversations/Show', [
            'conversation' => [
                'id' => $conversation->id,
                'patient' => $conversation->patient?->name ?? 'Sin nombre',
                'phone' => $conversation->phone_number,
                'handoff' => $conversation->handoff_to_human,
            ],
            'messages' => $conversation->messages()
                ->orderBy('created_at')
                ->get()
                ->map(fn ($msg) => [
                    'id' => $msg->id,
                    'role' => $msg->role,
                    'content' => $msg->content,
                    'timestamp' => $msg->created_at->setTimezone('America/Guayaquil')->format('d/m H:i'),
                ]),
        ]);
    }
}
