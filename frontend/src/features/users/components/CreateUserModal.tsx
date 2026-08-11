import { useState, type FormEvent } from 'react';
import { Modal } from '@/components/ui/Modal';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Select } from '@/components/ui/Select';
import { FormField } from '@/components/ui/FormField';
import { useCreateUser } from '../hooks/useUsers';
import { ROLE_OPTIONS } from '../roleOptions';
import { toApiError } from '@/utils/apiError';
import type { Role } from '@/types/user';

export function CreateUserModal({ isOpen, onClose }: { isOpen: boolean; onClose: () => void }) {
  const createUser = useCreateUser();
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [role, setRole] = useState<Role>('program_coordinator');

  const error = createUser.error ? toApiError(createUser.error) : null;

  function reset() {
    setName('');
    setEmail('');
    setPassword('');
    setRole('program_coordinator');
    createUser.reset();
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();

    try {
      await createUser.mutateAsync({ name, email, password, role });
      reset();
      onClose();
    } catch {
      // Field errors render inline below; nothing further to do here.
    }
  }

  return (
    <Modal
      isOpen={isOpen}
      onClose={() => {
        reset();
        onClose();
      }}
      title="Create user"
    >
      <form onSubmit={(event) => void handleSubmit(event)} className="space-y-4">
        <FormField label="Name" htmlFor="create-name" error={error?.fieldError('name')}>
          <Input id="create-name" required value={name} onChange={(event) => setName(event.target.value)} />
        </FormField>

        <FormField label="Email" htmlFor="create-email" error={error?.fieldError('email')}>
          <Input
            id="create-email"
            type="email"
            required
            value={email}
            onChange={(event) => setEmail(event.target.value)}
          />
        </FormField>

        <FormField label="Password" htmlFor="create-password" error={error?.fieldError('password')}>
          <Input
            id="create-password"
            type="password"
            required
            value={password}
            onChange={(event) => setPassword(event.target.value)}
          />
        </FormField>

        <FormField label="Role" htmlFor="create-role" error={error?.fieldError('role')}>
          <Select id="create-role" value={role} onChange={(event) => setRole(event.target.value as Role)}>
            {ROLE_OPTIONS.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </Select>
        </FormField>

        {error && !error.errors && <p className="text-sm text-red-600 dark:text-red-400">{error.message}</p>}

        <div className="flex justify-end gap-2">
          <Button type="button" variant="secondary" onClick={onClose}>
            Cancel
          </Button>
          <Button type="submit" isLoading={createUser.isPending}>
            Create
          </Button>
        </div>
      </form>
    </Modal>
  );
}
