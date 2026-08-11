import { describe, expect, it, vi, beforeEach } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { renderWithProviders } from '@/test/renderWithProviders';
import { LoginPage } from '@/pages/LoginPage';
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

const adminUser: User = {
  id: 'user-1',
  name: 'Grace Hopper',
  email: 'admin@pams.test',
  roles: ['admin'],
  direct_permissions: [],
  effective_permissions: ['users.manage'],
  is_active: true,
};

/**
 * Exercises the real AuthProvider + LoginForm + LoginPage together — only
 * the network boundary (authService) is mocked, per ARCH-0003 §7: the
 * "current user" lives in exactly one place (the /auth/me React Query
 * cache entry AuthContext wraps), so driving the UI through a real submit
 * and asserting on rendered state is what actually proves the wiring
 * works, not just that each piece works in isolation.
 */
describe('login flow', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    // Every render starts from "session check in flight, then not logged in" — matches a fresh page load.
    mockedAuthService.me.mockRejectedValue(new ApiError('Unauthenticated.', 401));
  });

  it('logs a user in and calls authService with the submitted credentials', async () => {
    mockedAuthService.login.mockResolvedValue(adminUser);
    const user = userEvent.setup();

    renderWithProviders(<LoginPage />, { route: '/login' });

    await waitFor(() => expect(screen.getByLabelText('Email')).toBeInTheDocument());

    await user.type(screen.getByLabelText('Email'), 'admin@pams.test');
    await user.type(screen.getByLabelText('Password'), 'a-strong-password');
    await user.click(screen.getByRole('button', { name: 'Sign in' }));

    await waitFor(() => {
      // React Query's mutationFn is invoked with a second (internal
      // context) argument the real authService.login ignores — matching
      // just the first argument is what actually matters here.
      expect(mockedAuthService.login.mock.calls[0]?.[0]).toEqual({
        email: 'admin@pams.test',
        password: 'a-strong-password',
      });
    });
  });

  it('shows the server error message when login fails', async () => {
    mockedAuthService.login.mockRejectedValue(new ApiError('These credentials do not match our records.', 422));
    const user = userEvent.setup();

    renderWithProviders(<LoginPage />, { route: '/login' });

    await waitFor(() => expect(screen.getByLabelText('Email')).toBeInTheDocument());

    await user.type(screen.getByLabelText('Email'), 'admin@pams.test');
    await user.type(screen.getByLabelText('Password'), 'wrong-password');
    await user.click(screen.getByRole('button', { name: 'Sign in' }));

    expect(await screen.findByRole('alert')).toHaveTextContent('These credentials do not match our records.');
  });
});
