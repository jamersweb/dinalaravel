<?php

namespace App\Models;

use App\Helpers\FileHandle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomatedMessage extends Model
{
    use HasFactory;

    function getContentAttribute($value){
        if($this->type==='text' || empty($value))
        return $value;
        return FileHandle::getURL($value,3);
    }
    function getContentArAttribute($value){
        if($this->type==='text' || empty($value))
        return $value;
        return FileHandle::getURL($value,3);
    }
}
