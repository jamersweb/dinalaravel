<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSub extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected static function newFactory()
    {
        return \Database\Factories\UserSubFactory::new();
    }

    function payment(){
        return $this->hasOne(Payment::class,'id','payment_id');
    }

    function clientName(){
        return User::where('id',$this->user_id)->pluck('name')->first();
    }

    function subscription(){
        return Subscription::where('id',$this->sub_id)->pluck('name')->first();
    }
}
