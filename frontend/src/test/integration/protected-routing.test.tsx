import { describe, expect, it, vi, beforeEach } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import { Routes, Route } from 'react-router-dom';
import { renderWithProviders } from '@/test/renderWithProviders';
import { ProtectedRoute } from '@/routes/ProtectedRoute';
import { authService } from '@/features/auth/authService';
import { ApiError } from '@/utils/apiError';
import type { User } from '@/types/user';

vi.mock('@/features/auth/authService', () => ({
  authService: {
    login: vi.fn(),
    logout: vi.fn(),
    me: vi.fn(),
  },
}));

const mockedAuthService = vi.mocked(authService);

const coordinator: User = {
  id: 'user-2',
  name: 'Katherine Johnson',
  email: 'coordinator@pams.test',
  roles: ['program_coordinator'],
  direct_permissions: [],
  effective_permissions: [],
  is_active: true,
};

function TestApp() {
  return (
    <Routes>
      <Route path="/login" element={<div>Login page</div>} />
      <Route element={<ProtectedRoute />}>
        <Route path="/" element={<div>Protected dashboard content</div>} />
      </Route>
    </Routes>
  );
}

describe('ProtectedRoute', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('redirects to /login when there is no session', async () => {
    mockedAuthService.me.mockRejectedValue(new ApiError('Unauthenticated.', 401));

    renderWithProviders(<TestApp />, { route: '/' });

    expect(await screen.findByText('Login page')).toBeInTheDocument();
    expect(screen.queryByText('Protected dashboard content')).not.toBeInTheDocument();
  });

  it('renders the protected content when a session exists', async () => {
    mockedAuthService.me.mockResolvedValue(coordinator);

    renderWithProviders(<TestApp />, { route: '/' });

    expect(await screen.findByText('Protected dashboard content')).toBeInTheDocument();
    expect(screen.queryByText('Login page')).not.toBeInTheDocument();
  });

  it('shows a loading state before the session check resolves', async () => {
    let resolveSession: (user: User) => void = () => {};
    mockedAuthService.me.mockReturnValue(
      new Promise((resolve) => {
        resolveSession = resolve;
      }),
    );

    renderWithProviders(<TestApp />, { route: '/' });

    expect(screen.getByRole('status', { name: 'Loading' })).toBeInTheDocument();

    resolveSession(coordinator);
    await waitFor(() => expect(screen.getByText('Protected dashboard content')).toBeInTheDocument());
  });
});
