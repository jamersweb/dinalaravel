<?php

namespace App\Models;

use App\Helpers\FileHandle;
use App\Models\Concerns\AvailableInContentLocale;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Program extends Model
{
    use AvailableInContentLocale;
    use HasFactory;

    protected $casts = [
        'locale_translations' => 'array',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\ProgramFactory::new();
    }

    function programPhases(){
        return $this->hasMany(ProgramPhase::class,'program_id','id');
    }

    function totalWeeks(){
        $weeks = ProgramPhase::where('program_id',$this->id)->pluck('weeks')->toArray();
        return array_sum($weeks);
    }

    function subscribers(){
        return $this->hasMany(ProgramSub::class,'program_id','id');
    }

    function getImageAttribute($value){
        if (empty($value)) return null;
        if (is_string($value) && strlen($value) === 11) {
            return config('app.youtube_thumbnail_baseUrl_start').$value.config('app.youtube_thumbnail_baseUrl_end');
        }

        if (Storage::disk('fwd_media')->exists('programs/' . basename($value))) {
            return FileHandle::getURL($value, 2);
        }

        return FileHandle::getURL($value,4);
    }

    function isSubscribed($id){
        $isSub = ProgramSub::where('program_id',$this->id)->where('user_id',$id)->first();
        if($isSub)
        return true;
        return false;
    }
}
