<?php

namespace Database\Factories;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'activity_type' => $this->faker->randomElement(['workout', 'meal', 'bodystat', 'photos', 'steps']),
            'activity_target' => $this->faker->numberBetween(1, 100),
            'date' => $this->faker->date(),
        ];
    }
}

