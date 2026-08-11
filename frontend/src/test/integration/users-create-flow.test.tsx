import { describe, expect, it, vi, beforeEach } from 'vitest';
import { screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { renderWithProviders } from '@/test/renderWithProviders';
import { UsersPage } from '@/pages/UsersPage';
import { authService } from '@/features/auth/authService';
import { usersService } from '@/features/users/usersService';
import type { User } from '@/types/user';
import type { PaginatedResponse } from '@/types/api';

vi.mock('@/features/auth/authService', () => ({
  authService: { login: vi.fn(), logout: vi.fn(), me: vi.fn() },
}));

vi.mock('@/features/users/usersService', () => ({
  usersService: {
    list: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    remove: vi.fn(),
    assignRole: vi.fn(),
    syncPermissions: vi.fn(),
  },
}));

const mockedAuthService = vi.mocked(authService);
const mockedUsersService = vi.mocked(usersService);

const admin: User = {
  id: 'admin-1',
  name: 'Grace Hopper',
  email: 'admin@pams.test',
  roles: ['admin'],
  direct_permissions: [],
  effective_permissions: ['users.manage'],
  is_active: true,
};

function emptyPage(): PaginatedResponse<User> {
  return {
    data: [],
    meta: { current_page: 1, last_page: 1, per_page: 15, total: 0 },
    links: { first: null, last: null, prev: null, next: null },
  };
}

function pageWith(users: User[]): PaginatedResponse<User> {
  return {
    data: users,
    meta: { current_page: 1, last_page: 1, per_page: 15, total: users.length },
    links: { first: null, last: null, prev: null, next: null },
  };
}

/**
 * Exercises UsersPage's full composition — useAuth (for the "don't let an
 * admin deactivate/delete themselves" UI hints), useUsers (React Query),
 * UsersTable, and CreateUserModal — together, with only the two service
 * modules mocked. This is the admin-provisioning flow ARCH-0001 §0
 * describes: there's no public registration route, so this form *is*
 * the only way a new PAMS account ever gets created.
 */
describe('users create flow', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockedAuthService.me.mockResolvedValue(admin);
  });

  it('creates a user through the form and the new user appears in the list', async () => {
    const newUser: User = {
      id: 'user-2',
      name: 'New Coordinator',
      email: 'coordinator@pams.test',
      roles: ['program_coordinator'],
      direct_permissions: [],
      effective_permissions: [],
      is_active: true,
    };

    mockedUsersService.list.mockResolvedValueOnce(emptyPage()).mockResolvedValueOnce(pageWith([newUser]));
    mockedUsersService.create.mockResolvedValue(newUser);

    const user = userEvent.setup();
    renderWithProviders(<UsersPage />, { route: '/admin/users' });

    await waitFor(() => expect(mockedUsersService.list).toHaveBeenCalledTimes(1));

    await user.click(screen.getByRole('button', { name: 'Create user' }));

    const dialog = screen.getByRole('heading', { name: 'Create user' }).closest('div')!;
    await user.type(within(dialog).getByLabelText('Name'), 'New Coordinator');
    await user.type(within(dialog).getByLabelText('Email'), 'coordinator@pams.test');
    await user.type(within(dialog).getByLabelText('Password'), 'a-strong-password');
    await user.click(within(dialog).getByRole('button', { name: 'Create' }));

    await waitFor(() => {
      expect(mockedUsersService.create.mock.calls[0]?.[0]).toEqual({
        name: 'New Coordinator',
        email: 'coordinator@pams.test',
        password: 'a-strong-password',
        role: 'program_coordinator',
      });
    });

    // Creating invalidates the list query, which refetches — the newly
    // created user should now be visible without a manual page reload.
    expect(await screen.findByText('New Coordinator')).toBeInTheDocument();
  });

  it('shows a field-level error when the backend rejects the submission', async () => {
    mockedUsersService.list.mockResolvedValue(emptyPage());

    const { ApiError } = await import('@/utils/apiError');
    mockedUsersService.create.mockRejectedValue(
      new ApiError('The given data was invalid.', 422, { email: ['The email has already been taken.'] }),
    );

    const user = userEvent.setup();
    renderWithProviders(<UsersPage />, { route: '/admin/users' });

    await waitFor(() => expect(mockedUsersService.list).toHaveBeenCalledTimes(1));
    await user.click(screen.getByRole('button', { name: 'Create user' }));

    const dialog = screen.getByRole('heading', { name: 'Create user' }).closest('div')!;
    await user.type(within(dialog).getByLabelText('Name'), 'Someone');
    await user.type(within(dialog).getByLabelText('Email'), 'taken@pams.test');
    await user.type(within(dialog).getByLabelText('Password'), 'a-strong-password');
    await user.click(within(dialog).getByRole('button', { name: 'Create' }));

    expect(await screen.findByText('The email has already been taken.')).toBeInTheDocument();
  });
});
