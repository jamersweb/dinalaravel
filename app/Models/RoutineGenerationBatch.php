<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoutineGenerationBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_code',
        'status',
        'filters',
        'missing_content_report',
        'validation_report',
        'requested_count',
        'created_count',
        'created_by',
    ];

    protected $casts = [
        'filters' => 'array',
        'missing_content_report' => 'array',
        'validation_report' => 'array',
        'requested_count' => 'integer',
        'created_count' => 'integer',
    ];

    public function routines()
    {
        return $this->hasMany(Workout::class, 'routine_generation_batch_id');
    }
}
