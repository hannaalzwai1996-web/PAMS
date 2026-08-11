import { Navigate, Outlet } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth';
import type { Role } from '@/types/user';

/**
 * A client-side convenience only — hiding a page a user isn't allowed on.
 * The actual authorization boundary is the backend Policy (ADR-0001 §12);
 * this never substitutes for it, and every request this page makes would
 * still be independently rejected server-side if reached directly.
 */
export function RequireRole({ roles }: { roles: Role[] }) {
  const { hasRole } = useAuth();
  const allowed = roles.some((role) => hasRole(role));

  if (!allowed) {
    return <Navigate to="/" replace />;
  }

  return <Outlet />;
}
