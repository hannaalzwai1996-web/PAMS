import { useState, type FormEvent } from 'react';
import { Modal } from '@/components/ui/Modal';
import { Button } from '@/components/ui/Button';
import { Select } from '@/components/ui/Select';
import { FormField } from '@/components/ui/FormField';
import { useUpdateMatrix } from '../hooks/useMatrix';
import { toApiError } from '@/utils/apiError';

interface SelectedCell {
  objectiveId: string;
  objectiveCode: string;
  outcomeId: string;
  outcomeCode: string;
  correlationLevel: number | null;
}

const LEVEL_LABELS = ['', 'Low', 'Medium', 'High'];

/**
 * Only sets a level (1-3) — matches what PUT /matrix actually accepts
 * (BR-MTX-5). There is no "clear this one cell" capability on the
 * backend; a full re-generate or a per-outcome sync are the only ways to
 * remove a mapping, so this editor doesn't pretend otherwise.
 */
export function MatrixCellModal({
  programId,
  cell,
  onClose,
}: {
  programId: string;
  cell: SelectedCell | null;
  onClose: () => void;
}) {
  const updateMatrix = useUpdateMatrix(programId);
  const [level, setLevel] = useState<1 | 2 | 3>((cell?.correlationLevel as 1 | 2 | 3) || 1);

  const error = updateMatrix.error ? toApiError(updateMatrix.error) : null;

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    if (!cell) return;

    try {
      await updateMatrix.mutateAsync([
        { objective_id: cell.objectiveId, learning_outcome_id: cell.outcomeId, correlation_level: level },
      ]);
      onClose();
    } catch {
      // Surfaced via `error` below.
    }
  }

  return (
    <Modal isOpen={cell !== null} onClose={onClose} title={cell ? `${cell.objectiveCode} × ${cell.outcomeCode}` : ''}>
      <form onSubmit={(event) => void handleSubmit(event)} className="space-y-4">
        <FormField label="Correlation level" htmlFor="correlation-level">
          <Select
            id="correlation-level"
            value={level}
            onChange={(event) => setLevel(Number(event.target.value) as 1 | 2 | 3)}
          >
            <option value={1}>1 — {LEVEL_LABELS[1]}</option>
            <option value={2}>2 — {LEVEL_LABELS[2]}</option>
            <option value={3}>3 — {LEVEL_LABELS[3]}</option>
          </Select>
        </FormField>

        {error && <p className="text-sm text-red-600 dark:text-red-400">{error.message}</p>}

        <div className="flex justify-end gap-2">
          <Button type="button" variant="secondary" onClick={onClose}>
            Cancel
          </Button>
          <Button type="submit" isLoading={updateMatrix.isPending}>
            Save
          </Button>
        </div>
      </form>
    </Modal>
  );
}

export type { SelectedCell };
