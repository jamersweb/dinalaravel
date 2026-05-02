<?php

namespace App\Support;

use App\Models\Exercise;
use App\Models\Program;
use App\Models\Workout;

class ContentLocaleResolver
{
    public const MEAL_FIELDS = ['name', 'prep_time', 'cook_time', 'suitable_for', 'contains', 'ingredients', 'directions'];

    public const EXERCISE_FIELDS = ['title', 'instructions'];

    /** @var array<int, string> DB column is `discription` (historic typo). */
    public const PROGRAM_FIELDS = ['title', 'discription'];

    public const WORKOUT_FIELDS = ['title', 'instructions', 'daily_summary'];

    public static function overlayExercise(Exercise $ex, string $userLang): void
    {
        $userLang = strtolower($userLang);
        if ($ex->language === $userLang) {
            return;
        }
        $translations = $ex->locale_translations;
        if (! is_array($translations) || ! isset($translations[$userLang]) || ! is_array($translations[$userLang])) {
            return;
        }
        $p = $translations[$userLang];
        foreach (self::EXERCISE_FIELDS as $f) {
            if (! empty($p[$f]) && is_string($p[$f])) {
                $ex->{$f} = $p[$f];
            }
        }
    }

    public static function overlayProgram(Program $program, string $userLang): void
    {
        $userLang = strtolower($userLang);
        if (($program->language ?? '') === $userLang) {
            return;
        }
        $translations = $program->locale_translations;
        if (! is_array($translations) || ! isset($translations[$userLang]) || ! is_array($translations[$userLang])) {
            return;
        }
        $p = $translations[$userLang];
        foreach (self::PROGRAM_FIELDS as $f) {
            if (! empty($p[$f]) && is_string($p[$f])) {
                $program->{$f} = $p[$f];
            }
        }
    }

    public static function overlayWorkout(Workout $workout, string $userLang): void
    {
        $userLang = strtolower($userLang);
        if (($workout->language ?? '') === $userLang) {
            return;
        }
        $translations = $workout->locale_translations;
        if (! is_array($translations) || ! isset($translations[$userLang]) || ! is_array($translations[$userLang])) {
            return;
        }
        $p = $translations[$userLang];
        foreach (self::WORKOUT_FIELDS as $f) {
            if (! empty($p[$f]) && is_string($p[$f])) {
                $workout->{$f} = $p[$f];
            }
        }
    }

    /**
     * @param  array<string, mixed>  $meal
     * @return array<string, mixed>
     */
    public static function applyMealArray(array $meal, string $locale): array
    {
        $locale = strtolower($locale);
        if (($meal['language'] ?? '') === $locale) {
            return $meal;
        }

        $translations = $meal['locale_translations'] ?? null;
        if (is_string($translations)) {
            $translations = json_decode($translations, true);
        }
        if (! is_array($translations) || ! isset($translations[$locale]) || ! is_array($translations[$locale])) {
            return $meal;
        }

        $patch = $translations[$locale];
        foreach (self::MEAL_FIELDS as $field) {
            if (! empty($patch[$field]) && is_string($patch[$field])) {
                $meal[$field] = $patch[$field];
            }
        }

        return $meal;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public static function applyExerciseArray(array $row, string $locale): array
    {
        $locale = strtolower($locale);
        $rowLang = $row['language'] ?? '';
        if ($rowLang === $locale) {
            return $row;
        }

        $translations = $row['locale_translations'] ?? null;
        if (is_string($translations)) {
            $translations = json_decode($translations, true);
        }
        if (! is_array($translations) || ! isset($translations[$locale]) || ! is_array($translations[$locale])) {
            return $row;
        }

        $patch = $translations[$locale];
        foreach (self::EXERCISE_FIELDS as $field) {
            if (! empty($patch[$field]) && is_string($patch[$field])) {
                $row[$field] = $patch[$field];
            }
        }

        return $row;
    }
}
