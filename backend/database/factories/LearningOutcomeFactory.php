<?php

namespace Database\Factories;

use App\Domain\LearningOutcome\Models\LearningOutcome;
use App\Domain\Program\Models\Program;
use App\Support\Enums\LearningOutcomeCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearningOutcome>
 */
class LearningOutcomeFactory extends Factory
{
    protected $model = LearningOutcome::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'code' => 'PLO'.$this->faker->unique()->numberBetween(1, 999),
            'statement' => $this->faker->sentence(12),
            'category' => $this->faker->randomElement(LearningOutcomeCategory::cases()),
        ];
    }
}
