<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserSetting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'name' => $this->faker->firstName(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('User@Password123'),
            'role' => 1, // 1 = User, 2 = Admin
            'status' => 'active',
            'api_token' => Str::random(60),
            'fcm_token' => Str::random(100),
            'email_verification_code' => null,
            'code_expire_time' => null,
        ];
    }

    public function admin()
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 2,
            ];
        });
    }

    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'email_verification_code' => rand(1000, 9999),
                'code_expire_time' => now()->addMinutes(5),
            ];
        });
    }

    public function withDetails()
    {
        return $this->afterCreating(function (User $user) {
            UserDetail::factory()->create(['user_id' => $user->id]);
            UserSetting::factory()->create(['user_id' => $user->id]);
        });
    }
}

