<?php

namespace App\Providers;

use App\Domain\Department\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Domain\Department\Repositories\DepartmentRepository;
use App\Domain\LearningOutcome\Repositories\Contracts\LearningOutcomeRepositoryInterface;
use App\Domain\LearningOutcome\Repositories\Contracts\MatrixRepositoryInterface;
use App\Domain\LearningOutcome\Repositories\Contracts\ProgramObjectiveRepositoryInterface;
use App\Domain\LearningOutcome\Repositories\LearningOutcomeRepository;
use App\Domain\LearningOutcome\Repositories\MatrixRepository;
use App\Domain\LearningOutcome\Repositories\ProgramObjectiveRepository;
use App\Domain\Program\Repositories\Contracts\ProgramRepositoryInterface;
use App\Domain\Program\Repositories\ProgramRepository;
use App\Domain\User\Repositories\Contracts\UserRepositoryInterface;
use App\Domain\User\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Binds each domain's Repository interface to its Eloquent implementation.
 *
 * Services type-hint the interface (e.g. ProgramRepositoryInterface), never
 * the concrete class, so this is the single place that wires the two
 * together (ADR-0001 §2.4, §12). Add one binding per domain repository as
 * each module is implemented.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        UserRepositoryInterface::class => UserRepository::class,
        ProgramObjectiveRepositoryInterface::class => ProgramObjectiveRepository::class,
        LearningOutcomeRepositoryInterface::class => LearningOutcomeRepository::class,
        MatrixRepositoryInterface::class => MatrixRepository::class,
        DepartmentRepositoryInterface::class => DepartmentRepository::class,
        ProgramRepositoryInterface::class => ProgramRepository::class,
    ];
}
