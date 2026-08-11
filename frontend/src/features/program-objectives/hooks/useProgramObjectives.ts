import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { programObjectivesService } from '../programObjectivesService';
import type { ProgramObjectivePayload } from '@/types/program';

const queryKey = (programId: string) => ['programs', programId, 'objectives'] as const;

export function useProgramObjectives(programId: string) {
  return useQuery({
    queryKey: queryKey(programId),
    queryFn: () => programObjectivesService.list(programId),
    enabled: programId !== '',
  });
}

export function useCreateProgramObjective(programId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: ProgramObjectivePayload) => programObjectivesService.create(programId, payload),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: queryKey(programId) }),
  });
}

export function useUpdateProgramObjective(programId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ objectiveId, payload }: { objectiveId: string; payload: ProgramObjectivePayload }) =>
      programObjectivesService.update(programId, objectiveId, payload),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: queryKey(programId) }),
  });
}

export function useDeleteProgramObjective(programId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (objectiveId: string) => programObjectivesService.remove(programId, objectiveId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: queryKey(programId) }),
  });
}
