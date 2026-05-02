<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\UserSettingFactory::new();
    }
}
