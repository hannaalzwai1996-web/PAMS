import { QueryClient } from '@tanstack/react-query';

/**
 * A singleton, not created inline in <App>, specifically so the axios
 * response interceptor (apiClient.ts) can reach it too — a 401 from any
 * request (not just the initial /auth/me check) needs to invalidate the
 * cached session and drop the user back to the login screen.
 */
export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      staleTime: 30_000,
      refetchOnWindowFocus: false,
    },
  },
});

export const authMeQueryKey = ['auth', 'me'] as const;
