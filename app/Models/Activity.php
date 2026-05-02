<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\ActivityFactory::new();
    }

    function userImage(){
        return UserDetail::where('user_id',$this->source)->pluck('picture')->first();
    }

    function userName(){
        $name = UserDetail::where('user_id',$this->source)->first(['name','Lastname']);
        if ($name === null) {
            return '';
        }
        return trim(($name->name ?? '').' '.($name->Lastname ?? ''));
    }
}
