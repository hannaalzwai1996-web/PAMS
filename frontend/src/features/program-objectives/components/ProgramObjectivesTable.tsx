import { Table, TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '@/components/ui/Table';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { TargetIcon } from '@/components/icons';
import type { ProgramObjective } from '@/types/program';

interface ProgramObjectivesTableProps {
  objectives: ProgramObjective[];
  onEdit: (objective: ProgramObjective) => void;
  onDelete: (objective: ProgramObjective) => void;
  onAdd: () => void;
}

export function ProgramObjectivesTable({ objectives, onEdit, onDelete, onAdd }: ProgramObjectivesTableProps) {
  if (objectives.length === 0) {
    return (
      <EmptyState
        icon={<TargetIcon className="h-6 w-6" />}
        title="No objectives yet"
        description="Program Educational Objectives describe what graduates are expected to achieve a few years after graduation."
        action={<Button onClick={onAdd}>Add objective</Button>}
      />
    );
  }

  return (
    <Table>
      <TableHead>
        <TableRow>
          <TableHeaderCell>Code</TableHeaderCell>
          <TableHeaderCell>Statement</TableHeaderCell>
          <TableHeaderCell>Actions</TableHeaderCell>
        </TableRow>
      </TableHead>
      <TableBody>
        {objectives.map((objective) => (
          <TableRow key={objective.id}>
            <TableCell>
              <span className="font-medium text-brand-950 dark:text-white">{objective.code}</span>
            </TableCell>
            <TableCell>
              <span className="block max-w-2xl">{objective.statement}</span>
            </TableCell>
            <TableCell>
              <div className="flex gap-1">
                <Button variant="ghost" onClick={() => onEdit(objective)}>
                  Edit
                </Button>
                <Button variant="ghost-danger" onClick={() => onDelete(objective)}>
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
