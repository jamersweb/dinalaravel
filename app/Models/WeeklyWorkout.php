<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeeklyWorkout extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'week_id',
        'workout_id',
        'display_name',
        'section_tag',
        'sort_order',
        'status',
        'done_date',
    ];

    function workoutDetail(){
        return $this->hasOne(Workout::class,'id','workout_id');
    }
}
