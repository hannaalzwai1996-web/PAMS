import { useEffect, useState, type FormEvent } from 'react';
import { Modal } from '@/components/ui/Modal';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Select } from '@/components/ui/Select';
import { FormField } from '@/components/ui/FormField';
import { useAssignRole, useUpdateUser } from '../hooks/useUsers';
import { ROLE_OPTIONS } from '../roleOptions';
import { toApiError } from '@/utils/apiError';
import type { Role, User } from '@/types/user';

interface EditUserModalProps {
  user: User | null;
  onClose: () => void;
}

/**
 * Profile fields go through PATCH /admin/users/{id}; role goes through
 * POST /admin/users/{id}/role — two backend operations, submitted
 * together from one form for a simpler admin UX (same split the Filament
 * EditUser page uses, for the same reason).
 */
export function EditUserModal({ user, onClose }: EditUserModalProps) {
  const updateUser = useUpdateUser();
  const assignRole = useAssignRole();
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [isActive, setIsActive] = useState(true);
  const [role, setRole] = useState<Role>('program_coordinator');

  useEffect(() => {
    if (user) {
      setName(user.name);
      setEmail(user.email);
      setIsActive(user.is_active);
      setRole(user.roles[0] ?? 'program_coordinator');
    }
  }, [user]);

  const error = updateUser.error
    ? toApiError(updateUser.error)
    : assignRole.error
      ? toApiError(assignRole.error)
      : null;
  const isSubmitting = updateUser.isPending || assignRole.isPending;

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    if (!user) return;

    try {
      await updateUser.mutateAsync({ userId: user.id, payload: { name, email, is_active: isActive } });
      await assignRole.mutateAsync({ userId: user.id, role });
      onClose();
    } catch {
      // Surfaced via `error` above.
    }
  }

  return (
    <Modal isOpen={user !== null} onClose={onClose} title="Edit user">
      <form onSubmit={(event) => void handleSubmit(event)} className="space-y-4">
        <FormField label="Name" htmlFor="edit-name" error={error?.fieldError('name')}>
          <Input id="edit-name" required value={name} onChange={(event) => setName(event.target.value)} />
        </FormField>

        <FormField label="Email" htmlFor="edit-email" error={error?.fieldError('email')}>
          <Input
            id="edit-email"
            type="email"
            required
            value={email}
            onChange={(event) => setEmail(event.target.value)}
          />
        </FormField>

        <FormField label="Role" htmlFor="edit-role" error={error?.fieldError('role')}>
          <Select id="edit-role" value={role} onChange={(event) => setRole(event.target.value as Role)}>
            {ROLE_OPTIONS.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </Select>
        </FormField>

        <label className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
          <input
            type="checkbox"
            checked={isActive}
            onChange={(event) => setIsActive(event.target.checked)}
            className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
          />
          Active
        </label>

        {error && !error.errors && <p className="text-sm text-red-600 dark:text-red-400">{error.message}</p>}

        <div className="flex justify-end gap-2">
          <Button type="button" variant="secondary" onClick={onClose}>
            Cancel
          </Button>
          <Button type="submit" isLoading={isSubmitting}>
            Save
          </Button>
        </div>
      </form>
    </Modal>
  );
}
