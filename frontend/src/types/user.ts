/** Mirrors the fixed role set in App\Support\Enums\Role (backend). */
export type Role = 'admin' | 'qa_officer' | 'program_coordinator';

/** Mirrors App\Http\Resources\Api\V1\UserResource. */
export interface User {
  id: string;
  name: string;
  email: string;
  roles: Role[];
  direct_permissions: string[];
  effective_permissions: string[];
  is_active: boolean;
}

export interface CreateUserPayload {
  name: string;
  email: string;
  password: string;
  role: Role;
}

export interface UpdateUserPayload {
  name?: string;
  email?: string;
  is_active?: boolean;
}
