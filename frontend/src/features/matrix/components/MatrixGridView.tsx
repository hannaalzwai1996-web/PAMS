import { cn } from '@/utils/cn';
import { Card } from '@/components/ui/Card';
import { EmptyState } from '@/components/ui/EmptyState';
import { GridIcon } from '@/components/icons';
import type { MatrixGrid } from '@/types/matrix';
import type { SelectedCell } from './MatrixCellModal';

/** A single-hue intensity scale (rather than traffic-light amber/emerald) — reads as an accreditation heatmap, not a status indicator. */
const LEVEL_CLASSES: Record<number, string> = {
  1: 'bg-brand-50 text-brand-800 dark:bg-brand-900/30 dark:text-brand-200',
  2: 'bg-brand-200 text-brand-900 dark:bg-brand-700/60 dark:text-brand-100',
  3: 'bg-brand-700 text-white dark:bg-brand-500',
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
    <Card className="overflow-hidden p-0">
      <div className="max-h-[32rem] overflow-auto">
        <table className="w-full border-separate border-spacing-0 text-sm">
          <thead>
            <tr>
              <th className="sticky left-0 top-0 z-20 border-b border-r border-gray-200 bg-brand-50 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-brand-900 dark:border-gray-700 dark:bg-brand-900/40 dark:text-brand-200">
                Objective \ Outcome
              </th>
              {grid.outcomes.map((outcome) => (
                <th
                  key={outcome.id}
                  scope="col"
                  className="sticky top-0 z-10 whitespace-nowrap border-b border-r border-gray-200 bg-brand-50 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-brand-900 last:border-r-0 dark:border-gray-700 dark:bg-brand-900/40 dark:text-brand-200"
                >
                  {outcome.code}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className="bg-white dark:bg-gray-900">
            {grid.rows.map((row, rowIndex) => (
              <tr key={row.objective.id}>
                <th
                  scope="row"
                  className={cn(
                    'sticky left-0 z-10 whitespace-nowrap border-r border-gray-200 bg-white px-4 py-2.5 text-left text-sm font-semibold text-brand-950 dark:border-gray-700 dark:bg-gray-900 dark:text-white',
                    rowIndex !== grid.rows.length - 1 && 'border-b',
                  )}
                >
                  {row.objective.code}
                </th>
                {row.cells.map((cell) => (
                  <td
                    key={cell.learning_outcome_id}
                    className={cn(
                      'border-r border-gray-200 p-1.5 text-center last:border-r-0 dark:border-gray-700',
                      rowIndex !== grid.rows.length - 1 && 'border-b',
                    )}
                  >
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
                        'mx-auto flex h-9 w-9 items-center justify-center rounded text-sm font-semibold transition-colors hover:ring-2 hover:ring-brand-400',
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

      <div className="flex flex-wrap items-center gap-4 border-t border-gray-200 bg-gray-50/60 px-4 py-3 text-xs text-gray-500 dark:border-gray-800 dark:bg-transparent dark:text-gray-400">
        <span className="font-medium text-gray-600 dark:text-gray-300">Correlation level:</span>
        {LEGEND.map((item) => (
          <span key={item.level} className="flex items-center gap-1.5">
            <span
              className={cn(
                'flex h-5 w-5 items-center justify-center rounded text-[11px] font-semibold',
                LEVEL_CLASSES[item.level],
              )}
            >
              {item.level}
            </span>
            {item.label}
          </span>
        ))}
      </div>
    </Card>
  );
}
