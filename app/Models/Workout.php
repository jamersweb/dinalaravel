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

    protected $fillable = [
        'content_code',
        'user_id',
        'title',
        'category',
        'equipment_category',
        'fitness_level',
        'workout_type',
        'routine_source',
        'routine_status',
        'type',
        'tags',
        'instructions',
        'daily_summary',
        'routine_sections',
        'routine_validation_errors',
        'review_notes',
        'image',
        'language',
        'locale_translations',
        'approved_at',
        'approved_by',
        'reviewed_at',
        'reviewed_by',
        'routine_generation_batch_id',
    ];

    protected $casts = [
        'locale_translations' => 'array',
        'routine_sections' => 'array',
        'routine_validation_errors' => 'array',
        'approved_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\WorkoutFactory::new();
    }
    function workoutExercises() {
        return $this->hasMany(WorkoutExercise::class,'workout_id','id');
    }

    function routineGenerationBatch() {
        return $this->belongsTo(RoutineGenerationBatch::class, 'routine_generation_batch_id');
    }

    function getImageAttribute($value){
        if (empty($value)) return null;
        if (strlen($value) === 11) {
            return config('app.youtube_thumbnail_baseUrl_start').$value.config('app.youtube_thumbnail_baseUrl_end');
        }
        return FileHandle::getURL($value,4);
    }
}
