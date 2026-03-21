import { Link } from '@inertiajs/react';

const decodeLabel = (label) =>
    label.replace('&laquo;', '«').replace('&raquo;', '»');

export default function Pagination({ links }) {
    if (!links || links.length <= 3) return null;

    return (
        <div className="flex items-center justify-center gap-1 mt-4">
            {links.map((link, i) => (
                <Link
                    key={i}
                    href={link.url || '#'}
                    className={`px-3 py-1.5 text-sm rounded-lg transition-colors ${
                        link.active
                            ? 'bg-sage-500 text-white font-medium'
                            : link.url
                                ? 'text-gray-600 hover:bg-cream-100'
                                : 'text-gray-300 cursor-default pointer-events-none'
                    }`}
                    preserveScroll
                    preserveState
                >
                    {decodeLabel(link.label)}
                </Link>
            ))}
        </div>
    );
}
