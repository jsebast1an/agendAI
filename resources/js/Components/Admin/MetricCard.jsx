const colorMap = {
    sage: 'bg-sage-50 border-sage-200 text-sage-700',
    warm: 'bg-warm-50 border-warm-200 text-warm-400',
    gray: 'bg-gray-50 border-gray-200 text-gray-500',
    red: 'bg-red-50 border-red-200 text-red-500',
};

export default function MetricCard({ title, value, icon, color = 'sage' }) {
    const colors = colorMap[color] || colorMap.sage;

    return (
        <div className="bg-white rounded-xl border border-cream-200 p-5 shadow-sm">
            <div className="flex items-start justify-between">
                <div>
                    <p className="text-sm text-gray-500 font-medium">{title}</p>
                    <p className="text-2xl font-bold text-gray-800 mt-1">{value}</p>
                </div>
                <div className={`w-10 h-10 rounded-lg flex items-center justify-center border ${colors}`}>
                    <span className="w-5 h-5">{icon}</span>
                </div>
            </div>
        </div>
    );
}
