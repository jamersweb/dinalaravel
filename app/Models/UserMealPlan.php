<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMealPlan extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\UserMealPlanFactory::new();
    }

    function planDetail(){
        return $this->hasOne(MealPlan::class,'id','meal_plan_id');
    }
}
