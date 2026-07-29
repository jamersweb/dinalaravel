<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiProgramWeekDay extends Model
{
    use HasFactory;

    public const TYPE_WORKOUT = 'workout';
    public const TYPE_REST = 'rest';
    public const TYPE_ACTIVE_RECOVERY = 'active_recovery';

    protected $fillable = [
        'program_id',
        'program_phase_id',
        'week_id',
        'workout_id',
        'week_no',
        'day_no',
        'day_type',
        'display_name',
        'estimated_minutes',
        'training_style',
        'muscle_groups',
        'progression_notes',
        'recovery_guidance',
        'validation_errors',
    ];

    protected $casts = [
        'week_no' => 'integer',
        'day_no' => 'integer',
        'estimated_minutes' => 'integer',
        'muscle_groups' => 'array',
        'progression_notes' => 'array',
        'recovery_guidance' => 'array',
        'validation_errors' => 'array',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function programPhase()
    {
        return $this->belongsTo(ProgramPhase::class);
    }

    public function week()
    {
        return $this->belongsTo(WeekWiseProgram::class, 'week_id');
    }

    public function workout()
    {
        return $this->belongsTo(Workout::class);
    }
}
