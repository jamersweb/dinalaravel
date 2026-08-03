<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExerciseLibraryTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'exercise_id',
        'language',
        'equipment_category',
        'equipment_tags',
        'primary_category',
        'training_adaptation',
        'program_role',
        'muscle_group',
        'secondary_muscle_groups',
        'exercise_type',
        'movement_patterns',
        'training_styles',
        'workout_sections',
        'impact_level',
        'intensity_level',
        'video_variant',
        'recommended_duration_seconds',
        'recommended_repetitions',
        'recommended_sets',
        'recommended_rest_seconds',
        'safety_notes',
        'contraindications',
        'difficulty',
        'injury_cautions',
        'goal_fit',
        'usage_flags',
        'safety_flags',
        'approved_for_generation',
        'review_status',
        'notes',
    ];

    protected $casts = [
        'equipment_tags' => 'array',
        'secondary_muscle_groups' => 'array',
        'movement_patterns' => 'array',
        'training_styles' => 'array',
        'workout_sections' => 'array',
        'recommended_duration_seconds' => 'integer',
        'recommended_rest_seconds' => 'integer',
        'safety_notes' => 'array',
        'contraindications' => 'array',
        'injury_cautions' => 'array',
        'goal_fit' => 'array',
        'usage_flags' => 'array',
        'safety_flags' => 'array',
        'approved_for_generation' => 'boolean',
    ];

    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }
}
