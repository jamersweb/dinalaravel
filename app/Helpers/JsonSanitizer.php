<?php

namespace App\Helpers;

/**
 * Sanitizes data for safe JSON encoding.
 * Fixes invalid UTF-8, control characters, and other content that can cause
 * FormatException / "Unrecognized string escape" when parsed by strict JSON decoders (e.g. Dart).
 */
class JsonSanitizer
{
    /**
     * Recursively sanitize a value for JSON output.
     *
     * @param mixed $value
     * @return mixed
     */
    public static function sanitize($value)
    {
        if (is_array($value)) {
            return array_map([self::class, 'sanitize'], $value);
        }

        if (is_object($value)) {
            $arr = (array) $value;
            return (object) array_map([self::class, 'sanitize'], $arr);
        }

        if (is_string($value)) {
            return self::sanitizeString($value);
        }

        return $value;
    }

    /**
     * Sanitize a string for JSON: ensure valid UTF-8 and remove problematic control chars.
     *
     * @param string $str
     * @return string
     */
    public static function sanitizeString(string $str): string
    {
        if ($str === '') {
            return $str;
        }

        // Ensure valid UTF-8 - iconv ignores invalid sequences
        if (function_exists('iconv')) {
            $str = iconv('UTF-8', 'UTF-8//IGNORE', $str);
        }

        // Remove control characters except \t (0x09), \n (0x0A), \r (0x0D) - these can break JSON
        $str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $str);

        // Replace typographic/smart quotes with ASCII - they can break JSON when inside strings
        $smartQuotes = ["\xE2\x80\x9C", "\xE2\x80\x9D", "\xE2\x80\x9E", "\xE2\x80\x9F", "\xE2\x80\xB3", "\xE2\x80\xB6"];
        $str = str_replace($smartQuotes, '"', $str);
        $smartApos = ["\xE2\x80\x98", "\xE2\x80\x99", "\xE2\x80\x9A", "\xE2\x80\x9B", "\xE2\x80\xB2"];
        $str = str_replace($smartApos, "'", $str);

        // Replace unescaped double quotes with single quote - prevents "language""en" style JSON breaks
        $str = str_replace('"', "'", $str);

        return $str;
    }
}
