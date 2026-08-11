import { apiClient } from '@/services/apiClient';
import type { ApiSuccessEnvelope } from '@/types/api';
import type { MatrixBulkEditEntry, MatrixGrid } from '@/types/matrix';

/** Matches routes/api/v1/matrix.php exactly (ARCH-0002). */
export const matrixService = {
  async review(programId: string): Promise<MatrixGrid> {
    const response = await apiClient.get<ApiSuccessEnvelope<MatrixGrid>>(`/programs/${programId}/matrix`);

    return response.data.data;
  },

  async generate(programId: string, force = false): Promise<MatrixGrid> {
    const response = await apiClient.post<ApiSuccessEnvelope<MatrixGrid>>(`/programs/${programId}/matrix/generate`, {
      force,
    });

    return response.data.data;
  },

  async bulkUpdate(programId: string, entries: MatrixBulkEditEntry[]): Promise<MatrixGrid> {
    const response = await apiClient.put<ApiSuccessEnvelope<MatrixGrid>>(`/programs/${programId}/matrix`, {
      entries,
    });

    return response.data.data;
  },

  /** Fetched as a blob (not a plain <a href>) so auth failures surface in-app instead of a raw browser error page. */
  async exportCsv(programId: string): Promise<Blob> {
    const response = await apiClient.get(`/programs/${programId}/matrix/export`, { responseType: 'blob' });

    return response.data as Blob;
  },
};
