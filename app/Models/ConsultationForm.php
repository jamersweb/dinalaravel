<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationForm extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\ConsultationFormFactory::new();
    }

    protected $fillable = [
        'user_id',
        'health_background',
        'injuries',
        'goals',
        'lifestyle_habits',
        'preferred_training_style',
        'fitness_level',
        'medical_concerns',
        'training_experience',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tags()
    {
        return $this->hasManyThrough(Tag::class, UserTag::class, 'user_id', 'id', 'user_id', 'tag_id');
    }
}

