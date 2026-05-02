<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\FileHandle;

class Podcast extends Model
{
    use HasFactory;
    function getFileAttribute($value){
        if(empty($value)) return null;
        return FileHandle::getURL($value,8);
    }
}
