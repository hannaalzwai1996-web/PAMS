/**
 * Mirrors the response envelope defined in ADR-0001 §7 / SRS-0001 §8.4 and
 * implemented by App\Support\Http\Concerns\ApiResponses on the backend.
 */
export interface ApiSuccessEnvelope<T> {
  data: T;
  meta?: Record<string, unknown>;
  links?: Record<string, unknown>;
}

/** Mirrors App\Support\Exceptions\Handlers\ApiExceptionRenderer's error shape. */
export interface ApiErrorEnvelope {
  message: string;
  errors?: Record<string, string[]>;
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface PaginationLinks {
  first: string | null;
  last: string | null;
  prev: string | null;
  next: string | null;
}

/** Shape of any Laravel API Resource collection response. */
export interface PaginatedResponse<T> {
  data: T[];
  meta: PaginationMeta;
  links: PaginationLinks;
}
