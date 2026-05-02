<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;
    function fullName(){
        $name = UserDetail::where('user_id',$this->user_id)->first(['name','Lastname']);
        return $name->name.' '.$name->Lastname;
    }
    function userImage(){
        return UserDetail::where('user_id',$this->user_id)->pluck('picture')->first();
    }
}
