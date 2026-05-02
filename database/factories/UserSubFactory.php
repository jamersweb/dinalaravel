<?php

namespace Database\Factories;

use App\Models\UserSub;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class UserSubFactory extends Factory
{
    protected $model = UserSub::class;

    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'sub_id' => \App\Models\Subscription::factory(),
            'payment_id' => null,
            'status' => 'active',
            'sub_start_date' => Carbon::today(),
            'sub_expire_date' => Carbon::today()->addDays(30),
            'discount_code' => null,
            'discount_code_status' => null,
        ];
    }

    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'active',
                'sub_expire_date' => Carbon::today()->addDays(30),
            ];
        });
    }

    public function cancelled()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'cancelled',
            ];
        });
    }

    public function expired()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'expired',
                'sub_expire_date' => Carbon::today()->subDays(1),
            ];
        });
    }

    public function upgraded()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'upgraded',
            ];
        });
    }
}

