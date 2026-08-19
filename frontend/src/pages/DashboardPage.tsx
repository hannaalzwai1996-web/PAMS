import { useMemo, useState } from 'react';
import { useAuth } from '@/hooks/useAuth';
import { Spinner } from '@/components/ui/Spinner';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { usePrograms, useDeleteProgram } from '@/features/programs/hooks/usePrograms';
import { ProgramsGrid } from '@/features/programs/components/ProgramsGrid';
import { ProgramFormModal } from '@/features/programs/components/ProgramFormModal';
import { toApiError } from '@/utils/apiError';
import { BookOpenIcon, BuildingIcon, CheckBadgeIcon, InboxIcon } from '@/components/icons';
import type { Program } from '@/types/program';

const ROLE_LABELS: Record<string, string> = {
  admin: 'Administrator',
  qa_officer: 'Quality Assurance Officer',
  program_coordinator: 'Program Coordinator',
};

export function DashboardPage() {
  const { user, hasRole } = useAuth();
  const roleLabel = user?.roles.map((role) => ROLE_LABELS[role] ?? role).join(', ');
  const programsQuery = usePrograms();
  const deleteProgram = useDeleteProgram();
  const programs = programsQuery.data ?? [];

  const [modalProgramId, setModalProgramId] = useState<string | null | undefined>(null);

  const stats = useMemo(
    () => ({
      total: programsQuery.data?.length ?? 0,
      draft: programsQuery.data?.filter((p) => p.status === 'draft').length ?? 0,
      submitted: programsQuery.data?.filter((p) => p.status === 'submitted').length ?? 0,
      approved: programsQuery.data?.filter((p) => p.status === 'approved').length ?? 0,
    }),
    [programsQuery.data],
  );

  function handleDelete(program: Program) {
    if (!window.confirm(`Delete program "${program.name}"? This cannot be undone.`)) return;

    deleteProgram.mutate(program.id, {
      onError: (error) => window.alert(toApiError(error).message),
    });
  }

  return (
    <div>
      <PageHeader
        title={`Welcome, ${user?.name ?? ''}`}
        description={`Signed in as ${roleLabel}.`}
        actions={hasRole('admin') ? <Button onClick={() => setModalProgramId(undefined)}>Create program</Button> : undefined}
      />

      {!programsQuery.isLoading && !programsQuery.isError && (
        <div className="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
          <StatCard icon={<BuildingIcon className="h-5 w-5" />} label="Total programs" value={stats.total} />
          <StatCard icon={<InboxIcon className="h-5 w-5" />} label="Draft" value={stats.draft} />
          <StatCard icon={<BookOpenIcon className="h-5 w-5" />} label="Submitted" value={stats.submitted} />
          <StatCard icon={<CheckBadgeIcon className="h-5 w-5" />} label="Approved" value={stats.approved} />
        </div>
      )}

      <h2 className="mt-8 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
        Programs
      </h2>
      <div className="mt-3">
        {programsQuery.isLoading ? (
          <div className="flex justify-center py-12">
            <Spinner />
          </div>
        ) : programsQuery.isError ? (
          <p className="text-sm text-red-600 dark:text-red-400">Failed to load programs.</p>
        ) : programs.length === 0 ? (
          <EmptyState
            icon={<BuildingIcon className="h-6 w-6" />}
            title="No programs yet"
            description="Programs you're assigned to, or every program in the system if you're an admin or QA officer, will show up here."
          />
        ) : (
          <ProgramsGrid
            programs={programs}
            onEdit={(program) => setModalProgramId(program.id)}
            onDelete={handleDelete}
          />
        )}
      </div>

      <ProgramFormModal programId={modalProgramId} onClose={() => setModalProgramId(null)} />
    </div>
  );
}

function StatCard({ icon, label, value }: { icon: React.ReactNode; label: string; value: number }) {
  return (
    <Card className="flex items-center gap-3 p-4">
      <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400">
        {icon}
      </span>
      <div>
        <p className="text-xl font-semibold leading-none text-brand-950 dark:text-white">{value}</p>
        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{label}</p>
      </div>
    </Card>
  );
}
