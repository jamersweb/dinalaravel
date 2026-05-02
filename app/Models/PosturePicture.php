<?php

namespace App\Models;

use App\Helpers\FileHandle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosturePicture extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\PosturePictureFactory::new();
    }
    function getFrontPictureAttribute($value){
        if(empty($value)) return null;
        return FileHandle::getURL($value,1);
    }
    function getBackPictureAttribute($value){
        if(empty($value)) return null;
        return FileHandle::getURL($value,1);
    }
    function getSidePictureAttribute($value){
        if(empty($value)) return null;
        return FileHandle::getURL($value,1);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
