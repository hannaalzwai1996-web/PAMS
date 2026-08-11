<?php

namespace App\Models;

use App\Domain\Program\Models\Program;
use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Minimal lookup entity — see docs/database/0001-database-design.md §3.1.
 * Auto-increment PK per ADR-0001 §4 (pure reference data).
 */
#[Fillable(['code', 'name'])]
class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory;

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }
}
