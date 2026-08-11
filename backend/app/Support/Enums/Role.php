<?php

namespace App\Support\Enums;

/**
 * The fixed, immutable set of roles defined in ADR-0001 §8/§12. Adding a
 * fourth role requires an ADR amendment, not just a new enum case — every
 * place that assumes "exactly these three" (RoleSeeder, PermissionSeeder,
 * Policies) reads from here so there is exactly one source of truth for
 * the role slugs.
 */
enum Role: string
{
    case Admin = 'admin';
    case QualityAssuranceOfficer = 'qa_officer';
    case ProgramCoordinator = 'program_coordinator';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $role) => $role->value, self::cases());
    }
}
