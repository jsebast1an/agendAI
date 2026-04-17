import { useState } from 'react';
import { router } from '@inertiajs/react';

const DAYS = [
    { value: 1, label: 'Lunes' },
    { value: 2, label: 'Martes' },
    { value: 3, label: 'Miércoles' },
    { value: 4, label: 'Jueves' },
    { value: 5, label: 'Viernes' },
    { value: 6, label: 'Sábado' },
    { value: 0, label: 'Domingo' },
];

function buildGrid(schedules) {
    const grid = {};
    DAYS.forEach(({ value }) => { grid[value] = null; });
    (schedules || []).forEach((sc) => {
        grid[sc.day_of_week] = {
            start: sc.start_time.slice(0, 5),
            end:   sc.end_time.slice(0, 5),
        };
    });
    return grid;
}

export default function SchedulesTab({ professionals }) {
    const [selectedId, setSelectedId] = useState(professionals[0]?.id ?? null);
    const [grids, setGrids]           = useState(() => {
        const g = {};
        professionals.forEach((p) => { g[p.id] = buildGrid(p.schedules); });
        return g;
    });
    const [saving, setSaving] = useState(false);
    const [errors, setErrors] = useState({});

    const selected = professionals.find((p) => p.id === selectedId);
    const grid     = selectedId ? (grids[selectedId] || buildGrid([])) : {};

    const toggleDay = (day) => {
        setGrids((prev) => ({
            ...prev,
            [selectedId]: {
                ...prev[selectedId],
                [day]: prev[selectedId][day] ? null : { start: '09:00', end: '18:00' },
            },
        }));
    };

    const updateTime = (day, field, value) => {
        setGrids((prev) => ({
            ...prev,
            [selectedId]: {
                ...prev[selectedId],
                [day]: { ...prev[selectedId][day], [field]: value },
            },
        }));
    };

    const save = () => {
        if (!selectedId) return;
        const schedules = DAYS
            .filter(({ value }) => grid[value] !== null)
            .map(({ value }) => ({
                day_of_week: value,
                start_time:  grid[value].start,
                end_time:    grid[value].end,
            }));

        setSaving(true);
        setErrors({});
        router.put(
            route('admin.settings.schedules.update', selectedId),
            { schedules },
            {
                onSuccess: () => setSaving(false),
                onError: (e) => { setErrors(e); setSaving(false); },
            }
        );
    };

    if (professionals.length === 0) {
        return <p className="text-sm text-gray-400">No hay profesionales creados aún.</p>;
    }

    return (
        <div className="flex flex-col md:flex-row gap-6">
            {/* Left — professional selector */}
            <div className="md:w-56 flex-shrink-0">
                <p className="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Profesional</p>
                <div className="flex md:flex-col gap-2 overflow-x-auto pb-2 md:pb-0">
                    {professionals.map((p) => (
                        <button
                            key={p.id}
                            onClick={() => setSelectedId(p.id)}
                            className={`flex-shrink-0 text-left px-3 py-2 rounded-lg text-sm font-medium transition ${
                                selectedId === p.id
                                    ? 'bg-[var(--color-brand-100)] text-[var(--color-brand-700)]'
                                    : 'text-gray-600 hover:bg-gray-100'
                            }`}
                        >
                            {p.name}
                            {!p.active && <span className="ml-1.5 text-[10px] text-gray-400">(inactivo)</span>}
                        </button>
                    ))}
                </div>
            </div>

            {/* Right — weekly grid */}
            {selected && (
                <div className="flex-1">
                    <div className="flex items-center justify-between mb-4">
                        <div>
                            <p className="font-semibold text-gray-800">{selected.name}</p>
                            <p className="text-xs text-gray-400">{selected.specialty || 'Sin especialidad'}</p>
                        </div>
                        <button
                            onClick={save}
                            disabled={saving}
                            className="px-4 py-2 text-sm font-medium text-white rounded-lg disabled:opacity-60 transition"
                            style={{ background: 'linear-gradient(to right, var(--color-brand-from), var(--color-brand-to))' }}
                        >
                            {saving ? 'Guardando...' : 'Guardar horarios'}
                        </button>
                    </div>

                    {errors.schedules && (
                        <p className="text-xs text-red-500 mb-3">{errors.schedules}</p>
                    )}

                    <div className="rounded-xl border border-[var(--color-border)] overflow-hidden">
                        {DAYS.map(({ value, label }, idx) => {
                            const active = grid[value] !== null;
                            return (
                                <div
                                    key={value}
                                    className={`flex items-center gap-4 px-4 py-3 ${
                                        idx < DAYS.length - 1 ? 'border-b border-[var(--color-border-soft)]' : ''
                                    } ${active ? 'bg-white' : 'bg-[var(--color-canvas)]'}`}
                                >
                                    {/* Toggle */}
                                    <button
                                        type="button"
                                        onClick={() => toggleDay(value)}
                                        className={`relative inline-flex h-5 w-9 flex-shrink-0 items-center rounded-full transition-colors ${
                                            active ? 'bg-[var(--color-brand-from)]' : 'bg-gray-200'
                                        }`}
                                    >
                                        <span className={`inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform ${
                                            active ? 'translate-x-4' : 'translate-x-0.5'
                                        }`} />
                                    </button>

                                    {/* Day name */}
                                    <span className={`w-24 text-sm font-medium ${active ? 'text-gray-800' : 'text-gray-400'}`}>
                                        {label}
                                    </span>

                                    {/* Time inputs */}
                                    {active ? (
                                        <div className="flex items-center gap-2 flex-1">
                                            <input
                                                type="time"
                                                value={grid[value]?.start || '09:00'}
                                                onChange={(e) => updateTime(value, 'start', e.target.value)}
                                                className="rounded border-gray-300 text-sm py-1 px-2 w-28"
                                            />
                                            <span className="text-gray-400 text-sm">—</span>
                                            <input
                                                type="time"
                                                value={grid[value]?.end || '18:00'}
                                                onChange={(e) => updateTime(value, 'end', e.target.value)}
                                                className="rounded border-gray-300 text-sm py-1 px-2 w-28"
                                            />
                                        </div>
                                    ) : (
                                        <span className="text-xs text-gray-400 flex-1">Día libre</span>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}
        </div>
    );
}
