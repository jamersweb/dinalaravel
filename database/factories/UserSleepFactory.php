<?php

namespace Database\Factories;

use App\Models\UserSleep;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class UserSleepFactory extends Factory
{
    protected $model = UserSleep::class;

    public function definition()
    {
        $bedTime = Carbon::today()->setTime(22, 0);
        $wakeTime = Carbon::today()->addDay()->setTime(6, 0);

        return [
            'user_id' => \App\Models\User::factory(),
            'bed_time' => $bedTime->format('H:i:s'),
            'bed_date' => $bedTime->format('Y-m-d'),
            'wakeup_time' => $wakeTime->format('H:i:s'),
            'wakeup_date' => $wakeTime->format('Y-m-d'),
            'date' => Carbon::today()->format('Y-m-d'),
        ];
    }
}

