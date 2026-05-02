<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramSub extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected static function newFactory()
    {
        return \Database\Factories\ProgramSubFactory::new();
    }

    function programName(){
        return Program::where('id',$this->program_id)->pluck('title')->first();
    }

    function programImage(){
        return Program::where('id',$this->program_id)->pluck('image')->first();
    }

    function programDuration(){
        return WeekWiseProgram::where('program_sub_id',$this->id)->count();
    }

    function userName(){
        $name = UserDetail::where('user_id',$this->user_id)->first(['name','Lastname']);
        return $name->name.' '.$name->Lastname;
    }
}
