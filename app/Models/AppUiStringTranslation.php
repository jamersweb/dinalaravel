<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppUiStringTranslation extends Model
{
    protected $table = 'app_ui_string_translations';

    protected $fillable = [
        'locale',
        'message_key',
        'value',
    ];
}
