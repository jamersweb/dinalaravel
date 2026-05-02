<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HabitListItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'habit_list_id',
        'habit_name',
        'order',
    ];

    public function habitList()
    {
        return $this->belongsTo(HabitList::class);
    }

    public function completions()
    {
        return $this->hasMany(UserHabitCompletion::class);
    }
}

