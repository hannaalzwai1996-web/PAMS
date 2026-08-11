<?php

namespace App\Domain\LearningOutcome\Repositories\Contracts;

use App\Domain\LearningOutcome\Models\LearningOutcome;
use App\Domain\Program\Models\Program;
use App\Support\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface LearningOutcomeRepositoryInterface extends RepositoryInterface
{
    /**
     * @return Collection<int, LearningOutcome>
     */
    public function forProgram(Program $program): Collection;

    public function codeExistsForProgram(Program $program, string $code, ?string $excludingId = null): bool;
}
