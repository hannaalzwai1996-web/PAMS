<?php

namespace Database\Factories;

use App\Domain\Program\Models\Program;
use App\Models\Department;
use App\Support\Enums\ProgramStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    protected $model = Program::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'code' => strtoupper($this->faker->unique()->bothify('PRG-###')),
            'name' => $this->faker->words(4, true),
            'level' => $this->faker->randomElement(['diploma', 'bachelor', 'master', 'phd']),
            'description' => $this->faker->paragraph(),
            'duration_years' => $this->faker->numberBetween(1, 5),
            'status' => ProgramStatus::Draft,
            'current_version_no' => 1,
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => ProgramStatus::Approved]);
    }
}
