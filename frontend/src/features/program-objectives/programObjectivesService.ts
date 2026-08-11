import { apiClient } from '@/services/apiClient';
import type { ApiSuccessEnvelope } from '@/types/api';
import type { ProgramObjective, ProgramObjectivePayload } from '@/types/program';

/** Matches routes/api/v1/program-objectives.php exactly. */
export const programObjectivesService = {
  async list(programId: string): Promise<ProgramObjective[]> {
    const response = await apiClient.get<ApiSuccessEnvelope<ProgramObjective[]>>(
      `/programs/${programId}/objectives`,
    );

    return response.data.data;
  },

  async create(programId: string, payload: ProgramObjectivePayload): Promise<ProgramObjective> {
    const response = await apiClient.post<ApiSuccessEnvelope<ProgramObjective>>(
      `/programs/${programId}/objectives`,
      payload,
    );

    return response.data.data;
  },

  async update(programId: string, objectiveId: string, payload: ProgramObjectivePayload): Promise<ProgramObjective> {
    const response = await apiClient.patch<ApiSuccessEnvelope<ProgramObjective>>(
      `/programs/${programId}/objectives/${objectiveId}`,
      payload,
    );

    return response.data.data;
  },

  async remove(programId: string, objectiveId: string): Promise<void> {
    await apiClient.delete(`/programs/${programId}/objectives/${objectiveId}`);
  },
};
