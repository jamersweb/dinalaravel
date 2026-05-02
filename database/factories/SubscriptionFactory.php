<?php

namespace Database\Factories;

use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition()
    {
        return [
            'name' => $this->faker->words(3, true) . ' Plan',
            'price' => $this->faker->numberBetween(1000, 10000), // in cents
            'description' => $this->faker->paragraph(),
            'access_type' => $this->faker->randomElement(['half_access', 'full_access']),
            'image' => 'test_product_' . time() . '.jpg',
            'status' => 1,
        ];
    }

    public function fullAccess()
    {
        return $this->state(function (array $attributes) {
            return [
                'access_type' => 'full_access',
            ];
        });
    }

    public function halfAccess()
    {
        return $this->state(function (array $attributes) {
            return [
                'access_type' => 'half_access',
            ];
        });
    }

    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 0,
            ];
        });
    }
}

