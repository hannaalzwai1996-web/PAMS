<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Authorization is handled by the `can:update,user` route middleware
     * (UserPolicy) — not here.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Profile fields only — role and permission changes have their own
     * endpoints/requests (AssignRoleRequest, SyncUserPermissionsRequest).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $targetUserId = $this->route('user')?->id;

        return [
            'name' => ['sometimes', 'string', 'max:150'],
            'email' => ['sometimes', 'email', 'max:191', Rule::unique('users', 'email')->ignore($targetUserId)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
