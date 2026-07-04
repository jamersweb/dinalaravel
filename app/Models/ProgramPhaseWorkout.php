<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramPhaseWorkout extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_phase_id',
        'workout_id',
        'display_name',
        'section_tag',
        'sort_order',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\ProgramPhaseWorkoutFactory::new();
    }
    function workoutDetail(){
        return $this->hasOne(Workout::class,'id','workout_id');
    }
}
