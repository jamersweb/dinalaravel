<?php

namespace App\Http\Controllers\Concerns;

use App\Models\UserSetting;
use Illuminate\Support\Facades\Auth;

trait ResolvesUserLanguage
{
    protected function currentUserLanguage(): string
    {
        $uid = Auth::id();
        if (! $uid) {
            return 'en';
        }

        return UserSetting::query()->where('user_id', $uid)->value('language') ?: 'en';
    }
}
