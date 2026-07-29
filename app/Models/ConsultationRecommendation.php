<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationRecommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'consultation_form_id',
        'bmr',
        'tdee',
        'recommended_calories',
        'training_level',
        'equipment_category',
        'weekly_workout_frequency',
        'injury_precautions',
        'missing_fields',
        'recommended_routine_ids',
        'recommended_program_ids',
        'calculation_payload',
    ];

    protected $casts = [
        'bmr' => 'decimal:2',
        'tdee' => 'decimal:2',
        'recommended_calories' => 'integer',
        'weekly_workout_frequency' => 'integer',
        'injury_precautions' => 'array',
        'missing_fields' => 'array',
        'recommended_routine_ids' => 'array',
        'recommended_program_ids' => 'array',
        'calculation_payload' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function consultationForm()
    {
        return $this->belongsTo(ConsultationForm::class);
    }
}
