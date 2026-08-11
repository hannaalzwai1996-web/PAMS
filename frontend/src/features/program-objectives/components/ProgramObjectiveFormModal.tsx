import { useEffect, useState, type FormEvent } from 'react';
import { Modal } from '@/components/ui/Modal';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { FormField } from '@/components/ui/FormField';
import { useCreateProgramObjective, useUpdateProgramObjective } from '../hooks/useProgramObjectives';
import { toApiError } from '@/utils/apiError';
import type { ProgramObjective } from '@/types/program';

interface ProgramObjectiveFormModalProps {
  programId: string;
  /** null = closed, undefined = create mode, an objective = edit mode. */
  objective: ProgramObjective | null | undefined;
  onClose: () => void;
}

export function ProgramObjectiveFormModal({ programId, objective, onClose }: ProgramObjectiveFormModalProps) {
  const isOpen = objective !== null;
  const isEditing = Boolean(objective);
  const createObjective = useCreateProgramObjective(programId);
  const updateObjective = useUpdateProgramObjective(programId);
  const [code, setCode] = useState('');
  const [statement, setStatement] = useState('');

  useEffect(() => {
    setCode(objective?.code ?? '');
    setStatement(objective?.statement ?? '');
  }, [objective]);

  const mutation = isEditing ? updateObjective : createObjective;
  const error = mutation.error ? toApiError(mutation.error) : null;

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();

    try {
      if (objective) {
        await updateObjective.mutateAsync({ objectiveId: objective.id, payload: { code, statement } });
      } else {
        await createObjective.mutateAsync({ code, statement });
      }
      onClose();
    } catch {
      // Surfaced via `error` below.
    }
  }

  return (
    <Modal isOpen={isOpen} onClose={onClose} title={isEditing ? 'Edit objective' : 'Add objective'}>
      <form onSubmit={(event) => void handleSubmit(event)} className="space-y-4">
        <FormField label="Code" htmlFor="objective-code" error={error?.fieldError('code')} hint="e.g. PEO1">
          <Input id="objective-code" required value={code} onChange={(event) => setCode(event.target.value)} />
        </FormField>

        <FormField label="Statement" htmlFor="objective-statement" error={error?.fieldError('statement')}>
          <textarea
            id="objective-statement"
            required
            rows={3}
            value={statement}
            onChange={(event) => setStatement(event.target.value)}
            className="block w-full rounded-md border-0 px-3 py-1.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-800 dark:text-white dark:ring-gray-600"
          />
        </FormField>

        {error && !error.errors && <p className="text-sm text-red-600 dark:text-red-400">{error.message}</p>}

        <div className="flex justify-end gap-2">
          <Button type="button" variant="secondary" onClick={onClose}>
            Cancel
          </Button>
          <Button type="submit" isLoading={mutation.isPending}>
            Save
          </Button>
        </div>
      </form>
    </Modal>
  );
}
