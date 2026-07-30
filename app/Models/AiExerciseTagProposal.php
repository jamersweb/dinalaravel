<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiExerciseTagProposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'exercise_id',
        'provider',
        'model',
        'status',
        'source_metadata',
        'current_tag_payload',
        'proposed_payload',
        'confidence',
        'reasoning',
        'raw_response',
        'error_message',
        'generated_by',
        'reviewed_by',
        'generated_at',
        'reviewed_at',
    ];

    protected $casts = [
        'source_metadata' => 'array',
        'current_tag_payload' => 'array',
        'proposed_payload' => 'array',
        'confidence' => 'float',
        'generated_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }
}
