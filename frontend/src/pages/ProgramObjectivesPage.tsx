import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { Button } from '@/components/ui/Button';
import { Spinner } from '@/components/ui/Spinner';
import { PageHeader } from '@/components/ui/PageHeader';
import {
  useProgramObjectives,
  useDeleteProgramObjective,
} from '@/features/program-objectives/hooks/useProgramObjectives';
import { useProgramContext } from '@/features/programs/hooks/useProgramContext';
import { ProgramObjectivesTable } from '@/features/program-objectives/components/ProgramObjectivesTable';
import { ProgramObjectiveFormModal } from '@/features/program-objectives/components/ProgramObjectiveFormModal';
import { toApiError } from '@/utils/apiError';
import type { ProgramObjective } from '@/types/program';

/**
 * Route: /programs/:programId/objectives, reached via the program cards
 * on the Dashboard (features/programs).
 */
export function ProgramObjectivesPage() {
  const { programId = '' } = useParams<{ programId: string }>();
  // Hooks must run unconditionally (Rules of Hooks) — the "no id" case is
  // handled by `enabled: programId !== ''` inside the hook, and the early
  // return below only affects what's rendered, not which hooks ran.
  const objectivesQuery = useProgramObjectives(programId);
  const deleteObjective = useDeleteProgramObjective(programId);
  const program = useProgramContext(programId);

  const [modalState, setModalState] = useState<ProgramObjective | null | undefined>(null);

  if (!programId) return null;

  function handleDelete(objective: ProgramObjective) {
    if (!window.confirm(`Delete objective "${objective.code}"?`)) return;

    deleteObjective.mutate(objective.id, {
      onError: (error) => window.alert(toApiError(error).message),
    });
  }

  return (
    <div>
      <PageHeader
        title="Program Objectives"
        description={program ? `${program.code} — ${program.name}` : undefined}
        backTo={{ to: '/', label: 'Back to Dashboard' }}
        actions={<Button onClick={() => setModalState(undefined)}>Add objective</Button>}
      />

      <div className="mt-6">
        {objectivesQuery.isLoading ? (
          <div className="flex justify-center py-12">
            <Spinner />
          </div>
        ) : objectivesQuery.isError ? (
          <p className="text-sm text-red-600 dark:text-red-400">Failed to load objectives.</p>
        ) : (
          <ProgramObjectivesTable
            objectives={objectivesQuery.data ?? []}
            onEdit={setModalState}
            onDelete={handleDelete}
            onAdd={() => setModalState(undefined)}
          />
        )}
      </div>

      <ProgramObjectiveFormModal programId={programId} objective={modalState} onClose={() => setModalState(null)} />
    </div>
  );
}
