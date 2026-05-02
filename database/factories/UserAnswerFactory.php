<?php

namespace Database\Factories;

use App\Models\UserAnswer;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserAnswerFactory extends Factory
{
    protected $model = UserAnswer::class;

    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'question_id' => \App\Models\Question::factory(),
            'answer' => $this->faker->sentence(),
            'answer_type' => 'single',
        ];
    }

    public function multiple()
    {
        return $this->state(function (array $attributes) {
            return [
                'answer_type' => 'multiple',
                'answer' => json_encode([$this->faker->word(), $this->faker->word()]),
            ];
        });
    }
}

