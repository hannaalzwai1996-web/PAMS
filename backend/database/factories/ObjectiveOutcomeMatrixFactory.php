<?php

namespace Database\Factories;

use App\Domain\LearningOutcome\Models\LearningOutcome;
use App\Domain\LearningOutcome\Models\ObjectiveOutcomeMatrix;
use App\Domain\LearningOutcome\Models\ProgramObjective;
use App\Support\Enums\MatrixEntrySource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ObjectiveOutcomeMatrix>
 */
class ObjectiveOutcomeMatrixFactory extends Factory
{
    protected $model = ObjectiveOutcomeMatrix::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_objective_id' => ProgramObjective::factory(),
            'learning_outcome_id' => LearningOutcome::factory(),
            'correlation_level' => $this->faker->numberBetween(1, 3),
            'source' => MatrixEntrySource::Manual,
        ];
    }
}
