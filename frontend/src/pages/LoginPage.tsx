import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth';
import { LoginForm } from '@/features/auth/LoginForm';
import { CheckBadgeIcon } from '@/components/icons';

export function LoginPage() {
  const { isAuthenticated } = useAuth();
  const location = useLocation();
  const from = (location.state as { from?: Location })?.from?.pathname ?? '/';

  if (isAuthenticated) {
    return <Navigate to={from} replace />;
  }

  return (
    <div className="flex min-h-screen">
      {/* Branding side — hidden on small screens, keeps the form full-width there. */}
      <div className="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-indigo-700 p-12 text-white lg:flex">
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(255,255,255,0.12),_transparent_60%)]" />
        <div className="relative flex items-center gap-3">
          <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15">
            <CheckBadgeIcon className="h-6 w-6" />
          </span>
          <span className="text-lg font-bold tracking-wide">PAMS</span>
        </div>
        <div className="relative">
          <h2 className="text-3xl font-semibold leading-snug">
            Academic Program Specification &amp; Quality Assurance Management
          </h2>
          <p className="mt-4 max-w-md text-sm text-indigo-100">
            Manage program objectives, learning outcomes, and PO–PLO alignment in one place — built for accreditation
            and continuous quality improvement.
          </p>
        </div>
        <p className="relative text-xs text-indigo-200">© {new Date().getFullYear()} PAMS. All rights reserved.</p>
      </div>

      <div className="flex w-full flex-1 items-center justify-center bg-gray-50 px-4 dark:bg-gray-950">
        <div className="w-full max-w-sm">
          <div className="mb-8 flex flex-col items-center gap-3 lg:hidden">
            <span className="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600 text-white">
              <CheckBadgeIcon className="h-6 w-6" />
            </span>
            <div className="text-center">
              <h1 className="text-lg font-bold text-gray-900 dark:text-white">PAMS</h1>
              <p className="text-sm text-gray-500 dark:text-gray-400">Academic Program Specification &amp; QA</p>
            </div>
          </div>

          <div className="rounded-xl bg-white p-8 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Sign in to your account</h2>
            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">Enter your credentials to continue.</p>
            <div className="mt-6">
              <LoginForm />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
