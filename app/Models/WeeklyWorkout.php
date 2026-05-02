<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeeklyWorkout extends Model
{
    use HasFactory;
    public $timestamps = false;

    function workoutDetail(){
        return $this->hasOne(Workout::class,'id','workout_id');
    }
}
