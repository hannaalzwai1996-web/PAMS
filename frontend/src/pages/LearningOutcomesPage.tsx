import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { Button } from '@/components/ui/Button';
import { Spinner } from '@/components/ui/Spinner';
import {
  useLearningOutcomes,
  useDeleteLearningOutcome,
} from '@/features/learning-outcomes/hooks/useLearningOutcomes';
import { LearningOutcomesTable } from '@/features/learning-outcomes/components/LearningOutcomesTable';
import { LearningOutcomeFormModal } from '@/features/learning-outcomes/components/LearningOutcomeFormModal';
import { toApiError } from '@/utils/apiError';
import type { LearningOutcome } from '@/types/program';

export function LearningOutcomesPage() {
  const { programId = '' } = useParams<{ programId: string }>();
  const outcomesQuery = useLearningOutcomes(programId);
  const deleteOutcome = useDeleteLearningOutcome(programId);

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
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold text-gray-900 dark:text-white">Program Learning Outcomes</h1>
        <Button onClick={() => setModalState(undefined)}>Add outcome</Button>
      </div>

      <div className="mt-6">
        {outcomesQuery.isLoading ? (
          <div className="flex justify-center py-12">
            <Spinner />
          </div>
        ) : outcomesQuery.isError ? (
          <p className="text-sm text-red-600 dark:text-red-400">Failed to load learning outcomes.</p>
        ) : (
          <LearningOutcomesTable outcomes={outcomesQuery.data ?? []} onEdit={setModalState} onDelete={handleDelete} />
        )}
      </div>

      <LearningOutcomeFormModal programId={programId} outcome={modalState} onClose={() => setModalState(null)} />
    </div>
  );
}
