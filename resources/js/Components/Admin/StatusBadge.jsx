const styles = {
    confirmed: 'bg-[var(--color-success-bg)] text-[var(--color-success-text)] border-[var(--color-success-border)]',
    cancelled: 'bg-[var(--color-danger-bg)] text-[var(--color-danger-text)] border-[var(--color-danger-border)]',
    active:    'bg-[var(--color-brand-100)] text-[var(--color-brand-700)] border-[var(--color-brand-200)]',
    handoff:   'bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] border-[var(--color-warning-border)]',
};

const labels = {
    confirmed: 'Confirmada',
    cancelled: 'Cancelada',
    active:    'Activa',
    handoff:   'Derivada',
};

export default function StatusBadge({ status }) {
    return (
        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border ${styles[status] || styles.active}`}>
            {labels[status] || status}
        </span>
    );
}
