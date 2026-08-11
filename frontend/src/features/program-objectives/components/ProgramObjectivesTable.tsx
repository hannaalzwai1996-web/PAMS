import { Table, TableHead, TableBody, TableRow, TableHeaderCell, TableCell } from '@/components/ui/Table';
import { Button } from '@/components/ui/Button';
import type { ProgramObjective } from '@/types/program';

interface ProgramObjectivesTableProps {
  objectives: ProgramObjective[];
  onEdit: (objective: ProgramObjective) => void;
  onDelete: (objective: ProgramObjective) => void;
}

export function ProgramObjectivesTable({ objectives, onEdit, onDelete }: ProgramObjectivesTableProps) {
  if (objectives.length === 0) {
    return <p className="text-sm text-gray-500 dark:text-gray-400">No objectives yet.</p>;
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
            <TableCell>{objective.code}</TableCell>
            <TableCell>{objective.statement}</TableCell>
            <TableCell>
              <div className="flex gap-2">
                <Button variant="ghost" onClick={() => onEdit(objective)}>
                  Edit
                </Button>
                <Button variant="ghost" onClick={() => onDelete(objective)}>
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
