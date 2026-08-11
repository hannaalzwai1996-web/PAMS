import { useAuth } from '@/hooks/useAuth';
import { Spinner } from '@/components/ui/Spinner';
import { usePrograms } from '@/features/programs/hooks/usePrograms';
import { ProgramsGrid } from '@/features/programs/components/ProgramsGrid';

const ROLE_LABELS: Record<string, string> = {
  admin: 'Administrator',
  qa_officer: 'Quality Assurance Officer',
  program_coordinator: 'Program Coordinator',
};

export function DashboardPage() {
  const { user } = useAuth();
  const roleLabel = user?.roles.map((role) => ROLE_LABELS[role] ?? role).join(', ');
  const programsQuery = usePrograms();

  return (
    <div>
      <h1 className="text-xl font-semibold text-gray-900 dark:text-white">Welcome, {user?.name}</h1>
      <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">Signed in as {roleLabel}.</p>

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
        ) : (
          <ProgramsGrid programs={programsQuery.data ?? []} />
        )}
      </div>
    </div>
  );
}
