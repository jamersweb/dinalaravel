<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\FileHandle;

class Exercise extends Model
{
    use HasFactory;

    protected $hidden = [
        'custom_thumbnail',
    ];

    protected $casts = [
        'locale_translations' => 'array',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\ExerciseFactory::new();
    }

    public function getVideoUrlAttribute($value)
    {
        if($this->video_type == "youtube"){
            return config('app.youtube_video_baseUrl').$value;
        }
        if(empty($value)) return null;
        return FileHandle::getURL($value,4);
    }
    public function getImageAttribute($value)
    {
        // Must be loaded on the model (include in SELECT); otherwise accessor cannot prefer custom over YouTube default.
        $custom = $this->getRawOriginal('custom_thumbnail');
        if ($custom !== null && $custom !== '') {
            return FileHandle::getURL($custom, 4);
        }
        if ($this->video_type == 'youtube') {
            $id = is_string($value) ? $value : '';
            if (strlen($id) !== 11) {
                $vid = $this->getRawOriginal('video_url');
                $id = is_string($vid) ? $vid : '';
            }
            if (strlen($id) === 11) {
                return config('app.youtube_thumbnail_baseUrl_start').$id.config('app.youtube_thumbnail_baseUrl_end');
            }
        }
        if (empty($value)) {
            return null;
        }

        return FileHandle::getURL($value, 4);
    }

    function getAlternatesAttribute($value){
        return json_decode($value);
    }
}
