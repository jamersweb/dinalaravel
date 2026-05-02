<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NutritionCompilance extends Model
{
    use HasFactory;
    protected $fillable = ['user_id','meal_id','meal_plan_id','calories','carbs','fats','proteins'];
}
