<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MealPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'photo_url',
        'meal_details',
        'meal_date',
    ];

    protected $casts = [
        'meal_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(MealPhotoComment::class);
    }
}

