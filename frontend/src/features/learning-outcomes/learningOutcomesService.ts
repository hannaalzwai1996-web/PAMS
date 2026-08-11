import { apiClient } from '@/services/apiClient';
import type { ApiSuccessEnvelope } from '@/types/api';
import type { LearningOutcome, LearningOutcomePayload, ObjectiveMapping } from '@/types/program';

/** Matches routes/api/v1/learning-outcomes.php exactly. */
export const learningOutcomesService = {
  async list(programId: string): Promise<LearningOutcome[]> {
    const response = await apiClient.get<ApiSuccessEnvelope<LearningOutcome[]>>(
      `/programs/${programId}/learning-outcomes`,
    );

    return response.data.data;
  },

  async create(programId: string, payload: LearningOutcomePayload): Promise<LearningOutcome> {
    const response = await apiClient.post<ApiSuccessEnvelope<LearningOutcome>>(
      `/programs/${programId}/learning-outcomes`,
      payload,
    );

    return response.data.data;
  },

  async update(programId: string, outcomeId: string, payload: LearningOutcomePayload): Promise<LearningOutcome> {
    const response = await apiClient.patch<ApiSuccessEnvelope<LearningOutcome>>(
      `/programs/${programId}/learning-outcomes/${outcomeId}`,
      payload,
    );

    return response.data.data;
  },

  async remove(programId: string, outcomeId: string): Promise<void> {
    await apiClient.delete(`/programs/${programId}/learning-outcomes/${outcomeId}`);
  },

  async syncObjectiveMappings(
    programId: string,
    outcomeId: string,
    mappings: ObjectiveMapping[],
  ): Promise<LearningOutcome> {
    const response = await apiClient.put<ApiSuccessEnvelope<LearningOutcome>>(
      `/programs/${programId}/learning-outcomes/${outcomeId}/objectives`,
      { mappings },
    );

    return response.data.data;
  },
};
