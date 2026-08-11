<?php

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $this->getRoleNames(),
            // Permissions granted directly to this user, independent of role.
            'direct_permissions' => $this->getDirectPermissions()->pluck('name'),
            // Everything this user can actually do: role-granted + direct.
            'effective_permissions' => $this->getAllPermissions()->pluck('name'),
            'is_active' => $this->is_active,
        ];
    }
}
