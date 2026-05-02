<?php

namespace App\Models;

use App\Helpers\FileHandle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attatchment extends Model
{
    use HasFactory;
    function getFileAttribute($value){
        if(empty($value)) return null;
        return FileHandle::getURL($value,6);
    }
}
