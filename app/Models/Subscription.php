<?php

namespace App\Models;

use App\Helpers\FileHandle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\SubscriptionFactory::new();
    }

    function getImageAttribute($value){
        if(empty($value)) return null;
        return FileHandle::getURL($value,7);
    }

    function getPriceAttribute($value){
        return number_format($value/100,2);
    }
    function totalClients(){
        return UserDetail::where('subscription',$this->id)->count();
    }
}
