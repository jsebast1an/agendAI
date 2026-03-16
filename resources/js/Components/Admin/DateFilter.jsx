import { router } from '@inertiajs/react';

export default function DateFilter({ value, param = 'date', route: routeName }) {
    const handleChange = (e) => {
        router.get(route(routeName), { [param]: e.target.value }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <input
            type="date"
            value={value || ''}
            onChange={handleChange}
            className="rounded-lg border-cream-200 text-sm text-gray-700 focus:border-sage-400 focus:ring-sage-400"
        />
    );
}
