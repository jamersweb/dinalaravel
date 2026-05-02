<?php

namespace Database\Factories;

use App\Models\ConsultationForm;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsultationFormFactory extends Factory
{
    protected $model = ConsultationForm::class;

    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'health_background' => $this->faker->sentence(),
            'injuries' => $this->faker->sentence(),
            'goals' => $this->faker->randomElement(['lose weight', 'muscle gain', 'toning', 'strength']),
            'lifestyle_habits' => $this->faker->sentence(),
            'preferred_training_style' => $this->faker->randomElement(['cardio', 'strength', 'hiit', 'yoga']),
            'fitness_level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced']),
            'medical_concerns' => $this->faker->optional()->sentence(),
            'training_experience' => $this->faker->sentence(),
            'completed_at' => now(),
        ];
    }

    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'completed_at' => now(),
            ];
        });
    }

    public function incomplete()
    {
        return $this->state(function (array $attributes) {
            return [
                'completed_at' => null,
            ];
        });
    }
}

