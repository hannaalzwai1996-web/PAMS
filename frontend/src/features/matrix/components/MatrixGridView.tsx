import { cn } from '@/utils/cn';
import type { MatrixGrid } from '@/types/matrix';
import type { SelectedCell } from './MatrixCellModal';

const LEVEL_CLASSES: Record<number, string> = {
  1: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
  2: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
  3: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
};

export function MatrixGridView({ grid, onSelectCell }: { grid: MatrixGrid; onSelectCell: (cell: SelectedCell) => void }) {
  if (grid.objectives.length === 0 || grid.outcomes.length === 0) {
    return (
      <p className="text-sm text-gray-500 dark:text-gray-400">
        Add at least one Program Objective and one Learning Outcome before generating a matrix.
      </p>
    );
  }

  return (
    <div className="overflow-x-auto rounded-lg ring-1 ring-gray-200 dark:ring-gray-700">
      <table className="min-w-full border-collapse text-sm">
        <thead className="bg-gray-50 dark:bg-gray-800">
          <tr>
            <th className="sticky left-0 bg-gray-50 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-800 dark:text-gray-400">
              Objective \ Outcome
            </th>
            {grid.outcomes.map((outcome) => (
              <th
                key={outcome.id}
                className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
              >
                {outcome.code}
              </th>
            ))}
          </tr>
        </thead>
        <tbody className="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-gray-900">
          {grid.rows.map((row) => (
            <tr key={row.objective.id}>
              <td className="sticky left-0 bg-white px-4 py-3 font-medium text-gray-900 dark:bg-gray-900 dark:text-white">
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
                      'flex h-9 w-9 items-center justify-center rounded-md text-sm font-semibold transition-opacity hover:opacity-75',
                      cell.correlation_level
                        ? LEVEL_CLASSES[cell.correlation_level]
                        : 'bg-gray-50 text-gray-300 ring-1 ring-inset ring-gray-200 dark:bg-gray-800/50 dark:text-gray-600 dark:ring-gray-700',
                    )}
                    title={cell.source ? `Source: ${cell.source}` : 'Unmapped'}
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
  );
}
