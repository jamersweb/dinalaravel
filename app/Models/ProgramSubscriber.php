<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramSubscriber extends Model
{
    use HasFactory;
    protected $fillable = ['program_id','user_id','status'];

    function user(){
        return $this->hasOne(User::class,'id','user_id');
    }

    function userName(){
        $x = UserDetail::where('user_id',$this->user_id)->first(['name','Lastname']);
        return $x->name.' '.$x->Lastname;
    }
}
