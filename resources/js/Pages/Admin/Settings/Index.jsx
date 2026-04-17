import { useState, useEffect } from 'react';
import { router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import ProfessionalsTab from './ProfessionalsTab';
import ServicesTab from './ServicesTab';
import SchedulesTab from './SchedulesTab';

const TABS = [
    { key: 'professionals', label: 'Profesionales' },
    { key: 'services',      label: 'Servicios' },
    { key: 'schedules',     label: 'Horarios' },
];

export default function Settings({ professionals, services, tab }) {
    const { flash } = usePage().props;
    const [flashMsg, setFlashMsg] = useState(null);

    // Show flash message then auto-dismiss
    useEffect(() => {
        if (flash?.success || flash?.error) {
            setFlashMsg({ type: flash.success ? 'success' : 'error', text: flash.success || flash.error });
            const t = setTimeout(() => setFlashMsg(null), 3500);
            return () => clearTimeout(t);
        }
    }, [flash]);

    const switchTab = (newTab) => {
        router.get(route('admin.settings.index'), { tab: newTab }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout title="Configuración de Clínica">
            {/* Flash */}
            {flashMsg && (
                <div className={`mb-4 rounded-lg px-4 py-3 text-sm font-medium border ${
                    flashMsg.type === 'success'
                        ? 'bg-[var(--color-success-bg)] text-[var(--color-success-text)] border-[var(--color-success-border)]'
                        : 'bg-[var(--color-danger-bg)] text-[var(--color-danger-text)] border-[var(--color-danger-border)]'
                }`}>
                    {flashMsg.text}
                </div>
            )}

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

            {/* Tab content */}
            {tab === 'professionals' && (
                <ProfessionalsTab professionals={professionals} services={services} />
            )}
            {tab === 'services' && (
                <ServicesTab services={services} />
            )}
            {tab === 'schedules' && (
                <SchedulesTab professionals={professionals} />
            )}
        </AdminLayout>
    );
}
