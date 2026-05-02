<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\QuestionFactory::new();
    }
    function getOptionsEnAttribute($value){
        return json_decode($value);
    }

    function getOptionsArAttribute($value){
        return json_decode($value);
    }
}
