<?php

namespace Database\Factories;

use App\Models\Workout;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkoutFactory extends Factory
{
    protected $model = Workout::class;

    public function definition()
    {
        return [
            'title' => $this->faker->words(3, true) . ' Workout',
            'type' => $this->faker->randomElement(['simple', 'circuit', 'superset']),
            'category' => 'master',
            'instructions' => $this->faker->sentence(),
            'daily_summary' => null, // Can be set explicitly in tests
            'image' => null,
            'tags' => null,
            'user_id' => null,
        ];
    }
}

