import { apiClient } from '@/services/apiClient';
import type { ApiSuccessEnvelope } from '@/types/api';
import type { Department } from '@/types/department';

/** Matches routes/api/v1/departments.php exactly — read-only, Department itself stays Filament-managed. */
export const departmentsService = {
  async list(): Promise<Department[]> {
    const response = await apiClient.get<ApiSuccessEnvelope<Department[]>>('/departments');

    return response.data.data;
  },
};
