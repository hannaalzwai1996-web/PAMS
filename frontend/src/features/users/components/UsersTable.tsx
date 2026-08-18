import { Table, TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '@/components/ui/Table';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import type { User } from '@/types/user';

const ROLE_COLOR: Record<string, 'brand' | 'blue' | 'gray'> = {
  admin: 'brand',
  qa_officer: 'gray',
  program_coordinator: 'blue',
};

function initials(name: string): string {
  const parts = name.trim().split(/\s+/);
  return ((parts[0]?.[0] ?? '') + (parts[parts.length - 1]?.[0] ?? '')).toUpperCase();
}

interface UsersTableProps {
  users: User[];
  currentUserId: string | undefined;
  onEdit: (user: User) => void;
  onToggleActive: (user: User) => void;
  onDelete: (user: User) => void;
}

export function UsersTable({ users, currentUserId, onEdit, onToggleActive, onDelete }: UsersTableProps) {
  return (
    <Table>
      <TableHead>
        <TableRow>
          <TableHeaderCell>Name</TableHeaderCell>
          <TableHeaderCell>Email</TableHeaderCell>
          <TableHeaderCell>Role</TableHeaderCell>
          <TableHeaderCell>Status</TableHeaderCell>
          <TableHeaderCell>Actions</TableHeaderCell>
        </TableRow>
      </TableHead>
      <TableBody>
        {users.map((user) => {
          const isSelf = user.id === currentUserId;

          return (
            <TableRow key={user.id}>
              <TableCell>
                <div className="flex items-center gap-3">
                  <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-700 dark:bg-gray-800 dark:text-gray-300">
                    {initials(user.name)}
                  </span>
                  <span className="font-medium text-brand-950 dark:text-white">{user.name}</span>
                </div>
              </TableCell>
              <TableCell>{user.email}</TableCell>
              <TableCell>
                <div className="flex flex-wrap gap-1">
                  {user.roles.map((role) => (
                    <Badge key={role} color={ROLE_COLOR[role] ?? 'gray'}>
                      {role.replace('_', ' ')}
                    </Badge>
                  ))}
                </div>
              </TableCell>
              <TableCell>
                <Badge color={user.is_active ? 'green' : 'red'}>{user.is_active ? 'Active' : 'Inactive'}</Badge>
              </TableCell>
              <TableCell>
                <div className="flex flex-wrap gap-1">
                  <Button variant="ghost" onClick={() => onEdit(user)}>
                    Edit
                  </Button>
                  <Button
                    variant="ghost"
                    disabled={isSelf && user.is_active}
                    title={isSelf && user.is_active ? "You can't deactivate your own account" : undefined}
                    onClick={() => onToggleActive(user)}
                  >
                    {user.is_active ? 'Deactivate' : 'Activate'}
                  </Button>
                  <Button
                    variant="ghost-danger"
                    disabled={isSelf}
                    title={isSelf ? "You can't delete your own account" : undefined}
                    onClick={() => onDelete(user)}
                  >
                    Delete
                  </Button>
                </div>
              </TableCell>
            </TableRow>
          );
        })}
      </TableBody>
    </Table>
  );
}
