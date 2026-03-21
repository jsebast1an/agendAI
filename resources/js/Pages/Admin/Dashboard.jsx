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

const columns = [
    { key: 'patient', label: 'Paciente' },
    { key: 'service', label: 'Servicio' },
    { key: 'professional', label: 'Profesional' },
    { key: 'date', label: 'Fecha' },
    { key: 'time', label: 'Hora' },
    { key: 'status', label: 'Estado', render: (row) => <StatusBadge status={row.status} /> },
];

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
