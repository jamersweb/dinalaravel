<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BodyStats extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\BodyStatFactory::new();
    }
}
