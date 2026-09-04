<?php

namespace App\Helpers;

class JsonList
{
    /**
     * Decode a JSON list that may already be an array (MySQL JSON / Eloquent accessor).
     */
    public static function decode($value): array
    {
        if (is_null($value) || $value === '' || $value === 'null') {
            return [];
        }
        if (is_array($value)) {
            return array_values($value);
        }
        if (!is_string($value)) {
            return [];
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? array_values($decoded) : [];
    }

    public static function ids($value): array
    {
        return array_values(array_unique(array_filter(array_map('intval', self::decode($value)))));
    }
}
