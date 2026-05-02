<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserHabit extends Model
{
    use HasFactory;

    function habitName(){
        return Habit::where('id',$this->habit_id)->pluck('title')->first();
    }
}
