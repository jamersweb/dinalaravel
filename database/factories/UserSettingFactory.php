<?php

namespace Database\Factories;

use App\Models\UserSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserSettingFactory extends Factory
{
    protected $model = UserSetting::class;

    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'language' => $this->faker->randomElement(['en', 'ar']),
            'show_tutorial' => 1,
            'stats_sequence' => json_encode([
                ["icon" => "assets/images/SVG/steps_icon.svg", "item" => "steps"],
                ["icon" => "assets/images/SVG/sleep_icon.svg", "item" => "sleep"],
                ["icon" => "assets/images/SVG/body_weight_icon.svg", "item" => "body weight"],
            ]),
        ];
    }

    public function arabic()
    {
        return $this->state(function (array $attributes) {
            return [
                'language' => 'ar',
            ];
        });
    }

    public function english()
    {
        return $this->state(function (array $attributes) {
            return [
                'language' => 'en',
            ];
        });
    }
}

