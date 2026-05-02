<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserHabitCompletion extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'habit_list_item_id',
        'completed_date',
    ];

    protected $casts = [
        'completed_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function habitListItem()
    {
        return $this->belongsTo(HabitListItem::class);
    }
}

