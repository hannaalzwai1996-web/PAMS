import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/Button';
import { Select } from '@/components/ui/Select';
import { Badge } from '@/components/ui/Badge';
import { useProgramCoordinatorCandidates } from '@/features/users/hooks/useUsers';
import { useAssignCoordinator, useUnassignCoordinator } from '../hooks/usePrograms';
import { toApiError } from '@/utils/apiError';
import type { ProgramCoordinator } from '@/types/program';

interface ProgramCoordinatorsPanelProps {
  programId: string;
  coordinators: ProgramCoordinator[];
}

/**
 * Assign/unassign fire immediately (not bundled into the surrounding
 * form's Save) — a program can have more than one coordinator (the
 * schema is many-to-many, not one-to-one), so this is list management,
 * not a single value being swapped the way role assignment is.
 */
export function ProgramCoordinatorsPanel({ programId, coordinators }: ProgramCoordinatorsPanelProps) {
  const candidatesQuery = useProgramCoordinatorCandidates();
  const assignCoordinator = useAssignCoordinator(programId);
  const unassignCoordinator = useUnassignCoordinator(programId);
  const [selectedUserId, setSelectedUserId] = useState('');

  const assignedIds = useMemo(() => new Set(coordinators.map((c) => c.id)), [coordinators]);
  const availableCandidates = useMemo(
    () => (candidatesQuery.data?.data ?? []).filter((user) => !assignedIds.has(user.id)),
    [candidatesQuery.data, assignedIds],
  );

  const assignError = assignCoordinator.error ? toApiError(assignCoordinator.error) : null;
  const unassignError = unassignCoordinator.error ? toApiError(unassignCoordinator.error) : null;

  async function handleAssign() {
    if (!selectedUserId) return;

    try {
      await assignCoordinator.mutateAsync(selectedUserId);
      setSelectedUserId('');
    } catch {
      // Surfaced via assignError below.
    }
  }

  function handleUnassign(userId: string) {
    unassignCoordinator.mutate(userId);
  }

  return (
    <div>
      <h3 className="text-sm font-semibold text-brand-950 dark:text-white">Coordinators</h3>
      <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
        Program Coordinators assigned here can manage this program's objectives, learning outcomes, and PO–PLO
        matrix while it's in draft.
      </p>

      <div className="mt-3 space-y-2">
        {coordinators.length === 0 ? (
          <p className="text-sm text-gray-500 dark:text-gray-400">No coordinator assigned yet.</p>
        ) : (
          coordinators.map((coordinator) => (
            <div
              key={coordinator.id}
              className="flex items-center justify-between rounded-md border border-gray-200 px-3 py-2 dark:border-gray-800"
            >
              <div>
                <p className="text-sm font-medium text-gray-900 dark:text-white">{coordinator.name}</p>
                <p className="text-xs text-gray-500 dark:text-gray-400">{coordinator.email}</p>
              </div>
              <Button
                type="button"
                variant="ghost-danger"
                isLoading={unassignCoordinator.isPending && unassignCoordinator.variables === coordinator.id}
                onClick={() => handleUnassign(coordinator.id)}
              >
                Remove
              </Button>
            </div>
          ))
        )}
      </div>

      {unassignError && <p className="mt-2 text-sm text-red-600 dark:text-red-400">{unassignError.message}</p>}

      <div className="mt-4 flex items-end gap-2">
        <div className="flex-1">
          <label htmlFor="coordinator-candidate" className="block text-sm font-medium text-gray-900 dark:text-gray-100">
            Assign coordinator
          </label>
          <div className="mt-1">
            <Select
              id="coordinator-candidate"
              value={selectedUserId}
              onChange={(event) => setSelectedUserId(event.target.value)}
              disabled={candidatesQuery.isLoading || availableCandidates.length === 0}
            >
              <option value="">
                {candidatesQuery.isLoading
                  ? 'Loading Program Coordinators…'
                  : availableCandidates.length === 0
                    ? 'No available Program Coordinator accounts'
                    : 'Select a Program Coordinator'}
              </option>
              {availableCandidates.map((user) => (
                <option key={user.id} value={user.id}>
                  {user.name} ({user.email})
                </option>
              ))}
            </Select>
          </div>
        </div>
        <Button
          type="button"
          variant="secondary"
          disabled={!selectedUserId}
          isLoading={assignCoordinator.isPending}
          onClick={() => void handleAssign()}
        >
          Assign
        </Button>
      </div>
      {assignError && <p className="mt-2 text-sm text-red-600 dark:text-red-400">{assignError.message}</p>}
      {availableCandidates.length === 0 && !candidatesQuery.isLoading && (
        <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
          <Badge color="gray">Tip</Badge> Create a user with the Program Coordinator role first (Users page) to
          assign them here.
        </p>
      )}
    </div>
  );
}
