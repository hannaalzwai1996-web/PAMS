import { NavLink } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth';
import { cn } from '@/utils/cn';
import { CheckBadgeIcon, HomeIcon, UsersIcon, XIcon } from '@/components/icons';

interface NavItem {
  to: string;
  label: string;
  icon: (props: { className?: string }) => React.ReactElement;
  end?: boolean;
}

const NAV_ITEMS: NavItem[] = [
  { to: '/', label: 'Dashboard', icon: HomeIcon, end: true },
  { to: '/admin/users', label: 'Users', icon: UsersIcon },
];

function Brand() {
  return (
    <div className="flex h-16 shrink-0 items-center gap-2.5 border-b border-gray-200 px-5 dark:border-gray-800">
      <span className="flex h-9 w-9 items-center justify-center rounded-md bg-brand-800 text-white">
        <CheckBadgeIcon className="h-5 w-5" />
      </span>
      <div className="leading-tight">
        <p className="text-sm font-bold tracking-wide text-brand-950 dark:text-white">PAMS</p>
        <p className="text-[11px] text-gray-500 dark:text-gray-400">Quality Assurance</p>
      </div>
    </div>
  );
}

function NavList({ onNavigate }: { onNavigate?: () => void }) {
  const { hasRole } = useAuth();

  return (
    <nav className="flex-1 space-y-1 px-3 py-4">
      <p className="px-2 pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
        Menu
      </p>
      {NAV_ITEMS.filter((item) => item.to !== '/admin/users' || hasRole('admin')).map((item) => (
        <NavLink
          key={item.to}
          to={item.to}
          end={item.end}
          onClick={onNavigate}
          className={({ isActive }) =>
            cn(
              'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
              isActive
                ? 'bg-brand-50 text-brand-800 dark:bg-brand-500/10 dark:text-brand-300'
                : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800',
            )
          }
        >
          <item.icon className="h-5 w-5 shrink-0" />
          {item.label}
        </NavLink>
      ))}
    </nav>
  );
}

/** Fixed, persistent on desktop (lg+); rendered as a slide-over drawer on smaller screens by AppLayout. */
export function Sidebar() {
  return (
    <aside className="hidden lg:fixed lg:inset-y-0 lg:z-30 lg:flex lg:w-64 lg:flex-col lg:border-r lg:border-gray-200 lg:bg-white dark:lg:border-gray-800 dark:lg:bg-gray-900">
      <Brand />
      <NavList />
    </aside>
  );
}

export function MobileSidebar({ isOpen, onClose }: { isOpen: boolean; onClose: () => void }) {
  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-40 lg:hidden">
      <button type="button" aria-label="Close navigation menu" className="fixed inset-0 bg-gray-900/60" onClick={onClose} />
      <div className="relative flex h-full w-72 max-w-[80%] flex-col bg-white shadow-md dark:bg-gray-900">
        <Brand />
        <button
          type="button"
          aria-label="Close menu"
          onClick={onClose}
          className="absolute right-3 top-4 rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300"
        >
          <XIcon className="h-5 w-5" />
        </button>
        <NavList onNavigate={onClose} />
      </div>
    </div>
  );
}
