import { describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { UsersTable } from './UsersTable';
import type { User } from '@/types/user';

function makeUser(overrides: Partial<User> = {}): User {
  return {
    id: 'user-1',
    name: 'Ada Lovelace',
    email: 'ada@example.com',
    roles: ['program_coordinator'],
    direct_permissions: [],
    effective_permissions: [],
    is_active: true,
    ...overrides,
  };
}

describe('UsersTable', () => {
  it('renders one row per user with their name, email, role, and status', () => {
    render(
      <UsersTable
        users={[makeUser()]}
        currentUserId="admin-1"
        onEdit={vi.fn()}
        onToggleActive={vi.fn()}
        onDelete={vi.fn()}
      />,
    );

    expect(screen.getByText('Ada Lovelace')).toBeInTheDocument();
    expect(screen.getByText('ada@example.com')).toBeInTheDocument();
    expect(screen.getByText('program coordinator')).toBeInTheDocument();
    expect(screen.getByText('Active')).toBeInTheDocument();
  });

  it('calls onEdit with the row user when Edit is clicked', async () => {
    const onEdit = vi.fn();
    const user = userEvent.setup();
    const target = makeUser();
    render(
      <UsersTable users={[target]} currentUserId="admin-1" onEdit={onEdit} onToggleActive={vi.fn()} onDelete={vi.fn()} />,
    );

    await user.click(screen.getByRole('button', { name: 'Edit' }));

    expect(onEdit).toHaveBeenCalledWith(target);
  });

  it('disables Deactivate and Delete for the currently signed-in user', () => {
    render(
      <UsersTable
        users={[makeUser({ id: 'admin-1' })]}
        currentUserId="admin-1"
        onEdit={vi.fn()}
        onToggleActive={vi.fn()}
        onDelete={vi.fn()}
      />,
    );

    expect(screen.getByRole('button', { name: 'Deactivate' })).toBeDisabled();
    expect(screen.getByRole('button', { name: 'Delete' })).toBeDisabled();
  });

  it('leaves Deactivate and Delete enabled for a different user', async () => {
    const onToggleActive = vi.fn();
    const onDelete = vi.fn();
    const user = userEvent.setup();
    const target = makeUser({ id: 'user-2' });
    render(
      <UsersTable
        users={[target]}
        currentUserId="admin-1"
        onEdit={vi.fn()}
        onToggleActive={onToggleActive}
        onDelete={onDelete}
      />,
    );

    await user.click(screen.getByRole('button', { name: 'Deactivate' }));
    await user.click(screen.getByRole('button', { name: 'Delete' }));

    expect(onToggleActive).toHaveBeenCalledWith(target);
    expect(onDelete).toHaveBeenCalledWith(target);
  });

  it('shows Activate instead of Deactivate for an inactive user', () => {
    render(
      <UsersTable
        users={[makeUser({ is_active: false })]}
        currentUserId="admin-1"
        onEdit={vi.fn()}
        onToggleActive={vi.fn()}
        onDelete={vi.fn()}
      />,
    );

    expect(screen.getByRole('button', { name: 'Activate' })).toBeInTheDocument();
    expect(screen.getByText('Inactive')).toBeInTheDocument();
  });
});
