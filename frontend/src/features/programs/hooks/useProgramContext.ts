import { useLocation } from 'react-router-dom';
import { usePrograms } from './usePrograms';

interface ProgramContext {
  code: string;
  name: string;
}

/**
 * Best-effort "which program is this page about" for the breadcrumb on
 * Objectives/Learning Outcomes/Matrix pages — those routes only carry a
 * program id in the URL. Reads router `state` first (ProgramsGrid passes
 * {programCode, programName} on click, so it's instant with no extra
 * request); falls back to the already-cached `usePrograms()` list (same
 * query key as the Dashboard, so usually an instant cache hit even on a
 * hard refresh or a direct link). Returns undefined while neither source
 * has an answer yet — callers should render the code/name as optional.
 */
export function useProgramContext(programId: string): ProgramContext | undefined {
  const location = useLocation();
  const state = location.state as { programCode?: string; programName?: string } | null;

  const programsQuery = usePrograms();

  if (state?.programCode && state?.programName) {
    return { code: state.programCode, name: state.programName };
  }

  const match = programsQuery.data?.find((program) => program.id === programId);
  return match ? { code: match.code, name: match.name } : undefined;
}
