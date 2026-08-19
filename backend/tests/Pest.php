<?php

use App\Domain\LearningOutcome\Models\LearningOutcome;
use App\Domain\LearningOutcome\Models\ProgramObjective;
use App\Domain\Program\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Headers that make Sanctum treat a test request as coming from the SPA's
 * stateful origin (see EnsureFrontendRequestsAreStateful::fromFrontend()),
 * so session-based auth middleware actually runs during feature tests.
 *
 * @return array<string, string>
 */
function spaHeaders(): array
{
    return [
        'Origin' => 'http://localhost:5173',
        'Accept' => 'application/json',
    ];
}

/**
 * Creates a user with the `admin` role. Assumes RoleSeeder has already run
 * (`$this->seed(RoleSeeder::class)` or PermissionSeeder, which seeds it too).
 */
function makeAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    return $admin;
}

/**
 * Creates a user with the `program_coordinator` role, assigned to the
 * given program (\App\Domain\Program\Models\Program).
 */
function makeCoordinatorFor(Program $program): User
{
    $coordinator = User::factory()->create();
    $coordinator->assignRole('program_coordinator');
    $program->coordinators()->attach($coordinator);

    return $coordinator;
}

/**
 * Creates a user with the `qa_officer` role, not attached to any program.
 */
function makeQaOfficer(): User
{
    $qaOfficer = User::factory()->create();
    $qaOfficer->assignRole('qa_officer');

    return $qaOfficer;
}

/**
 * Seeds two Program Objectives and two Learning Outcomes on the given
 * program: one PEO/PLO pair with deliberate lexical overlap (for
 * PoPloMatrixService's auto-generation to find) and one pair with none.
 *
 * @return array{overlapping: ProgramObjective, unrelated: ProgramObjective, matchingOutcome: LearningOutcome, nonMatchingOutcome: LearningOutcome}
 */
function seedMatrixFixtures(Program $program): array
{
    $overlapping = $program->objectives()->create([
        'code' => 'PEO1',
        'statement' => 'Graduates will apply engineering analysis techniques to solve complex design problems.',
    ]);
    $unrelated = $program->objectives()->create([
        'code' => 'PEO2',
        'statement' => 'Graduates will demonstrate ethical leadership in professional practice.',
    ]);

    $matchingOutcome = $program->learningOutcomes()->create([
        'code' => 'PLO1',
        'statement' => 'Students apply engineering analysis techniques when solving complex design problems.',
        'category' => 'C',
    ]);
    $nonMatchingOutcome = $program->learningOutcomes()->create([
        'code' => 'PLO2',
        'statement' => 'Students communicate technical results clearly in written reports.',
        'category' => 'D',
    ]);

    return compact('overlapping', 'unrelated', 'matchingOutcome', 'nonMatchingOutcome');
}
