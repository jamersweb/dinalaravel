<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UMPTracking extends Model
{
    use HasFactory;
    protected $table = 'ump_trackings';
    protected $fillable = ['ump_id','mw_id','md_id','meal_id','meal_type','status'];
}
