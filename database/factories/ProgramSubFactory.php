<?php

namespace Database\Factories;

use App\Models\ProgramSub;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class ProgramSubFactory extends Factory
{
    protected $model = ProgramSub::class;

    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'program_id' => \App\Models\Program::factory(),
            'subscribe_date' => Carbon::today(),
            'status' => 'subscribed',
            'start_date' => null,
            'pause_date' => null,
            'resume_date' => null,
            'complete_date' => null,
        ];
    }

    public function inProgress()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'in-progress',
                'start_date' => Carbon::today()->subWeeks(2),
            ];
        });
    }

    public function paused()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'paused',
                'start_date' => Carbon::today()->subWeeks(2),
                'pause_date' => Carbon::today()->subWeek(),
            ];
        });
    }

    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'start_date' => Carbon::today()->subWeeks(8),
                'complete_date' => Carbon::today(),
            ];
        });
    }
}

