<?php

namespace App\Support;

class ContentCodeNormalizer
{
    /** Empty or whitespace-only string becomes null. */
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $t = trim($value);

        return $t === '' ? null : $t;
    }
}
