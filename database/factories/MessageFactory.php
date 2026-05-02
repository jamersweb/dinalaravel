<?php

namespace Database\Factories;

use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition()
    {
        return [
            'chat_id' => \App\Models\Chat::factory(),
            'sender' => $this->faker->randomElement(['user', 'admin']),
            'content' => $this->faker->sentence(),
            'content_ar' => $this->faker->sentence(),
            'msg_type' => 'text',
            'status' => 0,
        ];
    }
}

