<?php

namespace Database\Factories;

use App\Models\WeeklyWorkout;
use Illuminate\Database\Eloquent\Factories\Factory;

class WeeklyWorkoutFactory extends Factory
{
    protected $model = WeeklyWorkout::class;

    public function definition()
    {
        return [
            'week_id' => \App\Models\WeekWiseProgram::factory(),
            'workout_id' => \App\Models\Workout::factory(),
            'status' => 0,
        ];
    }
}

