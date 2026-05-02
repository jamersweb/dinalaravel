<?php

namespace Database\Factories;

use App\Models\UserMealPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class UserMealPlanFactory extends Factory
{
    protected $model = UserMealPlan::class;

    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'meal_plan_id' => \App\Models\MealPlan::factory(),
            'subscribe_date' => Carbon::today(),
            'status' => 'active',
        ];
    }

    public function switched()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'switched',
            ];
        });
    }

    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
            ];
        });
    }
}

