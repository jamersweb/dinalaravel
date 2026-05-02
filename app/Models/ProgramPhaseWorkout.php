<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramPhaseWorkout extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\ProgramPhaseWorkoutFactory::new();
    }
    function workoutDetail(){
        return $this->hasOne(Workout::class,'id','workout_id');
    }
}
