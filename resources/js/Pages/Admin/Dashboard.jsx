import AdminLayout from '@/Layouts/AdminLayout';
import MetricCard from '@/Components/Admin/MetricCard';
import DataTable from '@/Components/Admin/DataTable';
import StatusBadge from '@/Components/Admin/StatusBadge';

const CalendarIcon = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
        <rect x="3" y="4" width="18" height="18" rx="2" />
        <line x1="16" y1="2" x2="16" y2="6" />
        <line x1="8" y1="2" x2="8" y2="6" />
        <line x1="3" y1="10" x2="21" y2="10" />
    </svg>
);

const CheckIcon = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
        <polyline points="20,6 9,17 4,12" />
    </svg>
);

const XIcon = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
        <line x1="18" y1="6" x2="6" y2="18" />
        <line x1="6" y1="6" x2="18" y2="18" />
    </svg>
);

const UsersIcon = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" />
        <circle cx="9" cy="7" r="4" />
    </svg>
);

const ChatIcon = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z" />
    </svg>
);

const DollarIcon = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
        <line x1="12" y1="1" x2="12" y2="23" />
        <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" />
    </svg>
);

const CpuIcon = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
        <rect x="4" y="4" width="16" height="16" rx="2" />
        <rect x="9" y="9" width="6" height="6" />
        <line x1="9" y1="1" x2="9" y2="4" /><line x1="15" y1="1" x2="15" y2="4" />
        <line x1="9" y1="20" x2="9" y2="23" /><line x1="15" y1="20" x2="15" y2="23" />
        <line x1="20" y1="9" x2="23" y2="9" /><line x1="20" y1="15" x2="23" y2="15" />
        <line x1="1" y1="9" x2="4" y2="9" /><line x1="1" y1="15" x2="4" y2="15" />
    </svg>
);

const columns = [
    { key: 'patient', label: 'Paciente' },
    { key: 'service', label: 'Servicio' },
    { key: 'professional', label: 'Profesional' },
    { key: 'date', label: 'Fecha' },
    { key: 'time', label: 'Hora' },
    { key: 'status', label: 'Estado', render: (row) => <StatusBadge status={row.status} /> },
];

function formatCost(usd) {
    if (usd === 0) return '$0.00';
    if (usd < 0.01) return `$${usd.toFixed(4)}`;
    return `$${usd.toFixed(2)}`;
}

function formatTokens(n) {
    if (n >= 1_000_000) return `${(n / 1_000_000).toFixed(1)}M`;
    if (n >= 1_000) return `${(n / 1_000).toFixed(1)}K`;
    return String(n);
}

export default function Dashboard({ metrics, upcomingAppointments }) {
    return (
        <AdminLayout title="Dashboard">
            {/* Metric cards */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
                <MetricCard
                    title="Citas hoy"
                    value={metrics.appointmentsToday}
                    icon={<CalendarIcon />}
                    color="brand"
                />
                <MetricCard
                    title="Confirmadas"
                    value={metrics.confirmedTotal}
                    icon={<CheckIcon />}
                    color="success"
                />
                <MetricCard
                    title="Canceladas"
                    value={metrics.cancelledTotal}
                    icon={<XIcon />}
                    color="danger"
                />
                <MetricCard
                    title="Pacientes"
                    value={metrics.totalPatients}
                    icon={<UsersIcon />}
                    color="neutral"
                />
                <MetricCard
                    title="Conversaciones"
                    value={metrics.activeConversations}
                    icon={<ChatIcon />}
                    color="neutral"
                />
            </div>

            {/* AI cost metrics */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                <MetricCard
                    title="Costo hoy (IA)"
                    value={formatCost(metrics.costToday)}
                    icon={<DollarIcon />}
                    color="neutral"
                />
                <MetricCard
                    title="Costo este mes (IA)"
                    value={formatCost(metrics.costThisMonth)}
                    icon={<DollarIcon />}
                    color="neutral"
                />
                <MetricCard
                    title="Tokens hoy"
                    value={formatTokens(metrics.tokensTodayTotal)}
                    icon={<CpuIcon />}
                    color="neutral"
                />
            </div>

            {/* Upcoming appointments */}
            <div>
                <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                    Proximas citas
                </h3>
                <DataTable
                    columns={columns}
                    data={upcomingAppointments}
                    emptyMessage="No hay citas proximas."
                />
            </div>
        </AdminLayout>
    );
}
