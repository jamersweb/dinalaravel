<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HabitFolder extends Model
{
    use HasFactory;

    function habits(){
        return $this->hasMany(Habit::class,'habit_folder_id','id');
    }
}
