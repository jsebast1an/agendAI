import AdminLayout from '@/Layouts/AdminLayout';
import ChatBubble from '@/Components/Admin/ChatBubble';
import StatusBadge from '@/Components/Admin/StatusBadge';
import { Link } from '@inertiajs/react';

export default function ConversationShow({ conversation, messages }) {
    return (
        <AdminLayout title="Conversacion">
            {/* Header */}
            <div className="flex items-center justify-between mb-6">
                <div className="flex items-center gap-3">
                    <Link
                        href={route('admin.conversations.index')}
                        className="text-gray-400 hover:text-gray-600 transition-colors"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <h3 className="font-medium text-gray-800">{conversation.patient}</h3>
                        <p className="text-xs text-gray-400">{conversation.phone}</p>
                    </div>
                    {conversation.handoff && <StatusBadge status="handoff" />}
                </div>
            </div>

            {/* Chat */}
            <div className="bg-cream-100 rounded-xl border border-cream-200 p-6 max-h-[calc(100vh-14rem)] overflow-y-auto">
                {messages.length === 0 && (
                    <p className="text-center text-gray-400 text-sm">Sin mensajes.</p>
                )}
                {messages.map((msg) => (
                    <ChatBubble
                        key={msg.id}
                        role={msg.role}
                        content={msg.content}
                        timestamp={msg.timestamp}
                    />
                ))}
            </div>
        </AdminLayout>
    );
}
