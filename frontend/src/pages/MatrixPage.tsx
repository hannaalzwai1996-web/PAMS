import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { Button } from '@/components/ui/Button';
import { Spinner } from '@/components/ui/Spinner';
import { PageHeader } from '@/components/ui/PageHeader';
import { useMatrix, useGenerateMatrix } from '@/features/matrix/hooks/useMatrix';
import { matrixService } from '@/features/matrix/matrixService';
import { useProgramContext } from '@/features/programs/hooks/useProgramContext';
import { MatrixGridView } from '@/features/matrix/components/MatrixGridView';
import { MatrixSummaryBar } from '@/features/matrix/components/MatrixSummaryBar';
import { MatrixCellModal, type SelectedCell } from '@/features/matrix/components/MatrixCellModal';
import { toApiError } from '@/utils/apiError';

/**
 * The PO-PLO Matrix Generation Engine's UI (ARCH-0002). "Automatically
 * generate," "review," "manually edit," and "export" all map 1:1 to real
 * backend endpoints — nothing here recomputes a correlation client-side;
 * the lexical-overlap algorithm only exists in PoPloMatrixService.
 */
export function MatrixPage() {
  const { programId = '' } = useParams<{ programId: string }>();
  const matrixQuery = useMatrix(programId);
  const generateMatrix = useGenerateMatrix(programId);
  const program = useProgramContext(programId);

  const [selectedCell, setSelectedCell] = useState<SelectedCell | null>(null);
  const [isExporting, setIsExporting] = useState(false);

  if (!programId) return null;

  async function handleGenerate(force: boolean) {
    try {
      await generateMatrix.mutateAsync(force);
    } catch (error) {
      window.alert(toApiError(error).message);
    }
  }

  async function handleExport() {
    setIsExporting(true);
    try {
      const blob = await matrixService.exportCsv(programId);
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `po-plo-matrix-${programId}.csv`;
      link.click();
      URL.revokeObjectURL(url);
    } catch (error) {
      window.alert(toApiError(error).message);
    } finally {
      setIsExporting(false);
    }
  }

  return (
    <div>
      <PageHeader
        title="PO–PLO Matrix"
        description={program ? `${program.code} — ${program.name}` : 'Program Objective ↔ Learning Outcome alignment'}
        backTo={{ to: '/', label: 'Back to Dashboard' }}
        actions={
          <>
            <Button variant="secondary" onClick={() => void handleGenerate(false)} isLoading={generateMatrix.isPending}>
              Generate
            </Button>
            <Button
              variant="secondary"
              onClick={() => void handleGenerate(true)}
              isLoading={generateMatrix.isPending}
              title="Refreshes previously auto-generated cells; never touches manually edited ones"
            >
              Regenerate (force)
            </Button>
            <Button variant="secondary" onClick={() => void handleExport()} isLoading={isExporting}>
              Export CSV
            </Button>
          </>
        }
      />

      <div className="mt-6 space-y-4">
        {matrixQuery.isLoading ? (
          <div className="flex justify-center py-12">
            <Spinner />
          </div>
        ) : matrixQuery.isError ? (
          <p className="text-sm text-red-600 dark:text-red-400">Failed to load the matrix.</p>
        ) : matrixQuery.data ? (
          <>
            <MatrixSummaryBar summary={matrixQuery.data.summary} />
            <MatrixGridView grid={matrixQuery.data} onSelectCell={setSelectedCell} />
          </>
        ) : null}
      </div>

      <MatrixCellModal programId={programId} cell={selectedCell} onClose={() => setSelectedCell(null)} />
    </div>
  );
}
