<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkoutExercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'workout_id', 'exercise_id', 'sets', 'reps', 'reps_type', 'time',
        'rest_period', 'description', 'sets_rounds', 'category',
        'group_id', 'group_type', 'group_order',
    ];

    protected $casts = [
        'rest_period' => 'integer',
        'group_order' => 'integer',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\WorkoutExerciseFactory::new();
    }

    function exerciseDetail(){
        return $this->hasOne(Exercise::class,'id','exercise_id');
    }

    /**
     * Format rest period (seconds) for display: "15 sec", "2 min", "1 min 30 sec"
     */
    public static function formatRestPeriod(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0 sec';
        }
        if ($seconds < 60) {
            return $seconds . ' sec';
        }
        $mins = intval($seconds / 60);
        $secs = $seconds % 60;
        if ($secs === 0) {
            return $mins . ' min';
        }
        return $mins . ' min ' . $secs . ' sec';
    }
}
