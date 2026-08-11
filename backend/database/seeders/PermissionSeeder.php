<?php

namespace Database\Seeders;

use App\Support\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * The permission catalog referenced in docs/architecture/0001-authentication-architecture.md §5.
 *
 * Only `users.manage` is enforced by a route today (Admin/UserController).
 * The rest are seeded now as fixed reference data so each domain module
 * (Program, LearningOutcome, CourseMapping, Accreditation, QualityReport)
 * picks up an already-agreed permission slug instead of inventing one
 * ad hoc when it's built.
 */
class PermissionSeeder extends Seeder
{
    /**
     * @var array<string, array<int, Role>>
     */
    private const CATALOG = [
        'users.manage' => [Role::Admin],

        'programs.create' => [Role::ProgramCoordinator],
        'programs.edit' => [Role::ProgramCoordinator],
        'programs.submit' => [Role::ProgramCoordinator],
        'programs.review' => [Role::QualityAssuranceOfficer],
        'programs.view-any' => [Role::Admin, Role::QualityAssuranceOfficer],

        'program-objectives.manage' => [Role::ProgramCoordinator],
        'learning-outcomes.manage' => [Role::ProgramCoordinator],
        'course-mappings.manage' => [Role::ProgramCoordinator],

        'accreditation.manage' => [Role::Admin, Role::QualityAssuranceOfficer],

        'quality-reports.create' => [Role::ProgramCoordinator],
        'quality-reports.submit' => [Role::ProgramCoordinator],
        'quality-reports.review' => [Role::QualityAssuranceOfficer],

        'reports.export' => [Role::QualityAssuranceOfficer, Role::ProgramCoordinator],

        'dashboard.view' => [Role::Admin, Role::QualityAssuranceOfficer],
    ];

    public function run(): void
    {
        $roles = RoleModel::whereIn('name', Role::values())->get()->keyBy('name');

        foreach (self::CATALOG as $slug => $grantedTo) {
            $permission = Permission::firstOrCreate(['name' => $slug, 'guard_name' => 'web']);

            foreach ($grantedTo as $role) {
                $roles[$role->value]->givePermissionTo($permission);
            }
        }

        // Bulk-seeded permissions don't always fire the model events Spatie
        // relies on to invalidate its cache — force it so newly seeded
        // permissions are visible to Gate::check() immediately.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
