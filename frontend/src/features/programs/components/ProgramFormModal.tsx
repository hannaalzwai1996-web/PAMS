import { useEffect, useState, type FormEvent } from 'react';
import { Modal } from '@/components/ui/Modal';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Select } from '@/components/ui/Select';
import { Textarea } from '@/components/ui/Textarea';
import { FormField } from '@/components/ui/FormField';
import { Spinner } from '@/components/ui/Spinner';
import { useAuth } from '@/hooks/useAuth';
import { useDepartments } from '@/features/departments/hooks/useDepartments';
import { useCreateProgram, useUpdateProgram, useProgram } from '../hooks/usePrograms';
import { ProgramCoordinatorsPanel } from './ProgramCoordinatorsPanel';
import { toApiError } from '@/utils/apiError';
import type { ProgramLevel, ProgramPayload } from '@/types/program';

const LEVEL_OPTIONS: Array<{ value: ProgramLevel; label: string }> = [
  { value: 'diploma', label: 'Diploma' },
  { value: 'bachelor', label: "Bachelor's" },
  { value: 'master', label: "Master's" },
  { value: 'phd', label: 'PhD' },
];

const emptyForm = { department_id: '', code: '', name: '', level: 'bachelor' as ProgramLevel, description: '', duration_years: '4' };

interface ProgramFormModalProps {
  /** null = closed, undefined = create mode, a program id = edit mode. */
  programId: string | null | undefined;
  onClose: () => void;
}

/**
 * Coordinator assignment (P0.2) lives inside this same modal rather than
 * a separate page — it's only meaningful once a Program exists, and an
 * admin editing a Program's metadata is exactly the moment they'd also
 * want to see/manage who's assigned to it.
 */
export function ProgramFormModal({ programId, onClose }: ProgramFormModalProps) {
  const { hasRole } = useAuth();
  const isOpen = programId !== null;
  const isEditing = typeof programId === 'string';

  const programQuery = useProgram(isEditing ? programId : '');
  const departmentsQuery = useDepartments();
  const createProgram = useCreateProgram();
  const updateProgram = useUpdateProgram(isEditing ? programId : '');

  const [form, setForm] = useState(emptyForm);

  useEffect(() => {
    const program = programQuery.data;

    if (isEditing && program) {
      setForm({
        department_id: String(program.department.id),
        code: program.code,
        name: program.name,
        level: program.level,
        description: program.description ?? '',
        duration_years: String(program.duration_years),
      });
    } else if (!isEditing) {
      setForm(emptyForm);
    }
  }, [isEditing, programQuery.data]);

  const mutation = isEditing ? updateProgram : createProgram;
  const error = mutation.error ? toApiError(mutation.error) : null;
  const isLoadingExisting = isEditing && programQuery.isLoading;

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();

    const payload: ProgramPayload = {
      department_id: Number(form.department_id),
      code: form.code,
      name: form.name,
      level: form.level,
      description: form.description,
      duration_years: Number(form.duration_years),
    };

    try {
      if (isEditing) {
        await updateProgram.mutateAsync(payload);
      } else {
        await createProgram.mutateAsync(payload);
      }
      onClose();
    } catch {
      // Surfaced via `error` below.
    }
  }

  function handleClose() {
    setForm(emptyForm);
    createProgram.reset();
    updateProgram.reset();
    onClose();
  }

  return (
    <Modal isOpen={isOpen} onClose={handleClose} title={isEditing ? 'Edit program' : 'Create program'}>
      {isLoadingExisting ? (
        <div className="flex justify-center py-8">
          <Spinner />
        </div>
      ) : (
        <>
          <form onSubmit={(event) => void handleSubmit(event)} className="space-y-4">
            <FormField label="Department" htmlFor="program-department" error={error?.fieldError('department_id')}>
              <Select
                id="program-department"
                required
                value={form.department_id}
                onChange={(event) => setForm((current) => ({ ...current, department_id: event.target.value }))}
              >
                <option value="" disabled>
                  {departmentsQuery.isLoading ? 'Loading departments…' : 'Select a department'}
                </option>
                {departmentsQuery.data?.map((department) => (
                  <option key={department.id} value={department.id}>
                    {department.name}
                  </option>
                ))}
              </Select>
            </FormField>

            <FormField label="Program Code" htmlFor="program-code" error={error?.fieldError('code')} hint="e.g. BSCS-2026">
              <Input
                id="program-code"
                required
                value={form.code}
                onChange={(event) => setForm((current) => ({ ...current, code: event.target.value }))}
              />
            </FormField>

            <FormField label="Program Name" htmlFor="program-name" error={error?.fieldError('name')}>
              <Input
                id="program-name"
                required
                value={form.name}
                onChange={(event) => setForm((current) => ({ ...current, name: event.target.value }))}
              />
            </FormField>

            <FormField label="Level" htmlFor="program-level" error={error?.fieldError('level')}>
              <Select
                id="program-level"
                value={form.level}
                onChange={(event) => setForm((current) => ({ ...current, level: event.target.value as ProgramLevel }))}
              >
                {LEVEL_OPTIONS.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </Select>
            </FormField>

            <FormField label="Description" htmlFor="program-description" error={error?.fieldError('description')}>
              <Textarea
                id="program-description"
                rows={3}
                value={form.description}
                onChange={(event) => setForm((current) => ({ ...current, description: event.target.value }))}
              />
            </FormField>

            <FormField
              label="Duration (years)"
              htmlFor="program-duration"
              error={error?.fieldError('duration_years')}
            >
              <Input
                id="program-duration"
                type="number"
                min={1}
                max={20}
                required
                value={form.duration_years}
                onChange={(event) => setForm((current) => ({ ...current, duration_years: event.target.value }))}
              />
            </FormField>

            {error && !error.errors && <p className="text-sm text-red-600 dark:text-red-400">{error.message}</p>}

            <div className="flex justify-end gap-2">
              <Button type="button" variant="secondary" onClick={handleClose}>
                Cancel
              </Button>
              <Button type="submit" isLoading={mutation.isPending}>
                {isEditing ? 'Save changes' : 'Create program'}
              </Button>
            </div>
          </form>

          {isEditing && hasRole('admin') && programQuery.data && (
            <div className="mt-6 border-t border-gray-200 pt-6 dark:border-gray-800">
              <ProgramCoordinatorsPanel programId={programId} coordinators={programQuery.data.coordinators ?? []} />
            </div>
          )}
        </>
      )}
    </Modal>
  );
}
