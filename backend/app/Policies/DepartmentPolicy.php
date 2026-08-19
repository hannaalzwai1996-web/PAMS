<?php

namespace App\Policies;

use App\Models\User;

/**
 * Read-only reference data — every authenticated user can list
 * departments (needed to populate the Program create/edit form's
 * department picker, P0.1). Mutating a department stays Filament-only
 * (DepartmentService), so no other ability is defined here.
 *
 * Auto-discovered: App\Models\Department → this class, same convention
 * UserPolicy already relies on.
 */
class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }
}
