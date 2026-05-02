<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserHabitAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'habit_list_id',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function habitList()
    {
        return $this->belongsTo(HabitList::class);
    }
}

