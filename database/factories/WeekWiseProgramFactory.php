<?php

namespace Database\Factories;

use App\Models\WeekWiseProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

class WeekWiseProgramFactory extends Factory
{
    protected $model = WeekWiseProgram::class;

    public function definition()
    {
        return [
            'program_sub_id' => \App\Models\ProgramSub::factory(),
            'week_no' => $this->faker->numberBetween(1, 12),
            'status' => 0,
        ];
    }
}

