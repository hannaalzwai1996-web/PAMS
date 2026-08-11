<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SyncUserPermissionsRequest extends FormRequest
{
    /**
     * Authorization is handled by the `can:managePermissions,user` route
     * middleware (UserPolicy) — not here.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The full desired set, replacing whatever direct permissions the user
     * currently holds (this does not touch permissions they have via their
     * role). Each slug must already exist in the fixed catalog seeded by
     * PermissionSeeder — this endpoint assigns permissions, it does not
     * create new ones.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', 'distinct', 'exists:permissions,name'],
        ];
    }
}
