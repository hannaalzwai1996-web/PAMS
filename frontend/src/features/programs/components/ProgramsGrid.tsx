import { Link } from 'react-router-dom';
import { Badge } from '@/components/ui/Badge';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { useAuth } from '@/hooks/useAuth';
import { BuildingIcon, ChevronRightIcon, TargetIcon } from '@/components/icons';
import type { Program, ProgramStatus } from '@/types/program';

const STATUS_COLOR: Record<ProgramStatus, 'gray' | 'yellow' | 'green'> = {
  draft: 'gray',
  submitted: 'yellow',
  approved: 'green',
};

const QUICK_LINKS = [
  { segment: 'objectives', label: 'Objectives' },
  { segment: 'learning-outcomes', label: 'Outcomes' },
  { segment: 'matrix', label: 'Matrix' },
] as const;

interface ProgramsGridProps {
  programs: Program[];
  onEdit?: (program: Program) => void;
  onDelete?: (program: Program) => void;
}

export function ProgramsGrid({ programs, onEdit, onDelete }: ProgramsGridProps) {
  const { hasRole } = useAuth();
  // A coordinator's program list is already server-scoped to only the
  // programs they're assigned to (ProgramService::list() ->
  // forCoordinator()), so "is this user a coordinator at all" is enough
  // to know they may attempt to edit any card they can see here — the
  // backend Policy (admin, or the assigned coordinator while draft) is
  // still the actual enforcement, this only decides what to show.
  const canEdit = hasRole('admin') || hasRole('program_coordinator');
  const canDelete = hasRole('admin');

  if (programs.length === 0) {
    return (
      <EmptyState
        icon={<BuildingIcon className="h-6 w-6" />}
        title="No programs to show yet"
        description="Programs will appear here once they're created and, for coordinators, once you're assigned to one."
      />
    );
  }

  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      {programs.map((program) => (
        <Card key={program.id} className="flex flex-col p-5 transition-colors hover:border-brand-200">
          <div className="flex items-start justify-between gap-2">
            <div className="flex items-start gap-3">
              <span className="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400">
                <TargetIcon className="h-5 w-5" />
              </span>
              <div>
                <p className="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                  {program.code}
                </p>
                <h3 className="mt-0.5 font-semibold leading-snug text-brand-950 dark:text-white">{program.name}</h3>
              </div>
            </div>
            <Badge color={STATUS_COLOR[program.status]}>{program.status}</Badge>
          </div>

          <p className="mt-3 text-sm text-gray-500 dark:text-gray-400">{program.department.name}</p>

          <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
            {program.objectives_count} objective{program.objectives_count === 1 ? '' : 's'} ·{' '}
            {program.learning_outcomes_count} outcome{program.learning_outcomes_count === 1 ? '' : 's'}
          </p>

          <div className="mt-4 flex flex-wrap gap-2 border-t border-gray-100 pt-4 dark:border-gray-800">
            {QUICK_LINKS.map((link) => (
              <Link
                key={link.segment}
                to={`/programs/${program.id}/${link.segment}`}
                state={{ programCode: program.code, programName: program.name }}
                className="inline-flex items-center gap-0.5 rounded-md bg-gray-50 px-2.5 py-1.5 text-sm font-medium text-gray-700 transition-colors hover:bg-brand-50 hover:text-brand-800 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-brand-500/10 dark:hover:text-brand-300"
              >
                {link.label}
                <ChevronRightIcon className="h-3.5 w-3.5" />
              </Link>
            ))}
          </div>

          {((canEdit && onEdit) || (canDelete && onDelete)) && (
            <div className="mt-2 flex gap-1">
              {canEdit && onEdit && (
                <Button variant="ghost" onClick={() => onEdit(program)}>
                  Edit
                </Button>
              )}
              {canDelete && onDelete && (
                <Button variant="ghost-danger" onClick={() => onDelete(program)}>
                  Delete
                </Button>
              )}
            </div>
          )}
        </Card>
      ))}
    </div>
  );
}
