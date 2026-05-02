<?php

namespace App\Services;

use App\Models\Exercise;
use App\Models\Meal;
use App\Models\Program;
use App\Models\Workout;
use App\Support\ContentLocaleResolver;

class ContentBulkTranslationService
{
    public function __construct(private GoogleTranslateService $translator)
    {
    }

    /**
     * @param  array<int, string>  $scopes  e.g. ['meals','exercises','programs','workouts']
     * @return array{meals_updated: int, exercises_updated: int, programs_updated: int, workouts_updated: int}
     */
    public function translateForTarget(string $source, string $target, array $scopes): array
    {
        $source = strtolower($source);
        $target = strtolower($target);

        if ($target === $source) {
            return [
                'meals_updated' => 0,
                'exercises_updated' => 0,
                'programs_updated' => 0,
                'workouts_updated' => 0,
            ];
        }

        $mealsCount = 0;
        $exercisesCount = 0;
        $programsCount = 0;
        $workoutsCount = 0;

        if (in_array('meals', $scopes, true)) {
            Meal::query()
                ->where('meal_by', 'admin')
                ->where('language', $source)
                ->orderBy('id')
                ->chunkById(25, function ($meals) use ($source, $target, &$mealsCount) {
                    foreach ($meals as $meal) {
                        $map = $meal->locale_translations;
                        if (! is_array($map)) {
                            $map = [];
                        }
                        $bucket = [];
                        foreach (ContentLocaleResolver::MEAL_FIELDS as $field) {
                            $val = $meal->{$field};
                            if ($val === null || $val === '') {
                                continue;
                            }
                            $bucket[$field] = $this->translator->translate((string) $val, $source, $target);
                            usleep(50000);
                        }
                        if ($bucket !== []) {
                            $map[$target] = array_merge($map[$target] ?? [], $bucket);
                            $meal->locale_translations = $map;
                            $meal->save();
                            $mealsCount++;
                        }
                    }
                });
        }

        if (in_array('exercises', $scopes, true)) {
            Exercise::query()
                ->where('language', $source)
                ->orderBy('id')
                ->chunkById(25, function ($rows) use ($source, $target, &$exercisesCount) {
                    foreach ($rows as $ex) {
                        $map = $ex->locale_translations;
                        if (! is_array($map)) {
                            $map = [];
                        }
                        $bucket = [];
                        foreach (ContentLocaleResolver::EXERCISE_FIELDS as $field) {
                            $val = $ex->{$field};
                            if ($val === null || $val === '') {
                                continue;
                            }
                            $bucket[$field] = $this->translator->translate((string) $val, $source, $target);
                            usleep(50000);
                        }
                        if ($bucket !== []) {
                            $map[$target] = array_merge($map[$target] ?? [], $bucket);
                            $ex->locale_translations = $map;
                            $ex->save();
                            $exercisesCount++;
                        }
                    }
                });
        }

        if (in_array('programs', $scopes, true)) {
            Program::query()
                ->where('language', $source)
                ->orderBy('id')
                ->chunkById(25, function ($rows) use ($source, $target, &$programsCount) {
                    foreach ($rows as $row) {
                        $map = $row->locale_translations;
                        if (! is_array($map)) {
                            $map = [];
                        }
                        $bucket = [];
                        foreach (ContentLocaleResolver::PROGRAM_FIELDS as $field) {
                            $val = $row->{$field};
                            if ($val === null || $val === '') {
                                continue;
                            }
                            $bucket[$field] = $this->translator->translate((string) $val, $source, $target);
                            usleep(50000);
                        }
                        if ($bucket !== []) {
                            $map[$target] = array_merge($map[$target] ?? [], $bucket);
                            $row->locale_translations = $map;
                            $row->save();
                            $programsCount++;
                        }
                    }
                });
        }

        if (in_array('workouts', $scopes, true)) {
            Workout::query()
                ->where('language', $source)
                ->orderBy('id')
                ->chunkById(25, function ($rows) use ($source, $target, &$workoutsCount) {
                    foreach ($rows as $row) {
                        $map = $row->locale_translations;
                        if (! is_array($map)) {
                            $map = [];
                        }
                        $bucket = [];
                        foreach (ContentLocaleResolver::WORKOUT_FIELDS as $field) {
                            $val = $row->{$field};
                            if ($val === null || $val === '') {
                                continue;
                            }
                            $bucket[$field] = $this->translator->translate((string) $val, $source, $target);
                            usleep(50000);
                        }
                        if ($bucket !== []) {
                            $map[$target] = array_merge($map[$target] ?? [], $bucket);
                            $row->locale_translations = $map;
                            $row->save();
                            $workoutsCount++;
                        }
                    }
                });
        }

        return [
            'meals_updated' => $mealsCount,
            'exercises_updated' => $exercisesCount,
            'programs_updated' => $programsCount,
            'workouts_updated' => $workoutsCount,
        ];
    }
}
