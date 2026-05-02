<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkoutCompilation extends Model
{
    use HasFactory;
    protected $fillable = ['workout_id','user_id'];
}
