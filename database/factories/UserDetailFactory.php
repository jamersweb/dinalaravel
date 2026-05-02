<?php

namespace Database\Factories;

use App\Models\UserDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserDetailFactory extends Factory
{
    protected $model = UserDetail::class;

    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'name' => $this->faker->firstName(),
            'Lastname' => $this->faker->lastName(),
            'phone' => $this->faker->phoneNumber(),
            'DOB' => $this->faker->date('Y-m-d', '-18 years'),
            'country' => $this->faker->country(),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'height' => $this->faker->numberBetween(150, 200) . ' cm',
            'picture' => null,
            'subscription' => null,
            'subscription_status' => null,
        ];
    }

    public function withActiveSubscription()
    {
        return $this->state(function (array $attributes) {
            return [
                'subscription_status' => 'active',
            ];
        });
    }

    public function withExpiredSubscription()
    {
        return $this->state(function (array $attributes) {
            return [
                'subscription_status' => 'expired',
            ];
        });
    }
}

