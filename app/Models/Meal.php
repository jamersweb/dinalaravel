<?php

namespace App\Models;

use App\Helpers\FileHandle;
use App\Models\Concerns\AvailableInContentLocale;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meal extends Model
{
    use HasFactory, AvailableInContentLocale;

    protected $casts = [
        'locale_translations' => 'array',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\MealFactory::new();
    }
    public function getFileAttribute($value){
        if(empty($value)) return null;
        return FileHandle::getURL($value,5);
    }
    function getVideoThumbnailAttribute($value){
        if($this->file_type==='image' || empty($value))
        return null;
        return FileHandle::getURL($value,5);
    }
}
