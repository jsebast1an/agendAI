import { Link } from '@inertiajs/react';

export default function SidebarLink({ href, active = false, icon, children, method, as }) {
    return (
        <Link
            href={href}
            method={method}
            as={as}
            className={`flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 w-full text-left ${
                active
                    ? 'bg-[var(--color-sidebar-active)] text-[var(--color-sidebar-text-active)] shadow-[inset_1px_0_0_var(--color-brand-from)]'
                    : 'text-[var(--color-sidebar-text)] hover:bg-[var(--color-sidebar-hover)] hover:text-[var(--color-sidebar-text-active)]'
            }`}
        >
            <span className={`w-4 h-4 flex-shrink-0 ${active ? 'text-[var(--color-brand-from)]' : ''}`}>
                {icon}
            </span>
            <span>{children}</span>
        </Link>
    );
}
