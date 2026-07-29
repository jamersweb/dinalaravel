<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientRoutineAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'consultation_recommendation_id',
        'workout_id',
        'status',
        'assigned_by',
        'assigned_at',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function workout()
    {
        return $this->belongsTo(Workout::class);
    }

    public function recommendation()
    {
        return $this->belongsTo(ConsultationRecommendation::class, 'consultation_recommendation_id');
    }
}
