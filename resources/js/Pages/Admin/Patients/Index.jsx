import AdminLayout from '@/Layouts/AdminLayout';
import DataTable from '@/Components/Admin/DataTable';
import Pagination from '@/Components/Admin/Pagination';

const columns = [
    { key: 'name', label: 'Nombre' },
    { key: 'cedula', label: 'Cedula', render: (row) => row.cedula || '-' },
    { key: 'phone_number', label: 'Telefono' },
    {
        key: 'appointments_count',
        label: 'Citas',
        render: (row) => (
            <span className="inline-flex items-center justify-center w-7 h-7 rounded-full bg-sage-50 text-sage-700 text-xs font-medium">
                {row.appointments_count}
            </span>
        ),
    },
];

export default function PatientsIndex({ patients }) {
    return (
        <AdminLayout title="Pacientes">
            <DataTable
                columns={columns}
                data={patients.data}
                emptyMessage="No hay pacientes registrados."
            />
            <Pagination links={patients.links} />
        </AdminLayout>
    );
}
