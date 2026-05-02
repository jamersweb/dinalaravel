<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramPhase extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\ProgramPhaseFactory::new();
    }
    function phaseWorkouts(){
        return $this->hasMany(ProgramPhaseWorkout::class,'program_phase_id','id');
    }
}
