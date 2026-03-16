import { router } from '@inertiajs/react';

export default function SelectFilter({ value, param, options, placeholder, route: routeName, currentFilters = {} }) {
    const handleChange = (e) => {
        const newFilters = { ...currentFilters, [param]: e.target.value || undefined };
        router.get(route(routeName), newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <select
            value={value || ''}
            onChange={handleChange}
            className="rounded-lg border-cream-200 text-sm text-gray-700 focus:border-sage-400 focus:ring-sage-400"
        >
            <option value="">{placeholder}</option>
            {options.map((opt) => (
                <option key={opt.value} value={opt.value}>
                    {opt.label}
                </option>
            ))}
        </select>
    );
}
