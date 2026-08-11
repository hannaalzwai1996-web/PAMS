import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { matrixService } from '../matrixService';
import type { MatrixBulkEditEntry } from '@/types/matrix';

const queryKey = (programId: string) => ['programs', programId, 'matrix'] as const;

export function useMatrix(programId: string) {
  return useQuery({
    queryKey: queryKey(programId),
    queryFn: () => matrixService.review(programId),
    enabled: programId !== '',
  });
}

export function useGenerateMatrix(programId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (force: boolean) => matrixService.generate(programId, force),
    onSuccess: (grid) => queryClient.setQueryData(queryKey(programId), grid),
  });
}

export function useUpdateMatrix(programId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (entries: MatrixBulkEditEntry[]) => matrixService.bulkUpdate(programId, entries),
    onSuccess: (grid) => queryClient.setQueryData(queryKey(programId), grid),
  });
}
