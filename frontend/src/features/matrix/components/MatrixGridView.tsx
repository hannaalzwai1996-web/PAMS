import { cn } from '@/utils/cn';
import { Card } from '@/components/ui/Card';
import { EmptyState } from '@/components/ui/EmptyState';
import { GridIcon } from '@/components/icons';
import type { MatrixGrid } from '@/types/matrix';
import type { SelectedCell } from './MatrixCellModal';

const LEVEL_CLASSES: Record<number, string> = {
  1: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
  2: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
  3: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
};

const LEGEND: Array<{ level: number; label: string }> = [
  { level: 1, label: 'Low' },
  { level: 2, label: 'Medium' },
  { level: 3, label: 'High' },
];

export function MatrixGridView({ grid, onSelectCell }: { grid: MatrixGrid; onSelectCell: (cell: SelectedCell) => void }) {
  if (grid.objectives.length === 0 || grid.outcomes.length === 0) {
    return (
      <EmptyState
        icon={<GridIcon className="h-6 w-6" />}
        title="Not enough data to build a matrix"
        description="Add at least one Program Objective and one Learning Outcome before generating a matrix."
      />
    );
  }

  return (
    <Card className="overflow-hidden">
      <div className="overflow-x-auto">
        <table className="min-w-full border-collapse text-sm">
          <thead className="bg-gray-50 dark:bg-gray-800">
            <tr>
              <th className="sticky left-0 z-10 bg-gray-50 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                Objective \ Outcome
              </th>
              {grid.outcomes.map((outcome) => (
                <th
                  key={outcome.id}
                  className="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                >
                  {outcome.code}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-gray-900">
            {grid.rows.map((row) => (
              <tr key={row.objective.id}>
                <td className="sticky left-0 z-10 bg-white px-4 py-3 font-medium text-gray-900 dark:bg-gray-900 dark:text-white">
                  {row.objective.code}
                </td>
                {row.cells.map((cell) => (
                  <td key={cell.learning_outcome_id} className="px-2 py-2 text-center">
                    <button
                      type="button"
                      onClick={() =>
                        onSelectCell({
                          objectiveId: row.objective.id,
                          objectiveCode: row.objective.code,
                          outcomeId: cell.learning_outcome_id,
                          outcomeCode: cell.code,
                          correlationLevel: cell.correlation_level,
                        })
                      }
                      className={cn(
                        'mx-auto flex h-9 w-9 items-center justify-center rounded-md text-sm font-semibold transition-opacity hover:opacity-75',
                        cell.correlation_level
                          ? LEVEL_CLASSES[cell.correlation_level]
                          : 'bg-gray-50 text-gray-300 ring-1 ring-inset ring-gray-200 dark:bg-gray-800/50 dark:text-gray-600 dark:ring-gray-700',
                      )}
                      title={cell.source ? `Source: ${cell.source}` : 'Unmapped — click to set a correlation level'}
                    >
                      {cell.correlation_level ?? '–'}
                    </button>
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <div className="flex flex-wrap items-center gap-4 border-t border-gray-100 px-4 py-3 text-xs text-gray-500 dark:border-gray-800 dark:text-gray-400">
        <span className="font-medium text-gray-600 dark:text-gray-300">Correlation level:</span>
        {LEGEND.map((item) => (
          <span key={item.level} className="flex items-center gap-1.5">
            <span className={cn('flex h-5 w-5 items-center justify-center rounded text-[11px] font-semibold', LEVEL_CLASSES[item.level])}>
              {item.level}
            </span>
            {item.label}
          </span>
        ))}
      </div>
    </Card>
  );
}
