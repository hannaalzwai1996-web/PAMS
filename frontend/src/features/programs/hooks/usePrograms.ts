import { useQuery } from '@tanstack/react-query';
import { programsService } from '../programsService';

export function usePrograms() {
  return useQuery({
    queryKey: ['programs'],
    queryFn: programsService.list,
  });
}
