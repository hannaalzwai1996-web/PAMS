import axios from 'axios';
import { authMeQueryKey, queryClient } from './queryClient';

/**
 * The backend origin (no /api/v1 suffix) — also used to hit
 * /sanctum/csrf-cookie, which lives outside the versioned API prefix.
 */
export const API_ORIGIN = (import.meta.env.VITE_API_URL as string | undefined) ?? 'http://localhost:8000';

/**
 * Every request in this app goes through this one client — ARCH-0001 §1:
 * Sanctum cookie-based SPA auth, never a bearer token stored in JS. All
 * feature `services/` modules build on top of this instance rather than
 * creating their own, so auth/CSRF handling is configured in exactly one
 * place.
 */
export const apiClient = axios.create({
  baseURL: `${API_ORIGIN}/api/v1`,
  withCredentials: true,
  // Axios's default XSRF handling only auto-attaches the header for
  // same-origin requests; the SPA (Vite dev server) and API run on
  // different origins/ports, so this opts in explicitly. Cookie/header
  // names default to XSRF-TOKEN / X-XSRF-TOKEN, which already match
  // Sanctum's — no further config needed.
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
  },
});

/**
 * A 401 from *any* request — not just the initial session check — means
 * the session ended server-side (expired, or an admin deactivated the
 * account, which revokes it immediately per FR-USR-03). Dropping the
 * cached user here is what makes the app react to that without a manual
 * refresh: everything reading `useAuth()` re-renders to the logged-out
 * state on the next tick.
 */
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (axios.isAxiosError(error) && error.response?.status === 401) {
      queryClient.setQueryData(authMeQueryKey, null);
    }

    return Promise.reject(error);
  },
);

/**
 * Must be called before the first stateful request (login) in a fresh
 * browser session — Sanctum issues the XSRF-TOKEN cookie here, which
 * apiClient then attaches to subsequent mutating requests
 * (ARCH-0001 §1/§3).
 */
export async function ensureCsrfCookie(): Promise<void> {
  await axios.get(`${API_ORIGIN}/sanctum/csrf-cookie`, { withCredentials: true });
}
