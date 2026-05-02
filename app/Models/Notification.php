<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    function userImage(){
        return UserDetail::where('user_id',$this->source)->pluck('picture')->first();
    }

    function userName(){
        $name = UserDetail::where('user_id',$this->source)->first(['name','Lastname']);
        if(is_null($name))
        return '-';
        return $name->name.' '.$name->Lastname;
    }
}
