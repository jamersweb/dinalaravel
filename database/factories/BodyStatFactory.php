<?php

namespace Database\Factories;

use App\Models\BodyStats;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class BodyStatFactory extends Factory
{
    protected $model = BodyStats::class;

    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'weight' => $this->faker->randomFloat(2, 50, 150),
            'body_fat' => $this->faker->randomFloat(2, 10, 40),
            'lean_body_mass' => $this->faker->randomFloat(2, 40, 100),
            'date' => Carbon::today()->format('Y-m-d'),
        ];
    }
}

