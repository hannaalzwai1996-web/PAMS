import { useState } from 'react';
import { Outlet } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth';
import { Button } from '@/components/ui/Button';
import { Sidebar, MobileSidebar } from '@/components/layout/Sidebar';
import { CheckBadgeIcon, LogoutIcon, MenuIcon } from '@/components/icons';

const ROLE_LABELS: Record<string, string> = {
  admin: 'Administrator',
  qa_officer: 'Quality Assurance Officer',
  program_coordinator: 'Program Coordinator',
};

function initials(name: string): string {
  const parts = name.trim().split(/\s+/);
  return ((parts[0]?.[0] ?? '') + (parts[parts.length - 1]?.[0] ?? '')).toUpperCase();
}

/**
 * The shell every authenticated page renders inside (via ProtectedRoute's
 * nested <Outlet/>) — navigation visibility mirrors the backend's actual
 * authorization (admin-only links only render for admins), though the
 * real enforcement is always server-side (ADR-0001 §12): hiding a link
 * here is a UX nicety, not a security boundary.
 */
export function AppLayout() {
  const { user, logout } = useAuth();
  const [isMobileNavOpen, setIsMobileNavOpen] = useState(false);
  const roleLabel = user ? (ROLE_LABELS[user.roles[0]] ?? user.roles[0]) : '';

  return (
    <div className="min-h-screen bg-surface dark:bg-surface-dark">
      <Sidebar />
      <MobileSidebar isOpen={isMobileNavOpen} onClose={() => setIsMobileNavOpen(false)} />

      <div className="lg:pl-64">
        <header className="sticky top-0 z-20 border-b border-gray-200 bg-white/80 backdrop-blur dark:border-gray-800 dark:bg-gray-900/80">
          <div className="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <div className="flex items-center gap-3">
              <button
                type="button"
                aria-label="Open navigation menu"
                onClick={() => setIsMobileNavOpen(true)}
                className="-ml-1 rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200 lg:hidden"
              >
                <MenuIcon className="h-6 w-6" />
              </button>
              <span className="flex items-center gap-2 text-sm font-bold text-brand-950 dark:text-white lg:hidden">
                <CheckBadgeIcon className="h-5 w-5 text-brand-800" />
                PAMS
              </span>
            </div>

            <div className="flex items-center gap-3">
              <div className="hidden text-right sm:block">
                <p className="text-sm font-medium text-gray-900 dark:text-white">{user?.name}</p>
                <p className="text-xs text-gray-500 dark:text-gray-400">{roleLabel}</p>
              </div>
              <span className="flex h-9 w-9 items-center justify-center rounded-full bg-brand-800 text-sm font-semibold text-white">
                {user ? initials(user.name) : ''}
              </span>
              <Button variant="secondary" onClick={() => void logout()}>
                <LogoutIcon className="h-4 w-4" />
                <span className="hidden sm:inline">Log out</span>
              </Button>
            </div>
          </div>
        </header>

        <main className="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
          <div className="mx-auto max-w-6xl">
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  );
}
