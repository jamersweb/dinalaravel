<?php

namespace App\Models;

use App\Helpers\FileHandle;
use App\Models\Concerns\AvailableInContentLocale;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workout extends Model
{
    use AvailableInContentLocale;
    use HasFactory;

    protected $casts = [
        'locale_translations' => 'array',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\WorkoutFactory::new();
    }
    function workoutExercises() {
        return $this->hasMany(WorkoutExercise::class,'workout_id','id');
    }

    function getImageAttribute($value){
        if (strlen($value) === 11) {
            return config('app.youtube_thumbnail_baseUrl_start').$value.config('app.youtube_thumbnail_baseUrl_end');
        }
        if(empty($value)) return null;
        return FileHandle::getURL($value,4);
    }
}
