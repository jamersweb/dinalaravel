<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Chat extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\ChatFactory::new();
    }
    
    function userName(){
        $name = UserDetail::where('user_id',$this->user_id)->first(['name','Lastname']);
        if(is_null($name) || is_null($name->name))
            return '-';
        return ($name->name ?? '') . ' ' . ($name->Lastname ?? '');
    }

    function userImage(){
        return UserDetail::where('user_id',$this->user_id)->pluck('picture')->first() ?? null;
    }

    function userSub(){
        $subId = UserDetail::where('user_id',$this->user_id)->pluck('subscription')->first();
        if(is_null($subId))
            return null;
        return Subscription::where('id',$subId)->pluck('name')->first();
    }

    function unreadMsgs($sender){
        return Message::where('chat_id',$this->id)->where('sender',$sender)->where('status',0)->count();
    }

    function lastMessage(){
        $msg = Message::where('chat_id',$this->id)->orderBy('created_at','desc')->first(['content','content_ar','msg_type']);
        return $msg;
    }
}
