<?php

namespace Database\Factories;

use App\Models\ProgramPhaseWorkout;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProgramPhaseWorkoutFactory extends Factory
{
    protected $model = ProgramPhaseWorkout::class;

    public function definition()
    {
        return [
            'program_phase_id' => \App\Models\ProgramPhase::factory(),
            'workout_id' => \App\Models\Workout::factory(),
            'order' => $this->faker->numberBetween(1, 10),
        ];
    }
}

