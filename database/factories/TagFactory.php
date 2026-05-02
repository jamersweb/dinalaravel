<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition()
    {
        return [
            'name' => $this->faker->words(2, true),
            'type' => $this->faker->randomElement(['injury', 'goal', 'medical', 'fitness_level', 'diet']),
            'category' => $this->faker->optional()->word(),
        ];
    }

    public function injury()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'injury',
                'name' => $this->faker->randomElement(['Back Injury', 'Knee Injury', 'Shoulder Injury']),
            ];
        });
    }

    public function goal()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'goal',
                'name' => $this->faker->randomElement(['Weight Loss', 'Muscle Gain', 'Toning', 'Strength']),
            ];
        });
    }

    public function medical()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'medical',
                'name' => $this->faker->randomElement(['PCOS', 'Diabetes', 'Insulin Resistance']),
            ];
        });
    }

    public function fitnessLevel()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'fitness_level',
                'name' => $this->faker->randomElement(['Beginner Level', 'Intermediate Level', 'Advanced Level']),
            ];
        });
    }
}

