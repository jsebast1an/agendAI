import { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import DataTable from '@/Components/Admin/DataTable';
import DrawerPanel from '@/Components/Admin/DrawerPanel';
import StatusBadge from '@/Components/Admin/StatusBadge';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import TextInput from '@/Components/TextInput';

function Toggle({ value, onChange }) {
    return (
        <button
            type="button"
            onClick={() => onChange(!value)}
            className={`relative inline-flex h-5 w-9 items-center rounded-full transition-colors ${
                value ? 'bg-[var(--color-brand-from)]' : 'bg-gray-200'
            }`}
        >
            <span className={`inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform ${
                value ? 'translate-x-4' : 'translate-x-0.5'
            }`} />
        </button>
    );
}

export default function ServicesTab({ services }) {
    const [drawer, setDrawer] = useState({ open: false, record: null });
    const [confirmDeleteId, setConfirmDeleteId] = useState(null);

    const { data, setData, post, put, errors, processing, reset } = useForm({
        name: '',
        description: '',
        active: true,
    });

    const openCreate = () => {
        reset();
        setData({ name: '', description: '', active: true });
        setDrawer({ open: true, record: null });
    };

    const openEdit = (record) => {
        setData({ name: record.name, description: record.description || '', active: record.active });
        setDrawer({ open: true, record });
    };

    const closeDrawer = () => {
        reset();
        setDrawer({ open: false, record: null });
    };

    const submit = (e) => {
        e.preventDefault();
        if (drawer.record) {
            put(route('admin.settings.services.update', drawer.record.id), { onSuccess: closeDrawer });
        } else {
            post(route('admin.settings.services.store'), { onSuccess: closeDrawer });
        }
    };

    const handleDelete = (id) => {
        router.delete(route('admin.settings.services.destroy', id), {
            onSuccess: () => setConfirmDeleteId(null),
        });
    };

    const columns = [
        { key: 'name',        label: 'Nombre' },
        { key: 'description', label: 'Descripción', render: (r) => (
            <span className="text-gray-500 text-xs truncate max-w-xs block">{r.description || '—'}</span>
        )},
        { key: 'active', label: 'Estado', render: (r) => (
            <StatusBadge status={r.active ? 'active' : 'inactive'} />
        )},
        { key: 'actions', label: '', render: (r) => (
            <div className="flex items-center gap-2 justify-end">
                {confirmDeleteId === r.id ? (
                    <>
                        <span className="text-xs text-gray-500">¿Eliminar?</span>
                        <button onClick={() => handleDelete(r.id)} className="text-xs font-medium text-red-600 hover:underline">Confirmar</button>
                        <button onClick={() => setConfirmDeleteId(null)} className="text-xs text-gray-400 hover:underline">Cancelar</button>
                    </>
                ) : (
                    <>
                        <button onClick={() => openEdit(r)} className="p-1.5 rounded hover:bg-gray-100 text-gray-400 hover:text-gray-700 transition">
                            <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" />
                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                            </svg>
                        </button>
                        <button onClick={() => setConfirmDeleteId(r.id)} className="p-1.5 rounded hover:bg-red-50 text-gray-400 hover:text-red-600 transition">
                            <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <polyline points="3 6 5 6 21 6" />
                                <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6" />
                                <path d="M10 11v6M14 11v6" />
                                <path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2" />
                            </svg>
                        </button>
                    </>
                )}
            </div>
        )},
    ];

    return (
        <div>
            <div className="flex items-center justify-between mb-4">
                <p className="text-sm text-gray-500">{services.length} servicio{services.length !== 1 ? 's' : ''}</p>
                <button
                    onClick={openCreate}
                    className="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-white transition"
                    style={{ background: 'linear-gradient(to right, var(--color-brand-from), var(--color-brand-to))' }}
                >
                    <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Nuevo servicio
                </button>
            </div>

            <DataTable columns={columns} data={services} emptyMessage="No hay servicios aún." />

            <DrawerPanel
                open={drawer.open}
                onClose={closeDrawer}
                title={drawer.record ? 'Editar servicio' : 'Nuevo servicio'}
                footer={
                    <>
                        <button type="button" onClick={closeDrawer} className="px-4 py-2 text-sm text-gray-500 hover:text-gray-700 transition">
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            form="service-form"
                            disabled={processing}
                            className="px-4 py-2 text-sm font-medium text-white rounded-lg disabled:opacity-60 transition"
                            style={{ background: 'linear-gradient(to right, var(--color-brand-from), var(--color-brand-to))' }}
                        >
                            {processing ? 'Guardando...' : 'Guardar'}
                        </button>
                    </>
                }
            >
                <form id="service-form" onSubmit={submit} className="space-y-4">
                    <div>
                        <InputLabel htmlFor="name" value="Nombre *" />
                        <TextInput
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="mt-1 block w-full"
                        />
                        <InputError message={errors.name} className="mt-1" />
                    </div>

                    <div>
                        <InputLabel htmlFor="description" value="Descripción" />
                        <textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            rows={3}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        />
                        <InputError message={errors.description} className="mt-1" />
                    </div>

                    <div className="flex items-center gap-3">
                        <Toggle value={data.active} onChange={(v) => setData('active', v)} />
                        <span className="text-sm text-gray-600">{data.active ? 'Activo' : 'Inactivo'}</span>
                    </div>
                </form>
            </DrawerPanel>
        </div>
    );
}
