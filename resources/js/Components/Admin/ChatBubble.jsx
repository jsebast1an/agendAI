export default function ChatBubble({ role, content, timestamp }) {
    const isUser = role === 'user';

    return (
        <div className={`flex ${isUser ? 'justify-end' : 'justify-start'} mb-3`}>
            <div className={`max-w-[75%] px-4 py-2.5 rounded-2xl text-sm leading-relaxed ${
                isUser
                    ? 'bg-sage-500 text-white rounded-br-md'
                    : 'bg-white border border-cream-200 text-gray-700 rounded-bl-md'
            }`}>
                <p className="whitespace-pre-wrap">{content}</p>
                {timestamp && (
                    <p className={`text-xs mt-1 ${isUser ? 'text-sage-200' : 'text-gray-400'}`}>
                        {timestamp}
                    </p>
                )}
            </div>
        </div>
    );
}
