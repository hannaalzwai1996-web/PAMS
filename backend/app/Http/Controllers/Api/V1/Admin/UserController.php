<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\User\DTOs\AssignRoleDTO;
use App\Domain\User\DTOs\SyncPermissionsDTO;
use App\Domain\User\DTOs\UserDTO;
use App\Domain\User\Services\UserService;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Admin\AssignRoleRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\SyncUserPermissionsRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Support\Enums\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * PAMS's "registration" surface — admin-provisioned accounts only (see
 * ARCH-0001 §0/§3). Authorization for every action here is already
 * resolved by the `can:` route middleware before the method body runs
 * (routes/api/v1/admin.php), so these methods only validate-and-delegate,
 * per ADR-0001 §2 — no business logic lives in this controller.
 */
class UserController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    /**
     * `?role=` is an optional filter added for the Program
     * Coordinator-assignment picker (P0.2) — validated against the fixed
     * Role enum rather than passed straight through, so an unrecognized
     * value is silently ignored (falls back to the unfiltered list)
     * instead of ever reaching Spatie's `role()` scope, which throws for
     * an unknown role name.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $role = $request->query('role');
        $role = in_array($role, Role::values(), true) ? $role : null;
        $perPage = max(1, min((int) $request->query('per_page', 15), 100));

        return UserResource::collection($this->users->list($perPage, $role));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->users->register(UserDTO::fromRequest($request));

        return $this->created(UserResource::make($user));
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $updated = $this->users->update($user, UserDTO::fromRequest($request), $request->user());

        return UserResource::make($updated);
    }

    public function destroy(Request $request, User $user): Response
    {
        $this->users->delete($user, $request->user());

        return $this->noContent();
    }

    public function assignRole(AssignRoleRequest $request, User $user): UserResource
    {
        $updated = $this->users->assignRole($user, AssignRoleDTO::fromRequest($request));

        return UserResource::make($updated);
    }

    public function syncPermissions(SyncUserPermissionsRequest $request, User $user): UserResource
    {
        $updated = $this->users->syncPermissions($user, SyncPermissionsDTO::fromRequest($request));

        return UserResource::make($updated);
    }
}
