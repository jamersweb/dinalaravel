<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition()
    {
        return [
            'amount' => $this->faker->randomFloat(2, 10, 100),
            'intent' => 'pi_' . $this->faker->unique()->bothify('########'),
            'card_used' => 'visa 4242',
            'stripe_reponse' => json_encode(['id' => 'pi_test']),
            'status' => 'success',
        ];
    }

    public function failed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'failed',
            ];
        });
    }
}

