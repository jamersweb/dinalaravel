<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MealPhotoComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'meal_photo_id',
        'user_id',
        'comment',
    ];

    public function mealPhoto()
    {
        return $this->belongsTo(MealPhoto::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

