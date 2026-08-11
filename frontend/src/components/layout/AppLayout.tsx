import { NavLink, Outlet } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth';
import { Button } from '@/components/ui/Button';
import { cn } from '@/utils/cn';

const navLinkClasses = ({ isActive }: { isActive: boolean }) =>
  cn(
    'rounded-md px-3 py-2 text-sm font-medium',
    isActive
      ? 'bg-indigo-600 text-white'
      : 'text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800',
  );

/**
 * The shell every authenticated page renders inside (via ProtectedRoute's
 * nested <Outlet/>) — navigation visibility mirrors the backend's actual
 * authorization (admin-only links only render for admins), though the
 * real enforcement is always server-side (ADR-0001 §12): hiding a link
 * here is a UX nicety, not a security boundary.
 */
export function AppLayout() {
  const { user, hasRole, logout } = useAuth();

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
      <header className="border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-3">
          <div className="flex items-center gap-6">
            <span className="text-sm font-bold text-gray-900 dark:text-white">PAMS</span>
            <nav className="flex gap-1">
              <NavLink to="/" end className={navLinkClasses}>
                Dashboard
              </NavLink>
              {hasRole('admin') && (
                <NavLink to="/admin/users" className={navLinkClasses}>
                  Users
                </NavLink>
              )}
            </nav>
          </div>
          <div className="flex items-center gap-3">
            <span className="text-sm text-gray-500 dark:text-gray-400">{user?.name}</span>
            <Button variant="secondary" onClick={() => void logout()}>
              Log out
            </Button>
          </div>
        </div>
      </header>
      <main className="mx-auto max-w-7xl px-4 py-8">
        <Outlet />
      </main>
    </div>
  );
}
