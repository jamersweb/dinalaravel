<?php

namespace Database\Factories;

use App\Models\MealPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class MealPlanFactory extends Factory
{
    protected $model = MealPlan::class;

    public function definition()
    {
        return [
            'name' => $this->faker->words(3, true) . ' Meal Plan',
            'description' => $this->faker->paragraph(),
            'image' => 'test_mealplan_' . time() . '.jpg',
            'language' => $this->faker->randomElement(['en', 'ar']),
            'tags' => json_encode([1, 2]),
            'status' => 1,
        ];
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

