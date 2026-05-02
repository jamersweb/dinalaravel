<?php

namespace Database\Factories;

use App\Models\UserStep;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class UserStepFactory extends Factory
{
    protected $model = UserStep::class;

    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'steps' => $this->faker->numberBetween(1000, 20000),
            'distance' => $this->faker->randomFloat(2, 0.5, 15),
            'burn_calories' => $this->faker->numberBetween(50, 500),
            'time' => $this->faker->numberBetween(1800, 7200),
            'date' => Carbon::today()->format('Y-m-d H:i:s'),
            'unit' => 'km',
        ];
    }
}

