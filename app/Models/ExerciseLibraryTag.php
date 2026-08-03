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
        'secondary_categories',
        'training_adaptation',
        'program_role',
        'muscle_group',
        'secondary_muscle_groups',
        'body_regions',
        'exercise_type',
        'exercise_family',
        'movement_direction',
        'stability_demand',
        'variation_type',
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
        'compatibility_flags',
        'regression_exercise_id',
        'progression_exercise_id',
        'alternative_exercise_ids',
        'usage_flags',
        'safety_flags',
        'approved_for_generation',
        'confidence_bucket',
        'review_status',
        'review_blockers',
        'notes',
    ];

    protected $casts = [
        'equipment_tags' => 'array',
        'secondary_categories' => 'array',
        'secondary_muscle_groups' => 'array',
        'body_regions' => 'array',
        'movement_patterns' => 'array',
        'training_styles' => 'array',
        'workout_sections' => 'array',
        'recommended_duration_seconds' => 'integer',
        'recommended_rest_seconds' => 'integer',
        'safety_notes' => 'array',
        'contraindications' => 'array',
        'injury_cautions' => 'array',
        'goal_fit' => 'array',
        'compatibility_flags' => 'array',
        'regression_exercise_id' => 'integer',
        'progression_exercise_id' => 'integer',
        'alternative_exercise_ids' => 'array',
        'usage_flags' => 'array',
        'safety_flags' => 'array',
        'approved_for_generation' => 'boolean',
        'review_blockers' => 'array',
    ];

    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }
}
