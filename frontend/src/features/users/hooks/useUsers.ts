import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { usersService } from '../usersService';
import type { CreateUserPayload, Role, UpdateUserPayload } from '@/types/user';

const usersQueryKey = (page: number) => ['users', page] as const;

export function useUsers(page = 1) {
  return useQuery({
    queryKey: usersQueryKey(page),
    queryFn: () => usersService.list(page),
    placeholderData: (previous) => previous,
  });
}

/** Invalidates every cached users page — simplest correct option for a small admin list. */
function useInvalidateUsers() {
  const queryClient = useQueryClient();

  return () => queryClient.invalidateQueries({ queryKey: ['users'] });
}

export function useCreateUser() {
  const invalidate = useInvalidateUsers();

  return useMutation({
    mutationFn: (payload: CreateUserPayload) => usersService.create(payload),
    onSuccess: invalidate,
  });
}

export function useUpdateUser() {
  const invalidate = useInvalidateUsers();

  return useMutation({
    mutationFn: ({ userId, payload }: { userId: string; payload: UpdateUserPayload }) =>
      usersService.update(userId, payload),
    onSuccess: invalidate,
  });
}

export function useDeleteUser() {
  const invalidate = useInvalidateUsers();

  return useMutation({
    mutationFn: (userId: string) => usersService.remove(userId),
    onSuccess: invalidate,
  });
}

export function useAssignRole() {
  const invalidate = useInvalidateUsers();

  return useMutation({
    mutationFn: ({ userId, role }: { userId: string; role: Role }) => usersService.assignRole(userId, role),
    onSuccess: invalidate,
  });
}

export function useSyncPermissions() {
  const invalidate = useInvalidateUsers();

  return useMutation({
    mutationFn: ({ userId, permissions }: { userId: string; permissions: string[] }) =>
      usersService.syncPermissions(userId, permissions),
    onSuccess: invalidate,
  });
}
