import { useEffect, useState, type FormEvent } from 'react';
import { Modal } from '@/components/ui/Modal';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Select } from '@/components/ui/Select';
import { FormField } from '@/components/ui/FormField';
import { useCreateLearningOutcome, useUpdateLearningOutcome } from '../hooks/useLearningOutcomes';
import { toApiError } from '@/utils/apiError';
import type { LearningOutcome, LearningOutcomeCategory } from '@/types/program';

const CATEGORY_OPTIONS: Array<{ value: LearningOutcomeCategory; label: string }> = [
  { value: 'A', label: 'A — Knowledge' },
  { value: 'B', label: 'B — Cognitive Skills' },
  { value: 'C', label: 'C — Practical Skills' },
  { value: 'D', label: 'D — General Skills' },
];

interface LearningOutcomeFormModalProps {
  programId: string;
  outcome: LearningOutcome | null | undefined;
  onClose: () => void;
}

export function LearningOutcomeFormModal({ programId, outcome, onClose }: LearningOutcomeFormModalProps) {
  const isOpen = outcome !== null;
  const isEditing = Boolean(outcome);
  const createOutcome = useCreateLearningOutcome(programId);
  const updateOutcome = useUpdateLearningOutcome(programId);
  const [code, setCode] = useState('');
  const [statement, setStatement] = useState('');
  const [category, setCategory] = useState<LearningOutcomeCategory>('A');

  useEffect(() => {
    setCode(outcome?.code ?? '');
    setStatement(outcome?.statement ?? '');
    setCategory(outcome?.category ?? 'A');
  }, [outcome]);

  const mutation = isEditing ? updateOutcome : createOutcome;
  const error = mutation.error ? toApiError(mutation.error) : null;

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();

    try {
      if (outcome) {
        await updateOutcome.mutateAsync({ outcomeId: outcome.id, payload: { code, statement, category } });
      } else {
        await createOutcome.mutateAsync({ code, statement, category });
      }
      onClose();
    } catch {
      // Surfaced via `error` below.
    }
  }

  return (
    <Modal isOpen={isOpen} onClose={onClose} title={isEditing ? 'Edit learning outcome' : 'Add learning outcome'}>
      <form onSubmit={(event) => void handleSubmit(event)} className="space-y-4">
        <FormField label="Code" htmlFor="outcome-code" error={error?.fieldError('code')} hint="e.g. PLO1">
          <Input id="outcome-code" required value={code} onChange={(event) => setCode(event.target.value)} />
        </FormField>

        <FormField label="Statement" htmlFor="outcome-statement" error={error?.fieldError('statement')}>
          <textarea
            id="outcome-statement"
            required
            rows={3}
            value={statement}
            onChange={(event) => setStatement(event.target.value)}
            className="block w-full rounded-md border-0 px-3 py-1.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-800 dark:text-white dark:ring-gray-600"
          />
        </FormField>

        <FormField label="Category" htmlFor="outcome-category" error={error?.fieldError('category')}>
          <Select
            id="outcome-category"
            value={category}
            onChange={(event) => setCategory(event.target.value as LearningOutcomeCategory)}
          >
            {CATEGORY_OPTIONS.map((option) => (
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
          <Button type="submit" isLoading={mutation.isPending}>
            Save
          </Button>
        </div>
      </form>
    </Modal>
  );
}
