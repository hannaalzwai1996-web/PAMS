import { Table, TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '@/components/ui/Table';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { CheckBadgeIcon } from '@/components/icons';
import type { LearningOutcome, LearningOutcomeCategory } from '@/types/program';

const CATEGORY_COLOR: Record<LearningOutcomeCategory, 'indigo' | 'blue' | 'green' | 'yellow'> = {
  A: 'indigo',
  B: 'blue',
  C: 'green',
  D: 'yellow',
};

interface LearningOutcomesTableProps {
  outcomes: LearningOutcome[];
  onEdit: (outcome: LearningOutcome) => void;
  onDelete: (outcome: LearningOutcome) => void;
  onAdd: () => void;
}

export function LearningOutcomesTable({ outcomes, onEdit, onDelete, onAdd }: LearningOutcomesTableProps) {
  if (outcomes.length === 0) {
    return (
      <EmptyState
        icon={<CheckBadgeIcon className="h-6 w-6" />}
        title="No learning outcomes yet"
        description="Program Learning Outcomes describe what students are expected to know and be able to do by graduation."
        action={<Button onClick={onAdd}>Add outcome</Button>}
      />
    );
  }

  return (
    <Table>
      <TableHead>
        <TableRow>
          <TableHeaderCell>Code</TableHeaderCell>
          <TableHeaderCell>Statement</TableHeaderCell>
          <TableHeaderCell>Category</TableHeaderCell>
          <TableHeaderCell>Actions</TableHeaderCell>
        </TableRow>
      </TableHead>
      <TableBody>
        {outcomes.map((outcome) => (
          <TableRow key={outcome.id}>
            <TableCell>
              <span className="font-medium text-gray-900 dark:text-white">{outcome.code}</span>
            </TableCell>
            <TableCell>
              <span className="block max-w-2xl">{outcome.statement}</span>
            </TableCell>
            <TableCell>
              <Badge color={CATEGORY_COLOR[outcome.category]}>{outcome.category_label}</Badge>
            </TableCell>
            <TableCell>
              <div className="flex gap-1">
                <Button variant="ghost" onClick={() => onEdit(outcome)}>
                  Edit
                </Button>
                <Button variant="ghost-danger" onClick={() => onDelete(outcome)}>
                  Delete
                </Button>
              </div>
            </TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
}
