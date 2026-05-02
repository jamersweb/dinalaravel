<?php

namespace App\Models;

use App\Helpers\FileHandle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Food extends Model
{
    use HasFactory;
    protected $table = 'foods';
    function getImageAttribute($value){
        if(is_null($value) || empty($value))
        return $value;
        return FileHandle::getURL($value,9);
    }
}
