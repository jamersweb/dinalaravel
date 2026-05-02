<?php

namespace Database\Factories;

use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition()
    {
        return [
            'title' => $this->faker->words(3, true) . ' Program',
            'discription' => $this->faker->paragraph(),
            'image' => 'test_program_' . time() . '.jpg',
            'level' => $this->faker->randomElement(['beginner', 'intermediate', 'expert']),
            'language' => $this->faker->randomElement(['en', 'ar']),
            'tags' => json_encode([1, 2, 3]),
            'type' => $this->faker->randomElement(['predefined', 'custom']),
            'phases' => $this->faker->numberBetween(1, 4),
        ];
    }

    public function beginner()
    {
        return $this->state(function (array $attributes) {
            return [
                'level' => 'beginner',
            ];
        });
    }

    public function intermediate()
    {
        return $this->state(function (array $attributes) {
            return [
                'level' => 'intermediate',
            ];
        });
    }

    public function expert()
    {
        return $this->state(function (array $attributes) {
            return [
                'level' => 'expert',
            ];
        });
    }
}

