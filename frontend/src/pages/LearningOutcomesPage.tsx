import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { Button } from '@/components/ui/Button';
import { Spinner } from '@/components/ui/Spinner';
import { PageHeader } from '@/components/ui/PageHeader';
import {
  useLearningOutcomes,
  useDeleteLearningOutcome,
} from '@/features/learning-outcomes/hooks/useLearningOutcomes';
import { useProgramContext } from '@/features/programs/hooks/useProgramContext';
import { LearningOutcomesTable } from '@/features/learning-outcomes/components/LearningOutcomesTable';
import { LearningOutcomeFormModal } from '@/features/learning-outcomes/components/LearningOutcomeFormModal';
import { toApiError } from '@/utils/apiError';
import type { LearningOutcome } from '@/types/program';

export function LearningOutcomesPage() {
  const { programId = '' } = useParams<{ programId: string }>();
  const outcomesQuery = useLearningOutcomes(programId);
  const deleteOutcome = useDeleteLearningOutcome(programId);
  const program = useProgramContext(programId);

  const [modalState, setModalState] = useState<LearningOutcome | null | undefined>(null);

  if (!programId) return null;

  function handleDelete(outcome: LearningOutcome) {
    if (!window.confirm(`Delete learning outcome "${outcome.code}"?`)) return;

    deleteOutcome.mutate(outcome.id, {
      onError: (error) => window.alert(toApiError(error).message),
    });
  }

  return (
    <div>
      <PageHeader
        title="Program Learning Outcomes"
        description={program ? `${program.code} — ${program.name}` : undefined}
        backTo={{ to: '/', label: 'Back to Dashboard' }}
        actions={<Button onClick={() => setModalState(undefined)}>Add outcome</Button>}
      />

      <div className="mt-6">
        {outcomesQuery.isLoading ? (
          <div className="flex justify-center py-12">
            <Spinner />
          </div>
        ) : outcomesQuery.isError ? (
          <p className="text-sm text-red-600 dark:text-red-400">Failed to load learning outcomes.</p>
        ) : (
          <LearningOutcomesTable
            outcomes={outcomesQuery.data ?? []}
            onEdit={setModalState}
            onDelete={handleDelete}
            onAdd={() => setModalState(undefined)}
          />
        )}
      </div>

      <LearningOutcomeFormModal programId={programId} outcome={modalState} onClose={() => setModalState(null)} />
    </div>
  );
}
