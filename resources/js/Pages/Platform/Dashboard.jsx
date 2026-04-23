import { useState, useEffect } from 'react';
import { router, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import MetricCard from '@/Components/Admin/MetricCard';
import DataTable from '@/Components/Admin/DataTable';
import DrawerPanel from '@/Components/Admin/DrawerPanel';
import StatusBadge from '@/Components/Admin/StatusBadge';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import TextInput from '@/Components/TextInput';

const TABS = [
    { key: 'orgs', label: 'Organizaciones' },
    { key: 'logs', label: 'Logs API' },
];

// ── Icons ────────────────────────────────────────────────────────────────────

const GridIcon = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
        <rect x="3" y="3" width="7" height="7" rx="1" /><rect x="14" y="3" width="7" height="7" rx="1" />
        <rect x="3" y="14" width="7" height="7" rx="1" /><rect x="14" y="14" width="7" height="7" rx="1" />
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
        <rect x="4" y="4" width="16" height="16" rx="2" /><rect x="9" y="9" width="6" height="6" />
        <line x1="9" y1="1" x2="9" y2="4" /><line x1="15" y1="1" x2="15" y2="4" />
        <line x1="9" y1="20" x2="9" y2="23" /><line x1="15" y1="20" x2="15" y2="23" />
        <line x1="20" y1="9" x2="23" y2="9" /><line x1="20" y1="15" x2="23" y2="15" />
        <line x1="1" y1="9" x2="4" y2="9" /><line x1="1" y1="15" x2="4" y2="15" />
    </svg>
);
const ChatIcon = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z" />
    </svg>
);

// ── Formatters ───────────────────────────────────────────────────────────────

function fmt(usd) {
    if (usd === 0) return '$0.00';
    if (usd < 0.01) return `$${usd.toFixed(4)}`;
    return `$${usd.toFixed(3)}`;
}
function fmtTokens(n) {
    if (n >= 1_000_000) return `${(n / 1_000_000).toFixed(1)}M`;
    if (n >= 1_000) return `${(n / 1_000).toFixed(1)}K`;
    return String(n);
}

// ── Organizations CRUD ───────────────────────────────────────────────────────

const TIMEZONES = [
    'America/Guayaquil',
    'America/Bogota',
    'America/Lima',
    'America/Santiago',
    'America/Buenos_Aires',
    'America/New_York',
    'America/Mexico_City',
];

const emptyOrg = {
    name: '',
    wa_phone_number: '',
    timezone: 'America/Guayaquil',
    cancellation_hours_min: 24,
    type: 'production',
};

function OrgsTab({ organizations }) {
    const [drawer, setDrawer] = useState({ open: false, record: null });
    const [confirmDeleteId, setConfirmDeleteId] = useState(null);
    const { data, setData, post, put, errors, processing, reset } = useForm(emptyOrg);

    const openCreate = () => {
        reset(); setData(emptyOrg);
        setDrawer({ open: true, record: null });
    };

    const openEdit = (record) => {
        setData({
            name: record.name,
            wa_phone_number: record.wa_number,
            timezone: record.timezone,
            cancellation_hours_min: record.cancellation_hours_min,
            type: record.type,
        });
        setDrawer({ open: true, record });
    };

    const closeDrawer = () => { reset(); setDrawer({ open: false, record: null }); };

    const submit = (e) => {
        e.preventDefault();
        if (drawer.record) {
            put(route('platform.organizations.update', drawer.record.id), { onSuccess: closeDrawer });
        } else {
            post(route('platform.organizations.store'), { onSuccess: closeDrawer });
        }
    };

    const handleDelete = (id) => {
        router.delete(route('platform.organizations.destroy', id), {
            onSuccess: () => setConfirmDeleteId(null),
        });
    };

    const columns = [
        { key: 'name', label: 'Nombre' },
        { key: 'wa_number', label: 'WhatsApp' },
        { key: 'timezone', label: 'Zona horaria', render: (r) => <span className="text-xs text-gray-500">{r.timezone}</span> },
        { key: 'conversations', label: 'Conversaciones' },
        { key: 'patients', label: 'Pacientes' },
        { key: 'cost_month', label: 'Costo (mes)', render: (r) => fmt(r.cost_month) },
        { key: 'type', label: 'Tipo', render: (r) => (
            <StatusBadge status={r.type === 'test' ? 'inactive' : 'active'} label={r.type} />
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
                <p className="text-sm text-gray-500">{organizations.length} organización{organizations.length !== 1 ? 'es' : ''}</p>
                <button
                    onClick={openCreate}
                    className="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-white transition"
                    style={{ background: 'linear-gradient(to right, var(--color-brand-from), var(--color-brand-to))' }}
                >
                    <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Nueva organización
                </button>
            </div>

            <DataTable columns={columns} data={organizations} emptyMessage="No hay organizaciones registradas." />

            <DrawerPanel
                open={drawer.open}
                onClose={closeDrawer}
                title={drawer.record ? 'Editar organización' : 'Nueva organización'}
                footer={
                    <>
                        <button type="button" onClick={closeDrawer} className="px-4 py-2 text-sm text-gray-500 hover:text-gray-700 transition">
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            form="org-form"
                            disabled={processing}
                            className="px-4 py-2 text-sm font-medium text-white rounded-lg disabled:opacity-60 transition"
                            style={{ background: 'linear-gradient(to right, var(--color-brand-from), var(--color-brand-to))' }}
                        >
                            {processing ? 'Guardando...' : 'Guardar'}
                        </button>
                    </>
                }
            >
                <form id="org-form" onSubmit={submit} className="space-y-5">
                    <div>
                        <InputLabel htmlFor="org-name" value="Nombre *" />
                        <TextInput id="org-name" value={data.name} onChange={(e) => setData('name', e.target.value)} className="mt-1 block w-full" />
                        <InputError message={errors.name} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel htmlFor="org-wa" value="WhatsApp Business Number *" />
                        <TextInput id="org-wa" value={data.wa_phone_number} onChange={(e) => setData('wa_phone_number', e.target.value)} className="mt-1 block w-full" placeholder="15550436116" />
                        <InputError message={errors.wa_phone_number} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel htmlFor="org-tz" value="Zona horaria *" />
                        <select
                            id="org-tz"
                            value={data.timezone}
                            onChange={(e) => setData('timezone', e.target.value)}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm"
                        >
                            {TIMEZONES.map((tz) => <option key={tz} value={tz}>{tz}</option>)}
                        </select>
                        <InputError message={errors.timezone} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel htmlFor="org-cancel" value="Horas mínimas para cancelar" />
                        <TextInput id="org-cancel" type="number" min="0" max="168" value={data.cancellation_hours_min} onChange={(e) => setData('cancellation_hours_min', parseInt(e.target.value) || 0)} className="mt-1 block w-full" />
                        <InputError message={errors.cancellation_hours_min} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel htmlFor="org-type" value="Tipo *" />
                        <select
                            id="org-type"
                            value={data.type}
                            onChange={(e) => setData('type', e.target.value)}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm"
                        >
                            <option value="production">Production</option>
                            <option value="test">Test</option>
                        </select>
                        <InputError message={errors.type} className="mt-1" />
                    </div>
                </form>
            </DrawerPanel>
        </div>
    );
}

// ── Logs Tab ─────────────────────────────────────────────────────────────────

const logColumns = [
    { key: 'org',         label: 'Org' },
    { key: 'model',       label: 'Modelo', render: (r) => <span className="text-xs font-mono">{r.model.replace('claude-', '')}</span> },
    { key: 'input',       label: 'Input',       render: (r) => fmtTokens(r.input) },
    { key: 'output',      label: 'Output',      render: (r) => fmtTokens(r.output) },
    { key: 'cache_write', label: 'Cache W',     render: (r) => fmtTokens(r.cache_write) },
    { key: 'cache_read',  label: 'Cache R',     render: (r) => fmtTokens(r.cache_read) },
    { key: 'cost_usd',    label: 'Costo',       render: (r) => fmt(r.cost_usd) },
    { key: 'created_at',  label: 'Fecha' },
];

// ── Main page ────────────────────────────────────────────────────────────────

export default function Dashboard({ tab, globalMetrics, organizations, apiLogs }) {
    const { flash } = usePage().props;
    const [flashMsg, setFlashMsg] = useState(null);

    useEffect(() => {
        if (flash?.success || flash?.error) {
            setFlashMsg({ type: flash.success ? 'success' : 'error', text: flash.success || flash.error });
            const t = setTimeout(() => setFlashMsg(null), 3500);
            return () => clearTimeout(t);
        }
    }, [flash]);

    const switchTab = (newTab) => {
        router.get(route('platform.dashboard'), { tab: newTab }, { preserveState: true, preserveScroll: true });
    };

    return (
        <AdminLayout title="Plataforma">
            {flashMsg && (
                <div className={`mb-4 rounded-lg px-4 py-3 text-sm font-medium border ${
                    flashMsg.type === 'success'
                        ? 'bg-[var(--color-success-bg)] text-[var(--color-success-text)] border-[var(--color-success-border)]'
                        : 'bg-[var(--color-danger-bg)] text-[var(--color-danger-text)] border-[var(--color-danger-border)]'
                }`}>
                    {flashMsg.text}
                </div>
            )}

            {/* Metrics strip */}
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <MetricCard title="Organizaciones"      value={globalMetrics.totalOrgs}                    icon={<GridIcon />}   color="brand" />
                <MetricCard title="Costo hoy"           value={fmt(globalMetrics.costToday)}               icon={<DollarIcon />} color="neutral" />
                <MetricCard title="Costo este mes"      value={fmt(globalMetrics.costThisMonth)}           icon={<DollarIcon />} color="neutral" />
                <MetricCard title="Tokens hoy"          value={fmtTokens(globalMetrics.tokensTodayTotal)}  icon={<CpuIcon />}    color="neutral" />
            </div>

            {/* Tab bar */}
            <div className="flex gap-1 mb-6 border-b border-[var(--color-border)]">
                {TABS.map((t) => (
                    <button
                        key={t.key}
                        onClick={() => switchTab(t.key)}
                        className={`px-4 py-2.5 text-sm font-medium transition border-b-2 -mb-px ${
                            tab === t.key
                                ? 'border-[var(--color-brand-from)] text-[var(--color-brand-700)]'
                                : 'border-transparent text-gray-500 hover:text-gray-700'
                        }`}
                    >
                        {t.label}
                    </button>
                ))}
            </div>

            {tab === 'orgs' && <OrgsTab organizations={organizations} />}

            {tab === 'logs' && (
                <div>
                    <p className="text-xs text-gray-400 mb-3">Últimos {apiLogs.length} registros</p>
                    <DataTable columns={logColumns} data={apiLogs} emptyMessage="Sin registros de API todavía." />
                </div>
            )}
        </AdminLayout>
    );
}
