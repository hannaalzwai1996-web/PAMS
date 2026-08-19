import { useQuery } from '@tanstack/react-query';
import { departmentsService } from '../departmentsService';

export function useDepartments() {
  return useQuery({
    queryKey: ['departments'],
    queryFn: departmentsService.list,
  });
}
