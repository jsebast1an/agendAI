import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Admin/Pagination';
import StatusBadge from '@/Components/Admin/StatusBadge';
import { Link } from '@inertiajs/react';

export default function ConversationsIndex({ conversations }) {
    return (
        <AdminLayout title="Conversaciones">
            <div className="space-y-3">
                {conversations.data.length === 0 && (
                    <div className="bg-white rounded-xl border border-cream-200 shadow-sm p-8 text-center">
                        <p className="text-gray-400 text-sm">No hay conversaciones.</p>
                    </div>
                )}

                {conversations.data.map((conv) => (
                    <Link
                        key={conv.id}
                        href={route('admin.conversations.show', conv.id)}
                        className="block bg-white rounded-xl border border-cream-200 shadow-sm p-4 hover:border-sage-300 transition-colors"
                    >
                        <div className="flex items-start justify-between">
                            <div className="min-w-0 flex-1">
                                <div className="flex items-center gap-2 mb-1">
                                    <span className="font-medium text-gray-800 text-sm">
                                        {conv.patient}
                                    </span>
                                    <span className="text-xs text-gray-400">{conv.phone}</span>
                                    {conv.handoff && <StatusBadge status="handoff" />}
                                </div>
                                <p className="text-sm text-gray-500 truncate">
                                    {conv.last_message_role === 'assistant' && (
                                        <span className="text-sage-500">Bot: </span>
                                    )}
                                    {conv.last_message || 'Sin mensajes'}
                                </p>
                            </div>
                            <span className="text-xs text-gray-400 flex-shrink-0 ml-3">
                                {conv.updated_at}
                            </span>
                        </div>
                    </Link>
                ))}
            </div>
            <Pagination links={conversations.links} />
        </AdminLayout>
    );
}
