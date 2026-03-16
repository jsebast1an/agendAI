import AdminLayout from '@/Layouts/AdminLayout';
import DataTable from '@/Components/Admin/DataTable';
import StatusBadge from '@/Components/Admin/StatusBadge';
import Pagination from '@/Components/Admin/Pagination';
import { router } from '@inertiajs/react';

const columns = [
    { key: 'patient', label: 'Paciente' },
    { key: 'service', label: 'Servicio' },
    { key: 'professional', label: 'Profesional' },
    { key: 'date', label: 'Fecha' },
    { key: 'time', label: 'Horario' },
    { key: 'status', label: 'Estado', render: (row) => <StatusBadge status={row.status} /> },
];

export default function AppointmentsIndex({ appointments, professionals, filters }) {
    const updateFilter = (key, value) => {
        router.get(route('admin.appointments.index'), {
            ...filters,
            [key]: value || undefined,
        }, { preserveState: true, preserveScroll: true });
    };

    return (
        <AdminLayout title="Citas">
            {/* Filters */}
            <div className="flex flex-wrap gap-3 mb-6">
                <input
                    type="date"
                    value={filters.date || ''}
                    onChange={(e) => updateFilter('date', e.target.value)}
                    className="rounded-lg border-cream-200 text-sm text-gray-700 focus:border-sage-400 focus:ring-sage-400"
                />
                <select
                    value={filters.status || ''}
                    onChange={(e) => updateFilter('status', e.target.value)}
                    className="rounded-lg border-cream-200 text-sm text-gray-700 focus:border-sage-400 focus:ring-sage-400"
                >
                    <option value="">Todos los estados</option>
                    <option value="confirmed">Confirmadas</option>
                    <option value="cancelled">Canceladas</option>
                </select>
                <select
                    value={filters.professional_id || ''}
                    onChange={(e) => updateFilter('professional_id', e.target.value)}
                    className="rounded-lg border-cream-200 text-sm text-gray-700 focus:border-sage-400 focus:ring-sage-400"
                >
                    <option value="">Todos los profesionales</option>
                    {professionals.map((p) => (
                        <option key={p.id} value={p.id}>{p.name}</option>
                    ))}
                </select>
            </div>

            <DataTable
                columns={columns}
                data={appointments.data}
                emptyMessage="No hay citas con estos filtros."
            />
            <Pagination links={appointments.links} />
        </AdminLayout>
    );
}
