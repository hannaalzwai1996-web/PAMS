import { Table, TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '@/components/ui/Table';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import type { LearningOutcome } from '@/types/program';

interface LearningOutcomesTableProps {
  outcomes: LearningOutcome[];
  onEdit: (outcome: LearningOutcome) => void;
  onDelete: (outcome: LearningOutcome) => void;
}

export function LearningOutcomesTable({ outcomes, onEdit, onDelete }: LearningOutcomesTableProps) {
  if (outcomes.length === 0) {
    return <p className="text-sm text-gray-500 dark:text-gray-400">No learning outcomes yet.</p>;
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
            <TableCell>{outcome.code}</TableCell>
            <TableCell>{outcome.statement}</TableCell>
            <TableCell>
              <Badge color="indigo">{outcome.category_label}</Badge>
            </TableCell>
            <TableCell>
              <div className="flex gap-2">
                <Button variant="ghost" onClick={() => onEdit(outcome)}>
                  Edit
                </Button>
                <Button variant="ghost" onClick={() => onDelete(outcome)}>
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
