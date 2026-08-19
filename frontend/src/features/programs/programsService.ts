import { apiClient } from '@/services/apiClient';
import type { ApiSuccessEnvelope } from '@/types/api';
import type { Program, ProgramPayload } from '@/types/program';

/** Matches routes/api/v1/programs.php exactly. */
export const programsService = {
  async list(): Promise<Program[]> {
    const response = await apiClient.get<ApiSuccessEnvelope<Program[]>>('/programs');

    return response.data.data;
  },

  async show(programId: string): Promise<Program> {
    const response = await apiClient.get<ApiSuccessEnvelope<Program>>(`/programs/${programId}`);

    return response.data.data;
  },

  async create(payload: ProgramPayload): Promise<Program> {
    const response = await apiClient.post<ApiSuccessEnvelope<Program>>('/programs', payload);

    return response.data.data;
  },

  async update(programId: string, payload: ProgramPayload): Promise<Program> {
    const response = await apiClient.patch<ApiSuccessEnvelope<Program>>(`/programs/${programId}`, payload);

    return response.data.data;
  },

  async remove(programId: string): Promise<void> {
    await apiClient.delete(`/programs/${programId}`);
  },

  async assignCoordinator(programId: string, userId: string): Promise<Program> {
    const response = await apiClient.post<ApiSuccessEnvelope<Program>>(`/programs/${programId}/coordinators`, {
      user_id: userId,
    });

    return response.data.data;
  },

  async unassignCoordinator(programId: string, userId: string): Promise<Program> {
    const response = await apiClient.delete<ApiSuccessEnvelope<Program>>(
      `/programs/${programId}/coordinators/${userId}`,
    );

    return response.data.data;
  },
};
