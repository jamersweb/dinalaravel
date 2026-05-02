<?php

namespace Database\Factories;

use App\Models\WorkoutExercise;
use App\Models\Workout;
use App\Models\Exercise;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkoutExerciseFactory extends Factory
{
    protected $model = WorkoutExercise::class;

    public function definition()
    {
        return [
            'workout_id' => Workout::factory(),
            'exercise_id' => Exercise::factory(),
            'sets' => $this->faker->numberBetween(1, 5),
            'reps' => $this->faker->numberBetween(5, 20),
            'reps_type' => 'text', // 'text' for repetitions, 'time' for time-based exercises
            'time' => null,
            'rest_period' => $this->faker->numberBetween(30, 90),
            'description' => null,
            'sets_rounds' => null,
            'category' => 'simple', // Can be set explicitly in tests (warm_up, strength, cool_down, etc.)
        ];
    }
}

