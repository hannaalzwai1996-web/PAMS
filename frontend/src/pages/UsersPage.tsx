import { useState } from 'react';
import { useAuth } from '@/hooks/useAuth';
import { Button } from '@/components/ui/Button';
import { Spinner } from '@/components/ui/Spinner';
import { useUsers, useUpdateUser, useDeleteUser } from '@/features/users/hooks/useUsers';
import { UsersTable } from '@/features/users/components/UsersTable';
import { CreateUserModal } from '@/features/users/components/CreateUserModal';
import { EditUserModal } from '@/features/users/components/EditUserModal';
import type { User } from '@/types/user';

/**
 * Admin-only (routes/AppRouter.tsx wraps this in RequireRole(['admin'])).
 * "Registration" is admin-provisioned per ARCH-0001 §0 — there is no
 * public sign-up screen anywhere in this app, by design.
 */
export function UsersPage() {
  const { user: currentUser } = useAuth();
  const [page, setPage] = useState(1);
  const usersQuery = useUsers(page);
  const updateUser = useUpdateUser();
  const deleteUser = useDeleteUser();

  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [editingUser, setEditingUser] = useState<User | null>(null);

  function handleToggleActive(user: User) {
    void updateUser.mutateAsync({ userId: user.id, payload: { is_active: !user.is_active } });
  }

  function handleDelete(user: User) {
    if (window.confirm(`Delete ${user.name}? This cannot be undone.`)) {
      void deleteUser.mutateAsync(user.id);
    }
  }

  return (
    <div>
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold text-gray-900 dark:text-white">Users</h1>
        <Button onClick={() => setIsCreateOpen(true)}>Create user</Button>
      </div>

      <div className="mt-6">
        {usersQuery.isLoading ? (
          <div className="flex justify-center py-12">
            <Spinner />
          </div>
        ) : usersQuery.isError ? (
          <p className="text-sm text-red-600 dark:text-red-400">Failed to load users.</p>
        ) : (
          <>
            <UsersTable
              users={usersQuery.data?.data ?? []}
              currentUserId={currentUser?.id}
              onEdit={setEditingUser}
              onToggleActive={handleToggleActive}
              onDelete={handleDelete}
            />

            {usersQuery.data && usersQuery.data.meta.last_page > 1 && (
              <div className="mt-4 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                <span>
                  Page {usersQuery.data.meta.current_page} of {usersQuery.data.meta.last_page}
                </span>
                <div className="flex gap-2">
                  <Button
                    variant="secondary"
                    disabled={page <= 1}
                    onClick={() => setPage((current) => current - 1)}
                  >
                    Previous
                  </Button>
                  <Button
                    variant="secondary"
                    disabled={page >= usersQuery.data.meta.last_page}
                    onClick={() => setPage((current) => current + 1)}
                  >
                    Next
                  </Button>
                </div>
              </div>
            )}
          </>
        )}
      </div>

      <CreateUserModal isOpen={isCreateOpen} onClose={() => setIsCreateOpen(false)} />
      <EditUserModal user={editingUser} onClose={() => setEditingUser(null)} />
    </div>
  );
}
