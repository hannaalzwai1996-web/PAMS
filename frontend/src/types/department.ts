/** Mirrors App\Http\Resources\Api\V1\DepartmentResource. Read-only from the SPA — Department itself is still Filament-managed. */
export interface Department {
  id: number;
  code: string;
  name: string;
}
