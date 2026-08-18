import { Card } from '@/components/ui/Card';
import type { MatrixSummary } from '@/types/matrix';

const TILES: Array<{ key: keyof MatrixSummary; label: string; dot: string }> = [
  { key: 'total_pairs', label: 'Total pairs', dot: 'bg-gray-400' },
  { key: 'auto', label: 'Auto-generated', dot: 'bg-emerald-500' },
  { key: 'manual', label: 'Manually edited', dot: 'bg-blue-500' },
  { key: 'unmapped', label: 'Unmapped', dot: 'bg-gray-300' },
];

export function MatrixSummaryBar({ summary }: { summary: MatrixSummary }) {
  return (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
      {TILES.map((tile) => (
        <Card key={tile.key} className="flex items-center gap-2.5 px-4 py-3">
          <span className={`h-2.5 w-2.5 shrink-0 rounded-full ${tile.dot}`} />
          <div>
            <p className="text-lg font-semibold leading-none text-gray-900 dark:text-white">{summary[tile.key]}</p>
            <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{tile.label}</p>
          </div>
        </Card>
      ))}
    </div>
  );
}
