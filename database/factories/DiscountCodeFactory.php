<?php

namespace Database\Factories;

use App\Models\DiscountCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class DiscountCodeFactory extends Factory
{
    protected $model = DiscountCode::class;

    public function definition()
    {
        return [
            'code' => strtoupper($this->faker->bothify('???###')),
            'type' => $this->faker->randomElement(['percentage', 'amount']),
            'off_by' => $this->faker->numberBetween(5, 50),
            'valid_products' => null,
            'valid_till' => Carbon::today()->addDays(30),
            'availability' => 'general',
            'status' => 1,
        ];
    }

    public function percentage()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'percentage',
                'off_by' => $this->faker->numberBetween(10, 50),
            ];
        });
    }

    public function amount()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'amount',
                'off_by' => $this->faker->numberBetween(5, 20),
            ];
        });
    }

    public function expired()
    {
        return $this->state(function (array $attributes) {
            return [
                'valid_till' => Carbon::today()->subDays(1),
            ];
        });
    }
}

