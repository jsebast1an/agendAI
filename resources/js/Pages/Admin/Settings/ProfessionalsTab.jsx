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

export default function ProfessionalsTab({ professionals, services }) {
    const [drawer, setDrawer]           = useState({ open: false, record: null });
    const [confirmDeleteId, setConfirmDeleteId] = useState(null);

    const emptyForm = { name: '', specialty: '', active: true, services: [] };

    const { data, setData, post, put, errors, processing, reset } = useForm(emptyForm);

    const openCreate = () => {
        reset();
        setData(emptyForm);
        setDrawer({ open: true, record: null });
    };

    const openEdit = (record) => {
        setData({
            name:      record.name,
            specialty: record.specialty || '',
            active:    record.active,
            services:  record.services.map((s) => ({
                service_id:       s.service_id,
                duration_minutes: s.duration_minutes,
                price:            s.price ?? '',
            })),
        });
        setDrawer({ open: true, record });
    };

    const closeDrawer = () => {
        reset();
        setDrawer({ open: false, record: null });
    };

    const submit = (e) => {
        e.preventDefault();
        if (drawer.record) {
            put(route('admin.settings.professionals.update', drawer.record.id), { onSuccess: closeDrawer });
        } else {
            post(route('admin.settings.professionals.store'), { onSuccess: closeDrawer });
        }
    };

    const handleDelete = (id) => {
        router.delete(route('admin.settings.professionals.destroy', id), {
            onSuccess: () => setConfirmDeleteId(null),
        });
    };

    // Toggle a service in the services array
    const toggleService = (serviceId) => {
        const exists = data.services.find((s) => s.service_id === serviceId);
        if (exists) {
            setData('services', data.services.filter((s) => s.service_id !== serviceId));
        } else {
            setData('services', [...data.services, { service_id: serviceId, duration_minutes: 30, price: '' }]);
        }
    };

    const updateServicePivot = (serviceId, field, value) => {
        setData('services', data.services.map((s) =>
            s.service_id === serviceId ? { ...s, [field]: value } : s
        ));
    };

    const columns = [
        { key: 'name',      label: 'Nombre' },
        { key: 'specialty', label: 'Especialidad', render: (r) => (
            <span className="text-gray-500 text-xs">{r.specialty || '—'}</span>
        )},
        { key: 'services_count', label: 'Servicios', render: (r) => (
            <span className="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                {r.services.length}
            </span>
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
                <p className="text-sm text-gray-500">{professionals.length} profesional{professionals.length !== 1 ? 'es' : ''}</p>
                <button
                    onClick={openCreate}
                    className="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-white transition"
                    style={{ background: 'linear-gradient(to right, var(--color-brand-from), var(--color-brand-to))' }}
                >
                    <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Nuevo profesional
                </button>
            </div>

            <DataTable columns={columns} data={professionals} emptyMessage="No hay profesionales aún." />

            <DrawerPanel
                open={drawer.open}
                onClose={closeDrawer}
                title={drawer.record ? 'Editar profesional' : 'Nuevo profesional'}
                footer={
                    <>
                        <button type="button" onClick={closeDrawer} className="px-4 py-2 text-sm text-gray-500 hover:text-gray-700 transition">
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            form="professional-form"
                            disabled={processing}
                            className="px-4 py-2 text-sm font-medium text-white rounded-lg disabled:opacity-60 transition"
                            style={{ background: 'linear-gradient(to right, var(--color-brand-from), var(--color-brand-to))' }}
                        >
                            {processing ? 'Guardando...' : 'Guardar'}
                        </button>
                    </>
                }
            >
                <form id="professional-form" onSubmit={submit} className="space-y-5">
                    <div>
                        <InputLabel htmlFor="prof-name" value="Nombre *" />
                        <TextInput
                            id="prof-name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="mt-1 block w-full"
                        />
                        <InputError message={errors.name} className="mt-1" />
                    </div>

                    <div>
                        <InputLabel htmlFor="specialty" value="Especialidad" />
                        <TextInput
                            id="specialty"
                            value={data.specialty}
                            onChange={(e) => setData('specialty', e.target.value)}
                            className="mt-1 block w-full"
                        />
                        <InputError message={errors.specialty} className="mt-1" />
                    </div>

                    <div className="flex items-center gap-3">
                        <Toggle value={data.active} onChange={(v) => setData('active', v)} />
                        <span className="text-sm text-gray-600">{data.active ? 'Activo' : 'Inactivo'}</span>
                    </div>

                    {/* Services assignment */}
                    <div>
                        <p className="text-sm font-medium text-gray-700 mb-3">Servicios asignados</p>
                        {services.length === 0 ? (
                            <p className="text-xs text-gray-400">No hay servicios creados aún.</p>
                        ) : (
                            <div className="space-y-2">
                                {services.map((svc) => {
                                    const assigned = data.services.find((s) => s.service_id === svc.id);
                                    return (
                                        <div key={svc.id} className="rounded-lg border border-[var(--color-border)] p-3">
                                            <label className="flex items-center gap-2 cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    checked={!!assigned}
                                                    onChange={() => toggleService(svc.id)}
                                                    className="rounded border-gray-300 text-indigo-600"
                                                />
                                                <span className="text-sm font-medium text-gray-800">{svc.name}</span>
                                            </label>
                                            {assigned && (
                                                <div className="mt-2 grid grid-cols-2 gap-2 pl-6">
                                                    <div>
                                                        <label className="text-xs text-gray-500 block mb-0.5">Duración (min)</label>
                                                        <input
                                                            type="number"
                                                            min="5"
                                                            max="480"
                                                            value={assigned.duration_minutes}
                                                            onChange={(e) => updateServicePivot(svc.id, 'duration_minutes', parseInt(e.target.value) || 30)}
                                                            className="w-full rounded border-gray-300 text-sm py-1"
                                                        />
                                                    </div>
                                                    <div>
                                                        <label className="text-xs text-gray-500 block mb-0.5">Precio ($)</label>
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            step="0.01"
                                                            value={assigned.price}
                                                            onChange={(e) => updateServicePivot(svc.id, 'price', e.target.value)}
                                                            placeholder="Opcional"
                                                            className="w-full rounded border-gray-300 text-sm py-1"
                                                        />
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                        <InputError message={errors.services} className="mt-1" />
                    </div>
                </form>
            </DrawerPanel>
        </div>
    );
}
