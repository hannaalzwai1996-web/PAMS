import { apiClient } from '@/services/apiClient';
import type { ApiSuccessEnvelope, PaginatedResponse } from '@/types/api';
import type { CreateUserPayload, Role, UpdateUserPayload, User } from '@/types/user';

/**
 * One function per real backend endpoint (routes/api/v1/admin.php) — no
 * endpoint is invented here that doesn't already exist server-side (the
 * project rule: the frontend communicates only through REST APIs).
 */
export const usersService = {
  /**
   * `role`/`per_page` are optional filters added for the Program
   * Coordinator-assignment picker (P0.2) — `GET /admin/users?role=...`.
   * Omitted, this is the exact same call the Users admin page already made.
   */
  async list(page = 1, options: { role?: Role; per_page?: number } = {}): Promise<PaginatedResponse<User>> {
    const response = await apiClient.get<PaginatedResponse<User>>('/admin/users', {
      params: { page, ...options },
    });

    return response.data;
  },

  async create(payload: CreateUserPayload): Promise<User> {
    const response = await apiClient.post<ApiSuccessEnvelope<User>>('/admin/users', payload);

    return response.data.data;
  },

  async update(userId: string, payload: UpdateUserPayload): Promise<User> {
    const response = await apiClient.patch<ApiSuccessEnvelope<User>>(`/admin/users/${userId}`, payload);

    return response.data.data;
  },

  async remove(userId: string): Promise<void> {
    await apiClient.delete(`/admin/users/${userId}`);
  },

  async assignRole(userId: string, role: Role): Promise<User> {
    const response = await apiClient.post<ApiSuccessEnvelope<User>>(`/admin/users/${userId}/role`, { role });

    return response.data.data;
  },

  async syncPermissions(userId: string, permissions: string[]): Promise<User> {
    const response = await apiClient.put<ApiSuccessEnvelope<User>>(`/admin/users/${userId}/permissions`, {
      permissions,
    });

    return response.data.data;
  },
};
