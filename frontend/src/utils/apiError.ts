import { isAxiosError } from 'axios';
import type { ApiErrorEnvelope } from '@/types/api';

/**
 * Normalizes anything a failed request can throw into one typed shape, so
 * every feature handles errors the same way instead of each guessing at
 * Axios's error shape. Mirrors the backend's one error envelope
 * (App\Support\Exceptions\Handlers\ApiExceptionRenderer) on the frontend
 * side.
 */
export class ApiError extends Error {
  readonly status?: number;
  readonly errors?: Record<string, string[]>;

  constructor(message: string, status?: number, errors?: Record<string, string[]>) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.errors = errors;
  }

  /** The first validation message for a given field, if any — handy for form fields. */
  fieldError(field: string): string | undefined {
    return this.errors?.[field]?.[0];
  }
}

export function toApiError(error: unknown): ApiError {
  // Idempotent: without this check first, an already-normalized ApiError
  // falls through to the generic `Error` branch below (ApiError extends
  // Error) and silently loses .status/.errors — found via a test that
  // mocked a service rejection as an ApiError directly, a reasonable
  // thing to do and not actually specific to tests, so this is a real
  // gap, not a test-only concern.
  if (error instanceof ApiError) {
    return error;
  }

  if (isAxiosError<ApiErrorEnvelope>(error)) {
    const message = error.response?.data?.message ?? error.message ?? 'Request failed.';

    return new ApiError(message, error.response?.status, error.response?.data?.errors);
  }

  if (error instanceof Error) {
    return new ApiError(error.message);
  }

  return new ApiError('An unexpected error occurred.');
}
