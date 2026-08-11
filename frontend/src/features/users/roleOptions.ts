import type { Role } from '@/types/user';

/**
 * Shared by CreateUserModal and EditUserModal — was duplicated verbatim
 * in both (found during the pre-deployment quality review), risking the
 * two forms silently drifting apart if the role set ever changed.
 */
export const ROLE_OPTIONS: Array<{ value: Role; label: string }> = [
  { value: 'program_coordinator', label: 'Program Coordinator' },
  { value: 'qa_officer', label: 'Quality Assurance Officer' },
  { value: 'admin', label: 'Admin' },
];
