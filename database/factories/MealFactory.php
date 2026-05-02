<?php

namespace Database\Factories;

use App\Models\Meal;
use Illuminate\Database\Eloquent\Factories\Factory;

class MealFactory extends Factory
{
    protected $model = Meal::class;

    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'name' => $this->faker->words(3, true) . ' Meal',
            'prep_time' => $this->faker->numberBetween(5, 30) . ' mins',
            'cook_time' => $this->faker->numberBetween(10, 60) . ' mins',
            'suitable_for' => $this->faker->randomElement(['Weight Loss', 'Muscle Gain', 'Maintenance']),
            'tags' => json_encode([1, 2]),
            'file' => 'test_meal_' . time() . '.jpg',
            'file_type' => 'image',
            'no_of_servings' => $this->faker->numberBetween(1, 4),
            'calories_per_serving' => $this->faker->numberBetween(200, 600),
            'protein_per_serving' => $this->faker->numberBetween(10, 50),
            'carbs_per_serving' => $this->faker->numberBetween(20, 80),
            'fat_per_serving' => $this->faker->numberBetween(5, 30),
            'fiber_per_serving' => $this->faker->numberBetween(2, 15),
            'ingredients' => $this->faker->sentence(),
            'directions' => $this->faker->paragraph(),
            'meal_type' => $this->faker->randomElement(['auto', 'manual']),
            'language' => $this->faker->randomElement(['en', 'ar']),
        ];
    }
}

