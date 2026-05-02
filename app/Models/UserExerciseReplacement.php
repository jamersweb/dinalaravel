<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserExerciseReplacement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'workout_id',
        'original_exercise_id',
        'alternate_exercise_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function workout()
    {
        return $this->belongsTo(Workout::class);
    }

    public function originalExercise()
    {
        return $this->belongsTo(Exercise::class, 'original_exercise_id');
    }

    public function alternateExercise()
    {
        return $this->belongsTo(Exercise::class, 'alternate_exercise_id');
    }
}

