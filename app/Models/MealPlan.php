<?php

namespace App\Models;

use App\Helpers\FileHandle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MealPlan extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\MealPlanFactory::new();
    }
    function getImageAttribute($value){
        if(empty($value)) return null;
        return FileHandle::getURL($value,5);
    }
    
    // function getAttatchmentAttribute($value){
    //     if(config('app.mode')!=="local"){
    //         return FileHandle::getURL($value,5);
    //     } else {
    //         return url('/')."/storage/meals/".$value;
    //     }
    // }

    function links(){
        $temp['file_view'] = is_null($this->attatchment)?null:FileHandle::getURL($this->attatchment,5);
        $temp['file_downoad'] = is_null($this->attatchment)?null:FileHandle::getURL($this->attatchment,5);
        $temp['file_view2'] = is_null($this->attatchment2)?null:FileHandle::getURL($this->attatchment2,5);
        $temp['file_downoad2'] = is_null($this->attatchment2)?null:FileHandle::getURL($this->attatchment2,5);
        $temp['file_view3'] = is_null($this->attatchment3)?null:FileHandle::getURL($this->attatchment3,5);
        $temp['file_downoad3'] = is_null($this->attatchment3)?null:FileHandle::getURL($this->attatchment3,5);
        return $temp;
    }
}
