<?php

namespace App\Policies;

use App\Models\User;

/**
 * Reference implementation of the ADR-0001 §12 immutable rule:
 * authorization is enforced via Policies, never inline role-string checks
 * in a controller. Every future domain Policy (ProgramPolicy,
 * QualityReportPolicy, ...) follows this same shape — see
 * docs/architecture/0001-authentication-architecture.md §6.
 *
 * Every ability here gates on the same `users.manage` permission: user
 * management is a single, non-delegable authority in PAMS (only `admin`
 * holds it — see PermissionSeeder). Separate methods still exist per
 * action so each route/test names exactly what it's authorizing, and so a
 * future, more granular split (e.g. a QA lead who can view but not delete)
 * only requires changing one method body, not the call sites.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('users.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('users.manage');
    }

    public function update(User $user, User $target): bool
    {
        return $user->hasPermissionTo('users.manage');
    }

    public function delete(User $user, User $target): bool
    {
        return $user->hasPermissionTo('users.manage');
    }

    public function assignRole(User $user, User $target): bool
    {
        return $user->hasPermissionTo('users.manage');
    }

    public function managePermissions(User $user, User $target): bool
    {
        return $user->hasPermissionTo('users.manage');
    }
}
