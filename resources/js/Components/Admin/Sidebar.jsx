import { usePage } from '@inertiajs/react';
import SidebarLink from './SidebarLink';

const DashboardIcon = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
        <rect x="3" y="3" width="7" height="7" rx="1" />
        <rect x="14" y="3" width="7" height="7" rx="1" />
        <rect x="3" y="14" width="7" height="7" rx="1" />
        <rect x="14" y="14" width="7" height="7" rx="1" />
    </svg>
);

const CalendarIcon = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
        <rect x="3" y="4" width="18" height="18" rx="2" />
        <line x1="16" y1="2" x2="16" y2="6" />
        <line x1="8" y1="2" x2="8" y2="6" />
        <line x1="3" y1="10" x2="21" y2="10" />
    </svg>
);

const ChatIcon = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z" />
    </svg>
);

const UsersIcon = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" />
        <circle cx="9" cy="7" r="4" />
        <path d="M23 21v-2a4 4 0 00-3-3.87" />
        <path d="M16 3.13a4 4 0 010 7.75" />
    </svg>
);

const ClinicIcon = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
        <polyline points="9 22 9 12 15 12 15 22" />
    </svg>
);

const SettingsIcon = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
        <circle cx="12" cy="12" r="3" />
        <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z" />
    </svg>
);

const LogoutIcon = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4" />
        <polyline points="16,17 21,12 16,7" />
        <line x1="21" y1="12" x2="9" y2="12" />
    </svg>
);

const OrgsIcon = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
        <polyline points="9 22 9 12 15 12 15 22" />
    </svg>
);

const LogsIcon = () => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
        <rect x="2" y="3" width="20" height="14" rx="2" />
        <line x1="8" y1="21" x2="16" y2="21" />
        <line x1="12" y1="17" x2="12" y2="21" />
    </svg>
);

export default function Sidebar() {
    const { auth, url } = usePage().props;
    const currentPath = url ? url.split('?')[0] : '';
    const isSuperAdmin = auth.user?.role === 'superadmin';

    return (
        <aside className="w-64 bg-[var(--color-sidebar)] flex flex-col h-screen flex-shrink-0 border-r border-[var(--color-sidebar-border)]">
            {/* Logo */}
            <div className="px-5 py-6 border-b border-[var(--color-sidebar-border)]">
                <h1 className="text-lg font-bold tracking-tight">
                    <span className="brand-gradient-text">Agend</span>
                    <span className="text-[var(--color-sidebar-text-active)]">AI</span>
                </h1>
                {!isSuperAdmin && auth.organization && (
                    <p className="text-xs text-[var(--color-sidebar-text-muted)] mt-1 truncate">
                        {auth.organization.name}
                    </p>
                )}
                {isSuperAdmin && (
                    <p className="text-xs text-[var(--color-sidebar-text-muted)] mt-1">
                        Plataforma
                    </p>
                )}
            </div>

            {/* Nav */}
            <nav className="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
                {isSuperAdmin ? (
                    <>
                        <SidebarLink
                            href={route('platform.dashboard', { tab: 'orgs' })}
                            active={currentPath === '/platform' && !url.includes('tab=logs')}
                            icon={<OrgsIcon />}
                        >
                            Organizaciones
                        </SidebarLink>
                        <SidebarLink
                            href={route('platform.dashboard', { tab: 'logs' })}
                            active={currentPath === '/platform' && url.includes('tab=logs')}
                            icon={<LogsIcon />}
                        >
                            Logs
                        </SidebarLink>
                    </>
                ) : (
                    <>
                        <SidebarLink
                            href={route('admin.dashboard')}
                            active={currentPath === '/admin'}
                            icon={<DashboardIcon />}
                        >
                            Dashboard
                        </SidebarLink>
                        <SidebarLink
                            href={route('admin.settings.index')}
                            active={currentPath.startsWith('/admin/settings')}
                            icon={<ClinicIcon />}
                        >
                            Clínica
                        </SidebarLink>

                        <div className="pt-3 pb-1">
                            <p className="px-3 text-[10px] font-semibold uppercase tracking-widest text-[var(--color-sidebar-text-muted)]">
                                Actividad
                            </p>
                        </div>

                        <SidebarLink
                            href={route('admin.appointments.index')}
                            active={currentPath.startsWith('/admin/appointments')}
                            icon={<CalendarIcon />}
                        >
                            Citas
                        </SidebarLink>
                        <SidebarLink
                            href={route('admin.conversations.index')}
                            active={currentPath.startsWith('/admin/conversations')}
                            icon={<ChatIcon />}
                        >
                            Conversaciones
                        </SidebarLink>
                        <SidebarLink
                            href={route('admin.patients.index')}
                            active={currentPath.startsWith('/admin/patients')}
                            icon={<UsersIcon />}
                        >
                            Pacientes
                        </SidebarLink>
                    </>
                )}
            </nav>

            {/* Bottom nav */}
            <div className="px-3 py-4 border-t border-[var(--color-sidebar-border)] space-y-0.5">
                <SidebarLink
                    href={route('profile.edit')}
                    active={currentPath.startsWith('/profile')}
                    icon={<SettingsIcon />}
                >
                    Mi perfil
                </SidebarLink>
                <SidebarLink
                    href={route('logout')}
                    method="post"
                    as="button"
                    icon={<LogoutIcon />}
                >
                    Cerrar sesion
                </SidebarLink>
            </div>
        </aside>
    );
}
