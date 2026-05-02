<?php

namespace App\Models;

use App\Helpers\FileHandle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMessage extends Model
{
    use HasFactory;
    function userName(){
        $name = UserDetail::where('user_id',$this->from)->first(['name','Lastname']);
        return $name->name.' '.$name->Lastname;
    }
    function getFileUrlAttribute($name){
        if($this->msg_type==='text' || empty($name))
        return null;
        return FileHandle::getURL($name,3);
    }
}
