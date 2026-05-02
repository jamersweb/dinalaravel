<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeekWiseProgram extends Model
{
    use HasFactory;
    public $timestamps = false;

    function weeklyWorkouts(){
        return WeeklyWorkout::where('week_id',$this->id)->get();
    }
}
