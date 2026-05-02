<?php

namespace App\Support;

use App\Models\UserSetting;
use Illuminate\Support\Facades\Auth;

class UserContentLocale
{
    public static function forAuthenticatedUser(): string
    {
        $uid = Auth::id();
        if (! $uid) {
            return 'en';
        }

        $lang = UserSetting::query()->where('user_id', $uid)->value('language');

        return strtolower((string) ($lang ?: 'en'));
    }
}
