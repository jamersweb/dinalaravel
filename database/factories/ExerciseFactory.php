<?php

namespace Database\Factories;

use App\Models\Exercise;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExerciseFactory extends Factory
{
    protected $model = Exercise::class;

    public function definition()
    {
        return [
            'title' => $this->faker->words(3, true) . ' Exercise',
            'type' => $this->faker->randomElement(['strength', 'cardio', 'flexibility']),
            'exercise_type' => $this->faker->randomElement(['Strength-reps and weights', 'Cardio-time based', 'Body weight']),
            'instructions' => $this->faker->optional()->paragraph(),
            'weights' => $this->faker->optional()->paragraph(),
            'language' => $this->faker->randomElement(['English', 'Arabic']),
            'video_type' => $this->faker->randomElement(['youtube', 'local']),
            'video_url' => $this->faker->optional()->regexify('[A-Za-z0-9]{11}'), // YouTube video ID format
            'image' => $this->faker->optional()->regexify('[A-Za-z0-9]{11}'), // YouTube thumbnail ID format
            'video_duration' => $this->faker->optional()->numberBetween(30, 600),
            'tags' => null,
            'alternates' => null,
            'rest_period' => $this->faker->optional()->numberBetween(30, 120),
        ];
    }
}

