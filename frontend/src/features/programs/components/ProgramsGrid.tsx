import { Link } from 'react-router-dom';
import { Badge } from '@/components/ui/Badge';
import type { Program, ProgramStatus } from '@/types/program';

const STATUS_COLOR: Record<ProgramStatus, 'gray' | 'yellow' | 'green'> = {
  draft: 'gray',
  submitted: 'yellow',
  approved: 'green',
};

export function ProgramsGrid({ programs }: { programs: Program[] }) {
  if (programs.length === 0) {
    return <p className="text-sm text-gray-500 dark:text-gray-400">No programs to show yet.</p>;
  }

  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      {programs.map((program) => (
        <div
          key={program.id}
          className="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
        >
          <div className="flex items-start justify-between">
            <div>
              <p className="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {program.code}
              </p>
              <h2 className="mt-0.5 font-semibold text-gray-900 dark:text-white">{program.name}</h2>
            </div>
            <Badge color={STATUS_COLOR[program.status]}>{program.status}</Badge>
          </div>

          <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">{program.department.name}</p>

          <p className="mt-3 text-sm text-gray-500 dark:text-gray-400">
            {program.objectives_count} objective{program.objectives_count === 1 ? '' : 's'} ·{' '}
            {program.learning_outcomes_count} outcome{program.learning_outcomes_count === 1 ? '' : 's'}
          </p>

          <div className="mt-4 flex gap-3 text-sm font-medium">
            <Link className="text-indigo-600 hover:underline dark:text-indigo-400" to={`/programs/${program.id}/objectives`}>
              Objectives
            </Link>
            <Link
              className="text-indigo-600 hover:underline dark:text-indigo-400"
              to={`/programs/${program.id}/learning-outcomes`}
            >
              Outcomes
            </Link>
            <Link className="text-indigo-600 hover:underline dark:text-indigo-400" to={`/programs/${program.id}/matrix`}>
              Matrix
            </Link>
          </div>
        </div>
      ))}
    </div>
  );
}
