<?php

namespace Database\Seeders;

use App\Support\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role as RoleModel;

/**
 * Seeds the fixed, immutable role set defined in ADR-0001 §8 / §12. Exactly
 * these three roles may exist — adding a fourth requires an ADR amendment,
 * not a change to this seeder alone.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Role::values() as $role) {
            RoleModel::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }
}
