import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { learningOutcomesService } from '../learningOutcomesService';
import type { LearningOutcomePayload, ObjectiveMapping } from '@/types/program';

const queryKey = (programId: string) => ['programs', programId, 'learning-outcomes'] as const;

export function useLearningOutcomes(programId: string) {
  return useQuery({
    queryKey: queryKey(programId),
    queryFn: () => learningOutcomesService.list(programId),
    enabled: programId !== '',
  });
}

export function useCreateLearningOutcome(programId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: LearningOutcomePayload) => learningOutcomesService.create(programId, payload),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: queryKey(programId) }),
  });
}

export function useUpdateLearningOutcome(programId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ outcomeId, payload }: { outcomeId: string; payload: LearningOutcomePayload }) =>
      learningOutcomesService.update(programId, outcomeId, payload),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: queryKey(programId) }),
  });
}

export function useDeleteLearningOutcome(programId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (outcomeId: string) => learningOutcomesService.remove(programId, outcomeId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: queryKey(programId) }),
  });
}

export function useSyncObjectiveMappings(programId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ outcomeId, mappings }: { outcomeId: string; mappings: ObjectiveMapping[] }) =>
      learningOutcomesService.syncObjectiveMappings(programId, outcomeId, mappings),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: queryKey(programId) }),
  });
}
