import type { ReactElement, ReactNode } from 'react';
import { render } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { AuthProvider } from '@/contexts/AuthContext';

/**
 * Most feature components need Router + React Query context, and several
 * also need AuthContext (useAuth()) — this is the one place that wiring
 * is assembled for tests, so individual test files don't each hand-roll
 * a slightly different provider tree.
 *
 * A fresh QueryClient per call (never a shared singleton) is what stops
 * one test's cached server state from leaking into the next — retries
 * are disabled so a mocked rejection fails fast instead of retrying.
 */
export function createTestQueryClient(): QueryClient {
  return new QueryClient({
    defaultOptions: {
      queries: { retry: false },
      mutations: { retry: false },
    },
  });
}

interface RenderOptions {
  route?: string;
  queryClient?: QueryClient;
  withAuth?: boolean;
}

export function renderWithProviders(ui: ReactElement, options: RenderOptions = {}) {
  const queryClient = options.queryClient ?? createTestQueryClient();
  const withAuth = options.withAuth ?? true;

  function Wrapper({ children }: { children: ReactNode }) {
    const tree = withAuth ? <AuthProvider>{children}</AuthProvider> : children;

    return (
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={[options.route ?? '/']}>{tree}</MemoryRouter>
      </QueryClientProvider>
    );
  }

  return { queryClient, ...render(ui, { wrapper: Wrapper }) };
}
