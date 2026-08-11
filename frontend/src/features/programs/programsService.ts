import { apiClient } from '@/services/apiClient';
import type { ApiSuccessEnvelope } from '@/types/api';
import type { Program } from '@/types/program';

/** Matches routes/api/v1/programs.php exactly. */
export const programsService = {
  async list(): Promise<Program[]> {
    const response = await apiClient.get<ApiSuccessEnvelope<Program[]>>('/programs');

    return response.data.data;
  },
};
