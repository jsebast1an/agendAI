const colorMap = {
    brand:   'bg-[var(--color-brand-100)] border-[var(--color-brand-200)] text-[var(--color-brand-700)]',
    success: 'bg-[var(--color-success-bg)] border-[var(--color-success-border)] text-[var(--color-success-text)]',
    danger:  'bg-[var(--color-danger-bg)] border-[var(--color-danger-border)] text-[var(--color-danger-text)]',
    neutral: 'bg-gray-50 border-gray-200 text-gray-500',
};

export default function MetricCard({ title, value, icon, color = 'brand' }) {
    const colors = colorMap[color] || colorMap.brand;

    return (
        <div className="bg-[var(--color-surface)] rounded-xl border border-[var(--color-border)] p-5 shadow-sm">
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
