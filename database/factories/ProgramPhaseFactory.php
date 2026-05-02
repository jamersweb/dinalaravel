<?php

namespace Database\Factories;

use App\Models\ProgramPhase;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProgramPhaseFactory extends Factory
{
    protected $model = ProgramPhase::class;

    public function definition()
    {
        return [
            'program_id' => \App\Models\Program::factory(),
            'phase_no' => $this->faker->numberBetween(1, 10),
            'weeks' => $this->faker->numberBetween(1, 4),
            'name' => 'Week ' . $this->faker->numberBetween(1, 10) . ' to ' . $this->faker->numberBetween(11, 20),
            'summary' => null, // Can be set explicitly in tests
        ];
    }
}

