import type { ReactNode } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { authService } from '@/features/auth/authService';
import { authMeQueryKey } from '@/services/queryClient';
import { toApiError } from '@/utils/apiError';
import { AuthContext, type AuthContextValue } from './authContextDefinition';

/**
 * The single source of truth for "who is logged in," backed by
 * GET /auth/me (React Query, not local component state) so every
 * component reading useAuth() sees the same session — including the
 * moment it ends (apiClient's 401 interceptor writes to this same query).
 */
export function AuthProvider({ children }: { children: ReactNode }) {
  const queryClient = useQueryClient();

  const meQuery = useQuery({
    queryKey: authMeQueryKey,
    queryFn: authService.me,
    // A 401 here means "not logged in," an expected outcome, not a
    // transient failure worth retrying.
    retry: false,
    staleTime: 5 * 60 * 1000,
  });

  const loginMutation = useMutation({
    mutationFn: authService.login,
    onSuccess: (user) => {
      queryClient.setQueryData(authMeQueryKey, user);
    },
  });

  const logoutMutation = useMutation({
    mutationFn: authService.logout,
    onSettled: () => {
      queryClient.setQueryData(authMeQueryKey, null);
      // Every other cached query may contain data scoped to this user's
      // permissions — none of it should survive into the next session.
      queryClient.clear();
    },
  });

  const user = meQuery.data ?? null;

  const value: AuthContextValue = {
    user,
    isLoading: meQuery.isLoading,
    isAuthenticated: user !== null,
    hasRole: (role) => user?.roles.includes(role) ?? false,
    login: async (credentials) => {
      await loginMutation.mutateAsync(credentials);
    },
    isLoggingIn: loginMutation.isPending,
    loginError: loginMutation.error ? toApiError(loginMutation.error).message : null,
    logout: async () => {
      await logoutMutation.mutateAsync();
    },
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
