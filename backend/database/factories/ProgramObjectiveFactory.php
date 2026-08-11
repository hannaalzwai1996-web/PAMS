<?php

namespace Database\Factories;

use App\Domain\LearningOutcome\Models\ProgramObjective;
use App\Domain\Program\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgramObjective>
 */
class ProgramObjectiveFactory extends Factory
{
    protected $model = ProgramObjective::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'code' => 'PEO'.$this->faker->unique()->numberBetween(1, 999),
            'statement' => $this->faker->sentence(12),
        ];
    }
}
