<?php

namespace App\Models;

use App\Helpers\FileHandle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\MessageFactory::new();
    }

    function getFileUrlAttribute($name){
        if($this->msg_type==='text' || empty($name))
        return null;
        return FileHandle::getURL($name,3);
    }
}
