import { apiClient, ensureCsrfCookie } from '@/services/apiClient';
import type { ApiSuccessEnvelope } from '@/types/api';
import type { User } from '@/types/user';

export interface LoginCredentials {
  email: string;
  password: string;
}

/**
 * Every call here talks to the real backend (POST /auth/login, etc.) —
 * per the project rule that the frontend communicates only through REST
 * APIs, nothing is mocked or simulated client-side.
 */
export const authService = {
  async login(credentials: LoginCredentials): Promise<User> {
    // Sanctum SPA auth: the CSRF cookie must be fetched before every
    // fresh-session login attempt (ARCH-0001 §1).
    await ensureCsrfCookie();

    const response = await apiClient.post<ApiSuccessEnvelope<User>>('/auth/login', credentials);

    return response.data.data;
  },

  async logout(): Promise<void> {
    await apiClient.post('/auth/logout');
  },

  async me(): Promise<User> {
    const response = await apiClient.get<ApiSuccessEnvelope<User>>('/auth/me');

    return response.data.data;
  },
};
