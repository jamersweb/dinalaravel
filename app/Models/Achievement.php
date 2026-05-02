<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    use HasFactory;

     protected $fillable = ['user_id','badge_id','week_id','master_id'];


    public function badgeDetail()
    {
        return $this->hasOne(Badge::class,'id','badge_id');
    }
    public function programDetail()
    {
        return $this->hasOne(Program::class,'id','program_id');
    }
    function badgeName(){
        return Badge::where('id',$this->id)->pluck('name')->first();
    }
    function programName(){
        return Program::where('id',$this->id)->pluck('title')->first();
    }
}
