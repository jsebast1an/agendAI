import { Link } from '@inertiajs/react';

export default function SidebarLink({ href, active = false, icon, children }) {
    return (
        <Link
            href={href}
            className={`flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150 ${
                active
                    ? 'bg-sidebar-active text-sidebar-text-active'
                    : 'text-sidebar-text hover:bg-sidebar-hover hover:text-sidebar-text-active'
            }`}
        >
            <span className="w-5 h-5 flex-shrink-0">{icon}</span>
            <span>{children}</span>
        </Link>
    );
}
