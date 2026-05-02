<?php

namespace App\Traits;

trait JsonSanitizeTrait
{
    /**
     * Sanitize a value for safe JSON encoding.
     * Removes control chars, fixes invalid UTF-8 that breaks json_encode.
     */
    protected function sanitizeForJson($value)
    {
        if ($value === null || is_numeric($value) || is_bool($value)) {
            return $value;
        }
        if (is_array($value)) {
            return array_map([$this, 'sanitizeForJson'], $value);
        }
        if (is_object($value)) {
            return (object) array_map(function ($v) {
                return $this->sanitizeForJson($v);
            }, (array) $value);
        }
        $str = (string) $value;
        // Remove control characters (0x00-0x1F, 0x7F) that break JSON
        $str = preg_replace('/[\x00-\x1F\x7F]/u', '', $str);
        // Fix invalid UTF-8 for json_encode
        if (!mb_check_encoding($str, 'UTF-8')) {
            $str = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
        }
        return $str;
    }
}
