import { Badge } from '@/components/ui/Badge';
import type { MatrixSummary } from '@/types/matrix';

export function MatrixSummaryBar({ summary }: { summary: MatrixSummary }) {
  return (
    <div className="flex flex-wrap items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
      <span>{summary.total_pairs} total pairs</span>
      <Badge color="green">{summary.auto} auto</Badge>
      <Badge color="blue">{summary.manual} manual</Badge>
      <Badge color="gray">{summary.unmapped} unmapped</Badge>
    </div>
  );
}
