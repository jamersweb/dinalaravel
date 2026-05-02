<?php

namespace App\Models\Concerns;

trait AvailableInContentLocale
{
    /**
     * Programs/workouts authored in [locale] OR having JSON translations for [locale].
     */
    public function scopeAvailableInContentLocale($query, string $locale)
    {
        $locale = strtolower(trim($locale));
        if (! preg_match('/^[a-z0-9_-]{2,16}$/', $locale)) {
            return $query->whereRaw('1 = 0');
        }
        $path = '$."' . $locale . '"';

        return $query->where(function ($q) use ($locale, $path) {
            $q->where('language', $locale)
                ->orWhereRaw(
                    "JSON_CONTAINS_PATH(COALESCE(locale_translations, JSON_OBJECT()), 'one', ?)",
                    [$path]
                );
        });
    }
}
