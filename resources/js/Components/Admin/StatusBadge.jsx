const styles = {
    confirmed: 'bg-sage-50 text-sage-700 border-sage-200',
    cancelled: 'bg-red-50 text-red-700 border-red-200',
    active: 'bg-sage-50 text-sage-700 border-sage-200',
    handoff: 'bg-warm-50 text-warm-400 border-warm-200',
};

const labels = {
    confirmed: 'Confirmada',
    cancelled: 'Cancelada',
    active: 'Activa',
    handoff: 'Derivada',
};

export default function StatusBadge({ status }) {
    return (
        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border ${styles[status] || styles.confirmed}`}>
            {labels[status] || status}
        </span>
    );
}
