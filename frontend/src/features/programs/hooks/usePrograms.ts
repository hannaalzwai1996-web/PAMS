import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { programsService } from '../programsService';
import type { ProgramPayload } from '@/types/program';

export function usePrograms() {
  return useQuery({
    queryKey: ['programs'],
    queryFn: programsService.list,
  });
}

/** The single-program fetch — the one that carries `coordinators`, which the list endpoint doesn't eager-load. */
export function useProgram(programId: string) {
  return useQuery({
    queryKey: ['programs', programId],
    queryFn: () => programsService.show(programId),
    enabled: programId !== '',
  });
}

/**
 * Invalidating the bare `['programs']` key also invalidates every
 * `['programs', id]` single-program query — React Query matches by key
 * prefix — so one helper covers both the list and detail views after any
 * mutation.
 */
function useInvalidatePrograms() {
  const queryClient = useQueryClient();

  return () => queryClient.invalidateQueries({ queryKey: ['programs'] });
}

export function useCreateProgram() {
  const invalidate = useInvalidatePrograms();

  return useMutation({
    mutationFn: (payload: ProgramPayload) => programsService.create(payload),
    onSuccess: invalidate,
  });
}

export function useUpdateProgram(programId: string) {
  const invalidate = useInvalidatePrograms();

  return useMutation({
    mutationFn: (payload: ProgramPayload) => programsService.update(programId, payload),
    onSuccess: invalidate,
  });
}

export function useDeleteProgram() {
  const invalidate = useInvalidatePrograms();

  return useMutation({
    mutationFn: (programId: string) => programsService.remove(programId),
    onSuccess: invalidate,
  });
}

export function useAssignCoordinator(programId: string) {
  const invalidate = useInvalidatePrograms();

  return useMutation({
    mutationFn: (userId: string) => programsService.assignCoordinator(programId, userId),
    onSuccess: invalidate,
  });
}

export function useUnassignCoordinator(programId: string) {
  const invalidate = useInvalidatePrograms();

  return useMutation({
    mutationFn: (userId: string) => programsService.unassignCoordinator(programId, userId),
    onSuccess: invalidate,
  });
}
