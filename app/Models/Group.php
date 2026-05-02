<?php

namespace App\Models;

use App\Helpers\FileHandle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Group extends Model
{
    use HasFactory;

    function unreadMsgs(){
        return GroupMessage::where('group_id',$this->id)->where('from','!=',Auth::id())->where('status',0)->count();
    }

    function memberCount(){
        return GroupMember::where('group_id',$this->id)->where('role','member')->count();
    }
    function getImageAttribute($name){
        if(empty($name)) return null;
        return FileHandle::getURL($name,3);
    }
    function lastMessage(){
        return GroupMessage::where('group_id',$this->id)->orderBy('id','desc')->first(['content','content_ar','msg_type']);
    }
}
