<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'old_program_id',
        'new_program_id',
        'progress_snapshot',
        'switched_at',
    ];

    protected $casts = [
        'progress_snapshot' => 'array',
        'switched_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function oldProgram()
    {
        return $this->belongsTo(Program::class, 'old_program_id');
    }

    public function newProgram()
    {
        return $this->belongsTo(Program::class, 'new_program_id');
    }
}

