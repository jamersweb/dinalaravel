<?php

namespace App\Services;

use App\Models\ExerciseLibraryTag;
use App\Models\RoutineGenerationBatch;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class RoutineGeneratorService
{
    public function __construct(
        private RoutineContentAuditService $auditService,
        private RoutineValidatorService $validator
    ) {
    }

    public function generateBatch(array $filters): RoutineGenerationBatch
    {
        $filters = $this->normalizeFilters($filters);
        $audit = $this->auditService->audit($filters);

        $batch = RoutineGenerationBatch::create([
            'batch_code' => 'routine-batch-'.now()->format('Ymd-His').'-'.substr(md5(json_encode($filters)), 0, 8),
            'status' => $audit['status'] === 'ready' ? 'draft' : 'blocked_missing_content',
            'filters' => $filters,
            'missing_content_report' => $audit,
            'requested_count' => $filters['limit'],
            'created_count' => 0,
            'created_by' => $filters['created_by'] ?? null,
        ]);

        if ($audit['status'] !== 'ready') {
            return $batch;
        }

        $created = [];
        try {
            DB::transaction(function () use ($filters, $batch, &$created) {
                $typeCodes = $filters['workout_types'];
                $nextVariationByType = [];
                foreach ($typeCodes as $typeCode) {
                    $nextVariationByType[$typeCode] = $this->nextAvailableVariation($filters, $typeCode);
                }

                for ($variation = 1; $variation <= $filters['variations_per_type']; $variation++) {
                    foreach ($typeCodes as $typeCode) {
                        if (count($created) >= $filters['limit']) {
                            break 2;
                        }

                        $nextVariation = $nextVariationByType[$typeCode] ?? null;
                        if ($nextVariation === null) {
                            continue;
                        }

                        $routineId = RoutineLibraryRules::routineId(
                            $filters['equipment_category'],
                            $filters['fitness_level'],
                            $typeCode,
                            $filters['language'],
                            $nextVariation
                        );

                        $workout = $this->createRoutine($batch, $filters, $typeCode, $nextVariation, $routineId);
                        $created[] = $workout->id;
                        $nextVariationByType[$typeCode] = $this->nextAvailableVariation($filters, $typeCode, $nextVariation + 1);
                    }
                }
            });
        } catch (Throwable $e) {
            $batch->status = 'failed';
            $batch->validation_report = [
                'error' => $e->getMessage(),
            ];
            $batch->save();

            return $batch;
        }

        $validation = [];
        foreach (Workout::whereIn('id', $created)->get() as $workout) {
            $result = $this->validator->validateWorkout($workout);
            $workout->routine_validation_errors = $result['errors'];
            $workout->routine_status = $result['valid'] ? 'pending_review' : 'revision';
            $workout->save();
            $validation[$workout->content_code] = $result;
        }

        $batch->created_count = count($created);
        $batch->validation_report = $validation;
        $batch->status = count($created) > 0 ? 'pending_review' : 'empty';
        $batch->save();

        return $batch;
    }

    private function nextAvailableVariation(array $filters, string $typeCode, int $start = 1): ?int
    {
        for ($variation = max(1, $start); $variation <= 99; $variation++) {
            $routineId = RoutineLibraryRules::routineId(
                $filters['equipment_category'],
                $filters['fitness_level'],
                $typeCode,
                $filters['language'],
                $variation
            );

            if (! Workout::where('content_code', $routineId)->exists()) {
                return $variation;
            }
        }

        return null;
    }

    private function createRoutine(
        RoutineGenerationBatch $batch,
        array $filters,
        string $typeCode,
        int $variation,
        string $routineId
    ): Workout {
        $title = $this->routineTitle($filters, $typeCode, $variation);
        $routineFilters = array_merge($filters, [
            'workout_type' => $typeCode,
            'variation' => $variation,
        ]);
        $mainExerciseCount = $this->mainExerciseCount($filters);
        $usedExerciseIds = [];
        $usedTitleKeys = [];
        $mainWorkout = $this->pick($routineFilters, 'main_workout', $mainExerciseCount, $variation, $usedExerciseIds, null, null, $usedTitleKeys);
        $focusProfile = $this->focusProfile($mainWorkout);
        $sections = [
            'dynamic_warm_up' => $this->dynamicWarmUp($routineFilters, $variation, $usedExerciseIds, $focusProfile, $usedTitleKeys),
            'warm_up_cardio' => $this->pick($routineFilters, 'cardio_warm_up', 1, $variation, $usedExerciseIds, $focusProfile, null, $usedTitleKeys),
            'muscle_activation' => $this->pick($routineFilters, 'muscle_activation', 1, $variation, $usedExerciseIds, $focusProfile, null, $usedTitleKeys),
            'lower_back_core_superset' => $this->lowerBackCoreSuperset($routineFilters, $variation, $usedExerciseIds, $focusProfile, $usedTitleKeys),
            'main_workout' => $mainWorkout,
            'post_workout_stretching' => $this->pickStretching($routineFilters, $this->stretchCount($filters), $variation, $usedExerciseIds, $focusProfile, $usedTitleKeys),
        ];
        if ($this->shouldIncludeOptionalCardio($filters, $typeCode)) {
            try {
                $sections['optional_additional_cardio'] = $this->pick($routineFilters, 'optional_cardio', 1, $variation + 3, $usedExerciseIds, $focusProfile, null, $usedTitleKeys);
            } catch (RuntimeException) {
                $sections['optional_additional_cardio'] = [];
            }
        }
        $estimatedMinutes = $this->estimateWorkoutMinutes($sections, $filters);

        $workout = Workout::create([
            'content_code' => $routineId,
            'title' => $title,
            'category' => 'master',
            'equipment_category' => $filters['equipment_category'],
            'fitness_level' => $filters['fitness_level'],
            'workout_type' => $typeCode,
            'routine_source' => 'generated',
            'routine_status' => 'draft',
            'routine_generation_batch_id' => $batch->id,
            'type' => 'regular',
            'language' => $filters['language'],
            'image' => $this->coverImage($sections),
            'instructions' => $this->description($filters, $typeCode),
            'daily_summary' => "{$estimatedMinutes} min estimated " . (RoutineLibraryRules::WORKOUT_TYPES[$typeCode] ?? strtoupper($typeCode)) . ' workout.',
            'routine_sections' => $this->sectionSummary($sections, $routineFilters, $estimatedMinutes),
        ]);

        foreach ($sections as $section => $tags) {
            $groupId = $section === 'lower_back_core_superset' ? 'lb-core-'.$workout->id : null;
            foreach ($tags as $index => $tag) {
                WorkoutExercise::create($this->workoutExercisePayload($workout->id, $tag, $section, $index, $filters, $groupId));
            }
        }

        return $workout;
    }

    private function pick(array $filters, string $usage, int $count, int $offset, array &$usedExerciseIds, ?array $focusProfile = null, ?string $bodyRegion = null, array &$usedTitleKeys = []): array
    {
        $candidateUsedExerciseIds = $usedExerciseIds;
        $candidateUsedTitleKeys = $usedTitleKeys;
        $allowedEquipment = RoutineLibraryRules::allowedExerciseEquipment($filters['equipment_category']);
        $preferredEquipment = RoutineLibraryRules::preferredExerciseEquipment($filters['equipment_category']);
        $tags = ExerciseLibraryTag::with('exercise:id,title,video_type,video_url,image,custom_thumbnail')
            ->where('language', $filters['language'])
            ->where('approved_for_generation', true)
            ->whereIn('equipment_category', $allowedEquipment)
            ->get()
            ->filter(fn ($tag) => $this->levelCanServeRoutine((string) $tag->difficulty, $filters['fitness_level']))
            ->filter(fn ($tag) => $this->tagCanServeFitnessLevel($tag, $filters['fitness_level']))
            ->filter(fn ($tag) => $this->tagMatchesUsage($tag, $usage))
            ->filter(fn ($tag) => $bodyRegion === null || in_array($bodyRegion, $this->bodyRegionsForTag($tag), true))
            ->unique('exercise_id')
            ->values();

        if ($usage === 'main_workout') {
            $preferredTags = $tags
                ->filter(fn ($tag) => in_array($tag->equipment_category, $preferredEquipment, true))
                ->values();

            if ($preferredTags->count() < $count) {
                throw new RuntimeException(
                    "Not enough approved {$filters['equipment_category']} exercises for usage {$usage}; found {$preferredTags->count()}."
                );
            }

            $tags = $preferredTags;
        }

        $tags = $tags
            ->reject(fn ($tag) => in_array((int) $tag->exercise_id, $candidateUsedExerciseIds, true) || isset($candidateUsedTitleKeys[$this->titleKey($tag)]))
            ->sortBy('exercise_id')
            ->values();

        if ($focusProfile !== null) {
            $scored = $tags
                ->map(fn ($tag) => ['tag' => $tag, 'score' => $this->profileScore($tag, $focusProfile)])
                ->sortByDesc('score')
                ->values();
            $matched = $scored->filter(fn ($item) => $item['score'] > 0)->pluck('tag')->values();
            $tags = ($matched->count() >= $count ? $matched : $scored->pluck('tag'))->values();
        }

        if ($tags->count() < $count) {
            throw new RuntimeException("Not enough unique approved exercises for usage {$usage}; found {$tags->count()}.");
        }

        $selected = [];
        $selectedFamilies = [];
        $seed = abs(crc32(implode('|', [
            $filters['language'] ?? '',
            $filters['equipment_category'] ?? '',
            $filters['fitness_level'] ?? '',
            $filters['target_minutes'] ?? '',
            $filters['workout_type'] ?? '',
            $filters['variation'] ?? '',
            $usage,
            $offset,
        ])));
        $start = $seed % $tags->count();
        for ($i = 0; count($selected) < $count && $i < $tags->count() * 2; $i++) {
            $tag = $tags[($start + $i) % $tags->count()];
            $titleKey = $this->titleKey($tag);
            if ($titleKey !== '' && isset($candidateUsedTitleKeys[$titleKey])) {
                continue;
            }
            $family = (string) ($tag->exercise_family ?? '');
            if ($usage === 'main_workout' && $family !== '' && isset($selectedFamilies[$family])) {
                continue;
            }

            $selected[] = $tag;
            $candidateUsedExerciseIds[] = (int) $tag->exercise_id;
            if ($titleKey !== '') {
                $candidateUsedTitleKeys[$titleKey] = true;
            }
            if ($family !== '') {
                $selectedFamilies[$family] = true;
            }
        }

        if (count($selected) < $count) {
            foreach ($tags as $tag) {
                if (in_array((int) $tag->exercise_id, $candidateUsedExerciseIds, true)) {
                    continue;
                }
                $titleKey = $this->titleKey($tag);
                if ($titleKey !== '' && isset($candidateUsedTitleKeys[$titleKey])) {
                    continue;
                }
                $selected[] = $tag;
                $candidateUsedExerciseIds[] = (int) $tag->exercise_id;
                if ($titleKey !== '') {
                    $candidateUsedTitleKeys[$titleKey] = true;
                }
                if (count($selected) >= $count) {
                    break;
                }
            }
        }

        if (count($selected) < $count) {
            throw new RuntimeException("Not enough unique approved exercises for usage {$usage}; selected ".count($selected)." of {$count}.");
        }

        $usedExerciseIds = $candidateUsedExerciseIds;
        $usedTitleKeys = $candidateUsedTitleKeys;

        return $selected;
    }

    private function dynamicWarmUp(array $filters, int $variation, array &$usedExerciseIds, array $focusProfile, array &$usedTitleKeys): array
    {
        $count = $this->dynamicWarmUpCount($filters);
        $mobilityCount = $count <= 3 ? 1 : 2;
        if ($count >= 7) {
            $mobilityCount = 3;
        }
        $warmUpCount = $count - $mobilityCount;

        $warmUps = $this->pick($filters, 'warm_up', $warmUpCount, $variation, $usedExerciseIds, $focusProfile, null, $usedTitleKeys);

        try {
            $mobility = $this->pick($filters, 'mobility', $mobilityCount, $variation + 1, $usedExerciseIds, $focusProfile, null, $usedTitleKeys);
        } catch (RuntimeException) {
            $mobility = [];
            if ($mobilityCount > 1) {
                try {
                    $mobility = $this->pick($filters, 'mobility', 1, $variation + 1, $usedExerciseIds, $focusProfile, null, $usedTitleKeys);
                } catch (RuntimeException) {
                    $mobility = [];
                }
            }

            $remainingWarmUps = $count - count($warmUps) - count($mobility);
            if ($remainingWarmUps > 0) {
                $warmUps = array_merge(
                    $warmUps,
                    $this->pick($filters, 'warm_up', $remainingWarmUps, $variation + 10, $usedExerciseIds, $focusProfile, null, $usedTitleKeys)
                );
            }
        }

        return array_merge($warmUps, $mobility);
    }

    private function lowerBackCoreSuperset(array $filters, int $variation, array &$usedExerciseIds, array $focusProfile, array &$usedTitleKeys): array
    {
        try {
            $lowerBack = $this->pick($filters, 'lower_back_activation', 1, $variation, $usedExerciseIds, $focusProfile, null, $usedTitleKeys);
        } catch (RuntimeException) {
            $lowerBack = $this->pick($filters, 'lower_back_strength', 1, $variation, $usedExerciseIds, $focusProfile, null, $usedTitleKeys);
        }

        $primaryCoreUsage = $variation % 2 === 0 ? 'abs' : 'obliques';
        $fallbackCoreUsage = $primaryCoreUsage === 'abs' ? 'obliques' : 'abs';
        try {
            $core = $this->pick($filters, $primaryCoreUsage, 1, $variation + 1, $usedExerciseIds, $focusProfile, null, $usedTitleKeys);
        } catch (RuntimeException) {
            $core = $this->pick($filters, $fallbackCoreUsage, 1, $variation + 2, $usedExerciseIds, $focusProfile, null, $usedTitleKeys);
        }

        return array_merge($lowerBack, $core);
    }

    private function pickStretching(array $filters, int $count, int $variation, array &$usedExerciseIds, array $focusProfile, array &$usedTitleKeys): array
    {
        $selected = [];
        foreach ($this->stretchCoverageTargets() as $index => $regions) {
            if (count($selected) >= $count) {
                break;
            }

            try {
                $selected = array_merge($selected, $this->pickStretchingForRegions(
                    $filters,
                    $variation + $index,
                    $usedExerciseIds,
                    $focusProfile,
                    $regions,
                    $usedTitleKeys
                ));
            } catch (RuntimeException) {
                continue;
            }
        }

        foreach ($this->stretchTargetRegions($focusProfile) as $index => $region) {
            if (count($selected) >= $count) {
                break;
            }

            try {
                $selected = array_merge(
                    $selected,
                    $this->pick($filters, 'stretching', 1, $variation + 10 + $index, $usedExerciseIds, $focusProfile, $region, $usedTitleKeys)
                );
            } catch (RuntimeException) {
                continue;
            }
        }

        $remaining = $count - count($selected);
        if ($remaining > 0) {
            $selected = array_merge(
                $selected,
                $this->pick($filters, 'stretching', $remaining, $variation + 30, $usedExerciseIds, null, null, $usedTitleKeys)
            );
        }

        return $selected;
    }

    private function pickStretchingForRegions(array $filters, int $variation, array &$usedExerciseIds, array $focusProfile, array $regions, array &$usedTitleKeys): array
    {
        foreach ($regions as $offset => $region) {
            try {
                return $this->pick($filters, 'stretching', 1, $variation + $offset, $usedExerciseIds, $focusProfile, $region, $usedTitleKeys);
            } catch (RuntimeException) {
                continue;
            }
        }

        throw new RuntimeException('Not enough stretching coverage for requested body-region group.');
    }

    private function focusProfile(array $mainWorkout): array
    {
        $profile = [
            'muscles' => [],
            'body_regions' => [],
            'movement_patterns' => [],
            'families' => [],
        ];

        foreach ($mainWorkout as $tag) {
            $profile['muscles'][] = strtolower((string) $tag->muscle_group);
            foreach (is_array($tag->secondary_muscle_groups) ? $tag->secondary_muscle_groups : [] as $muscle) {
                $profile['muscles'][] = strtolower((string) $muscle);
            }
            foreach (is_array($tag->body_regions) ? $tag->body_regions : [] as $region) {
                $profile['body_regions'][] = strtolower((string) $region);
            }
            foreach (is_array($tag->movement_patterns) ? $tag->movement_patterns : [] as $pattern) {
                $profile['movement_patterns'][] = strtolower((string) $pattern);
            }
            if (! empty($tag->exercise_family)) {
                $profile['families'][] = strtolower((string) $tag->exercise_family);
            }
        }

        return array_map(fn ($items) => array_values(array_unique(array_filter($items))), $profile);
    }

    private function profileScore(ExerciseLibraryTag $tag, array $profile): int
    {
        $score = 0;
        $tagMuscles = array_filter(array_merge(
            [strtolower((string) $tag->muscle_group)],
            array_map('strtolower', is_array($tag->secondary_muscle_groups) ? $tag->secondary_muscle_groups : [])
        ));
        $tagRegions = array_map('strtolower', is_array($tag->body_regions) ? $tag->body_regions : []);
        $tagPatterns = array_map('strtolower', is_array($tag->movement_patterns) ? $tag->movement_patterns : []);

        $score += count(array_intersect($tagMuscles, $profile['muscles'] ?? [])) * 3;
        $score += count(array_intersect($tagRegions, $profile['body_regions'] ?? [])) * 2;
        $score += count(array_intersect($tagPatterns, $profile['movement_patterns'] ?? []));

        return $score;
    }

    private function stretchTargetRegions(array $focusProfile): array
    {
        $targets = array_values(array_unique(array_merge(
            $focusProfile['body_regions'] ?? [],
            ['upper_body', 'lower_body', 'core', 'back']
        )));

        return array_values(array_intersect($targets, RoutineLibraryRules::BODY_REGIONS));
    }

    private function stretchCoverageTargets(): array
    {
        return [
            ['upper_body', 'chest', 'back', 'shoulders', 'arms'],
            ['lower_body', 'glutes', 'quadriceps', 'hamstrings', 'calves'],
            ['core', 'abs', 'obliques', 'lower_back', 'back'],
        ];
    }

    private function titleKey(ExerciseLibraryTag $tag): string
    {
        $title = strtolower(trim((string) optional($tag->exercise)->title));
        $title = preg_replace('/\s+/', ' ', $title) ?? $title;

        return preg_replace('/\b(day|part|variation)\s*\d+\b/', '', $title) ?? $title;
    }

    private function bodyRegionsForTag(ExerciseLibraryTag $tag): array
    {
        $regions = is_array($tag->body_regions) ? array_map('strtolower', $tag->body_regions) : [];
        $muscles = [strtolower((string) $tag->muscle_group)];
        foreach (is_array($tag->secondary_muscle_groups) ? $tag->secondary_muscle_groups : [] as $muscle) {
            $muscles[] = strtolower((string) $muscle);
        }

        foreach ($muscles as $muscle) {
            $regions = array_merge($regions, $this->bodyRegionsForMuscle($muscle));
        }

        $title = strtolower((string) optional($tag->exercise)->title);
        foreach ([
            'upper_body' => '/\b(chest|shoulder|lat|back|bicep|tricep|arm|pec|upper body)\b/',
            'lower_body' => '/\b(quad|hamstring|calf|glute|hip flexor|hip|groin|adductor|leg|piriformis)\b/',
            'core' => '/\b(core|abs|oblique|plank|rotation|twist)\b/',
            'back' => '/\b(back|lat|thoracic|cobra|child pose)\b/',
            'lower_back' => '/\b(lower back|lumbar|piriformis)\b/',
        ] as $region => $pattern) {
            if (preg_match($pattern, $title)) {
                $regions[] = $region;
            }
        }

        return array_values(array_unique(array_intersect($regions, RoutineLibraryRules::BODY_REGIONS)));
    }

    private function bodyRegionsForMuscle(string $muscle): array
    {
        $key = trim(str_replace('_', ' ', strtolower($muscle)));

        if (str_contains($key, 'glute')) {
            return ['glutes', 'lower_body'];
        }
        if (str_contains($key, 'hip') || str_contains($key, 'groin') || str_contains($key, 'adductor')) {
            return ['lower_body'];
        }
        if (str_contains($key, 'calf')) {
            return ['calves', 'lower_body'];
        }

        return [
            'abs' => ['core', 'abs'],
            'core' => ['core', 'abs'],
            'obliques' => ['core', 'obliques'],
            'lower back' => ['lower_back', 'back', 'core'],
            'back' => ['back', 'upper_body'],
            'middle back' => ['back', 'upper_body'],
            'upper back' => ['back', 'upper_body'],
            'lats' => ['back', 'upper_body'],
            'chest' => ['chest', 'upper_body'],
            'shoulder' => ['shoulders', 'upper_body'],
            'shoulders' => ['shoulders', 'upper_body'],
            'biceps' => ['arms', 'upper_body'],
            'triceps' => ['arms', 'upper_body'],
            'forearms' => ['arms', 'upper_body'],
            'hamstrings' => ['hamstrings', 'lower_body'],
            'quads' => ['quadriceps', 'lower_body'],
            'quadriceps' => ['quadriceps', 'lower_body'],
            'full body' => ['full_body'],
        ][$key] ?? [];
    }

    private function workoutExercisePayload(int $workoutId, ExerciseLibraryTag $tag, string $section, int $index, array $filters, ?string $groupId = null): array
    {
        $isTimed = in_array($section, [
            'dynamic_warm_up',
            'warm_up_cardio',
            'optional_additional_cardio',
            'post_workout_stretching',
        ], true);
        $sets = $this->prescribedSets($tag, $section, $filters);
        $reps = $tag->recommended_repetitions ?: ($section === 'main_workout' ? '10-12' : '8-15');
        $duration = $this->displayDuration($tag, $section);
        $rest = $tag->recommended_rest_seconds;
        if ($rest === null) {
            $rest = $section === 'main_workout' ? 60 : 30;
        }

        return [
            'workout_id' => $workoutId,
            'exercise_id' => $tag->exercise_id,
            'sets' => $isTimed ? null : $sets,
            'reps' => $isTimed ? null : $reps,
            'reps_type' => $isTimed ? 'time' : 'text',
            'time' => $isTimed ? $duration : null,
            'rest_period' => $rest,
            'description' => $this->sectionInstruction($section),
            'category' => $section,
            'group_id' => $groupId,
            'group_type' => $groupId ? 'superset' : null,
            'group_label' => $groupId ? 'Lower-Back and Core Superset' : null,
            'group_order' => $this->sectionOrder($section) + $index,
        ];
    }

    private function coverImage(array $sections): ?string
    {
        $orderedSections = [
            'main_workout',
            'lower_back_core_superset',
            'muscle_activation',
            'dynamic_warm_up',
            'optional_additional_cardio',
            'warm_up_cardio',
            'post_workout_stretching',
        ];

        foreach ($orderedSections as $section) {
            $tags = $sections[$section] ?? [];
            foreach ($tags as $tag) {
                $exercise = $tag->exercise;
                if (! $exercise) {
                    continue;
                }

                $customThumbnail = $exercise->getRawOriginal('custom_thumbnail');
                if (! empty($customThumbnail)) {
                    return $customThumbnail;
                }

                $image = $exercise->getRawOriginal('image');
                if (! empty($image)) {
                    return $image;
                }

                if ($exercise->video_type === 'youtube') {
                    $videoId = $exercise->getRawOriginal('video_url');
                    if (! empty($videoId)) {
                        return $videoId;
                    }
                }
            }
        }

        return null;
    }

    private function normalizeFilters(array $filters): array
    {
        $typeCodes = $filters['workout_types'] ?? array_keys(RoutineLibraryRules::WORKOUT_TYPES);
        $typeCodes = array_values(array_intersect($typeCodes, array_keys(RoutineLibraryRules::WORKOUT_TYPES)));
        $language = RoutineLibraryRules::normalizeLanguage($filters['language'] ?? 'en');
        if (! in_array($language, RoutineLibraryRules::CONTENT_LANGUAGES, true)) {
            throw new RuntimeException('No-audio routine generation is deferred until no-audio exercise coverage is approved.');
        }

        return [
            'language' => $language,
            'equipment_category' => RoutineLibraryRules::normalizeEquipment($filters['equipment_category'] ?? 'bodyweight'),
            'fitness_level' => RoutineLibraryRules::normalizeLevel($filters['fitness_level'] ?? 'beginner'),
            'workout_types' => $typeCodes ?: array_keys(RoutineLibraryRules::WORKOUT_TYPES),
            'target_minutes' => $this->normalizeTargetMinutes($filters['target_minutes'] ?? $filters['duration_minutes'] ?? 30),
            'variations_per_type' => max(1, min(15, (int) ($filters['variations_per_type'] ?? 1))),
            'limit' => max(1, min(2340, (int) ($filters['limit'] ?? 10))),
            'created_by' => $filters['created_by'] ?? null,
        ];
    }

    private function routineTitle(array $filters, string $typeCode, int $variation): string
    {
        $type = RoutineLibraryRules::WORKOUT_TYPES[$typeCode] ?? strtoupper($typeCode);
        $level = ucfirst($filters['fitness_level']);

        return "{$level} {$type} {$variation}";
    }

    private function description(array $filters, string $typeCode): string
    {
        $type = RoutineLibraryRules::WORKOUT_TYPES[$typeCode] ?? strtoupper($typeCode);
        $equipment = str_replace('_', ' ', $filters['equipment_category']);

        return "{$type} routine assembled from approved {$equipment} exercise-library content for an estimated {$filters['target_minutes']} minute session.";
    }

    private function sectionSummary(array $sections, array $filters, int $estimatedMinutes): array
    {
        $summary = [
            '_meta' => [
                'target_minutes' => $filters['target_minutes'],
                'estimated_minutes' => $estimatedMinutes,
                'duration_delta_minutes' => $estimatedMinutes - $filters['target_minutes'],
                'section_contract' => RoutineLibraryRules::ROUTINE_SECTION_CONTRACT,
                'dina_methodology' => [
                    'mandatory_usage' => RoutineLibraryRules::DINA_MANDATORY_USAGE,
                    'mobility_focus' => $this->mobilityFocus($filters),
                    'coaching_cue' => $this->dinaCoachingCue($filters),
                    'rules' => [
                        'dynamic_warm_up_before_cardio',
                        'lower_back_core_superset_every_session',
                        'full_body_post_workout_stretching',
                        'movement_quality_first',
                        'coach_approval_required',
                    ],
                ],
            ],
        ];
        foreach ($sections as $section => $tags) {
            $summary[$section] = [
                'label' => RoutineLibraryRules::WORKOUT_SECTION_LABELS[$section] ?? $section,
                'order' => $this->sectionOrder($section),
                'instructions' => $this->sectionInstruction($section),
                'estimated_minutes' => $this->estimateSectionMinutes($section, $tags, $filters),
                'exercises' => array_map(fn ($tag) => [
                    'exercise_id' => $tag->exercise_id,
                    'title' => optional($tag->exercise)->title,
                    'sets' => $tag->recommended_sets,
                    'repetitions' => $tag->recommended_repetitions,
                    'duration_seconds' => $tag->recommended_duration_seconds,
                    'rest_seconds' => $tag->recommended_rest_seconds,
                    'modification' => $this->modificationNote($tag),
                    'progression_option' => $this->progressionNote($tag),
                    'safety_notes' => $tag->safety_notes,
                ], $tags),
            ];
        }

        return $summary;
    }

    private function sectionInstruction(string $section): string
    {
        return match ($section) {
            'dynamic_warm_up' => 'Use controlled dynamic movement that prepares the joints, muscles, and patterns trained today.',
            'warm_up_cardio' => 'Keep the pace easy to moderate and gradually increase body temperature.',
            'mobility_dynamic_warm_up' => 'Move smoothly through a pain-free range and prepare the joints used today.',
            'muscle_activation' => 'Use controlled tempo to activate the main muscles before heavier work.',
            'lower_back_core_superset' => 'Pair lower-back preparation with core bracing. Keep the effort controlled and avoid fatigue.',
            'core_lower_back_preparation' => 'Brace gently and prepare the core and lower back without fatigue.',
            'main_workout' => 'Use clean form and leave 1-2 reps in reserve unless the program states otherwise.',
            'core_obliques' => 'Brace and avoid pulling through the neck or lower back.',
            'lower_back_strengthening' => 'Strengthen the posterior core with controlled tempo and no painful range.',
            'optional_additional_cardio' => 'Optional calorie-expenditure support; skip when recovery, pain, or time is a concern.',
            'post_workout_stretching' => 'Hold each stretch without bouncing, breathe slowly, and cover the full body with priority on muscles trained today.',
            'cool_down_stretching' => 'Hold each stretch without bouncing and breathe slowly.',
            default => '',
        };
    }

    private function sectionOrder(string $section): int
    {
        return array_search($section, [
            'dynamic_warm_up',
            'warm_up_cardio',
            'muscle_activation',
            'lower_back_core_superset',
            'main_workout',
            'optional_additional_cardio',
            'post_workout_stretching',
        ], true) * 10;
    }

    private function normalizeTargetMinutes($value): int
    {
        $minutes = (int) $value;
        if ($minutes <= 0) {
            return 30;
        }

        return max(15, min(60, $minutes));
    }

    private function mainExerciseCount(array $filters): int
    {
        $minutes = (int) $filters['target_minutes'];
        $base = match (true) {
            $minutes <= 15 => 3,
            $minutes <= 20 => 3,
            $minutes <= 30 => 5,
            $minutes <= 45 => 7,
            default => 8,
        };

        if ($filters['fitness_level'] === 'advanced' && $minutes >= 45) {
            return min(9, $base + 1);
        }

        return $base;
    }

    private function dynamicWarmUpCount(array $filters): int
    {
        $minutes = (int) $filters['target_minutes'];
        if ($minutes <= 15) {
            return 3;
        }
        if ($minutes <= 20) {
            return 4;
        }
        if ($minutes >= 60 || (($filters['fitness_level'] ?? '') === 'advanced' && $minutes >= 45)) {
            return 7;
        }

        return 5;
    }

    private function stretchCount(array $filters): int
    {
        $minutes = (int) $filters['target_minutes'];
        if ($minutes <= 30) {
            return 5;
        }
        if ($minutes >= 60) {
            return 8;
        }

        return 7;
    }

    private function shouldIncludeOptionalCardio(array $filters, string $typeCode): bool
    {
        $minutes = (int) $filters['target_minutes'];
        if ($minutes >= 45) {
            return true;
        }

        return $minutes >= 30 && in_array($typeCode, ['hic', 'cst', 'fnc'], true);
    }

    private function displayDuration(ExerciseLibraryTag $tag, string $section): string
    {
        if ($section === 'warm_up_cardio') {
            return '5-10 min';
        }
        if ($section === 'optional_additional_cardio') {
            return '10-20 min';
        }

        $seconds = (int) ($tag->recommended_duration_seconds ?: 45);

        return $seconds >= 60
            ? round($seconds / 60) . ' min'
            : $seconds . ' sec';
    }

    private function estimateWorkoutMinutes(array $sections, array $filters): int
    {
        $seconds = 0;
        foreach ($sections as $section => $tags) {
            $seconds += $this->estimateSectionMinutes($section, $tags, $filters) * 60;
        }

        return (int) max(1, round($seconds / 60));
    }

    private function estimateSectionMinutes(string $section, array $tags, array $filters): int
    {
        if ($tags === []) {
            return 0;
        }

        if ((int) $filters['target_minutes'] <= 15) {
            return match ($section) {
                'dynamic_warm_up' => 3,
                'warm_up_cardio' => 5,
                'muscle_activation' => 1,
                'lower_back_core_superset' => 2,
                'main_workout' => 5,
                'post_workout_stretching' => 3,
                'optional_additional_cardio' => 0,
                default => 1,
            };
        }

        if ((int) $filters['target_minutes'] <= 30) {
            return match ($section) {
                'dynamic_warm_up' => 4,
                'warm_up_cardio' => 5,
                'muscle_activation' => 2,
                'lower_back_core_superset' => 3,
                'main_workout' => 11,
                'post_workout_stretching' => 5,
                'optional_additional_cardio' => 10,
                default => 1,
            };
        }

        if ($section === 'dynamic_warm_up') {
            return (int) $filters['target_minutes'] >= 60 ? 7 : 5;
        }
        if ($section === 'warm_up_cardio') {
            return (int) $filters['target_minutes'] >= 60 ? 8 : 5;
        }
        if ($section === 'optional_additional_cardio') {
            return 10;
        }
        if ($section === 'post_workout_stretching') {
            return (int) $filters['target_minutes'] >= 60 ? 8 : 6;
        }

        $seconds = 0;
        foreach ($tags as $tag) {
            $sets = $this->estimatedSets($tag, $section, $filters);
            $workSeconds = $tag->recommended_duration_seconds
                ? (int) $tag->recommended_duration_seconds
                : ($section === 'main_workout' ? 45 : 30);
            $restSeconds = (int) ($tag->recommended_rest_seconds ?? ($section === 'main_workout' ? 60 : 30));
            $seconds += ($workSeconds * $sets) + ($restSeconds * max(0, $sets - 1)) + 20;
        }

        return (int) max(1, round($seconds / 60));
    }

    private function prescribedSets(ExerciseLibraryTag $tag, string $section, array $filters): string
    {
        if ($section === 'main_workout') {
            if ((int) $filters['target_minutes'] <= 20) {
                return '2';
            }
            if ((int) $filters['target_minutes'] >= 45 && $filters['fitness_level'] !== 'beginner') {
                return '3-4';
            }

            return '3';
        }

        if ($section === 'lower_back_core_superset') {
            return (int) $filters['target_minutes'] <= 15 ? '1' : '1-2';
        }

        if ($section === 'muscle_activation') {
            return '1-2';
        }

        return (string) ($tag->recommended_sets ?: '1');
    }

    private function estimatedSets(ExerciseLibraryTag $tag, string $section, array $filters): int
    {
        if ($section === 'main_workout') {
            return (int) $filters['target_minutes'] <= 20 ? 2 : 3;
        }
        if ($section === 'lower_back_core_superset') {
            return (int) $filters['target_minutes'] <= 15 ? 1 : 2;
        }
        if ($section === 'muscle_activation') {
            return 1;
        }

        $sets = $this->firstNumber((string) ($tag->recommended_sets ?: '1'));

        return max(1, min(2, $sets ?: 1));
    }

    private function firstNumber(string $value): int
    {
        if (preg_match('/\d+/', $value, $matches)) {
            return (int) $matches[0];
        }

        return 0;
    }

    private function modificationNote(ExerciseLibraryTag $tag): string
    {
        return match ((string) $tag->difficulty) {
            'advanced' => 'Reduce load, range, or tempo if form changes.',
            'intermediate' => 'Use the beginner version or lighter resistance when needed.',
            default => 'Use a smaller range of motion or a supported version if needed.',
        };
    }

    private function progressionNote(ExerciseLibraryTag $tag): string
    {
        if ($tag->recommended_repetitions) {
            return 'Progress by adding reps first, then resistance when form stays controlled.';
        }

        return 'Progress by increasing duration gradually while keeping intensity appropriate.';
    }

    private function mobilityFocus(array $filters): string
    {
        $seed = crc32(implode('|', [
            $filters['equipment_category'] ?? '',
            $filters['fitness_level'] ?? '',
            $filters['target_minutes'] ?? '',
            $filters['workout_type'] ?? '',
            $filters['variation'] ?? '',
        ]));
        $rotation = RoutineLibraryRules::MOBILITY_FOCUS_ROTATION;

        return $rotation[$seed % count($rotation)];
    }

    private function dinaCoachingCue(array $filters): string
    {
        $cues = [
            'Keep your ribs down and brace before every rep.',
            'Move with control before adding speed or load.',
            'Own the lowering phase and avoid rushing transitions.',
            'Push through the whole foot and keep the core connected.',
        ];
        $seed = crc32(($filters['fitness_level'] ?? '') . '|' . ($filters['equipment_category'] ?? ''));

        return $cues[$seed % count($cues)];
    }

    private function levelCanServeRoutine(string $exerciseLevel, string $routineLevel): bool
    {
        $order = [
            'beginner' => 1,
            'intermediate' => 2,
            'advanced' => 3,
        ];

        $exerciseRank = $order[$exerciseLevel] ?? 1;
        $routineRank = $order[$routineLevel] ?? 1;

        if ($routineLevel === 'beginner') {
            return $exerciseRank === 1;
        }

        return $exerciseRank <= $routineRank;
    }

    private function tagCanServeFitnessLevel(ExerciseLibraryTag $tag, string $routineLevel): bool
    {
        $safetyFlags = is_array($tag->safety_flags) ? $tag->safety_flags : [];

        if ($routineLevel === 'beginner' && ((string) $tag->impact_level === 'high' || ! empty($safetyFlags['high_impact']))) {
            return false;
        }

        return true;
    }

    private function tagMatchesUsage(ExerciseLibraryTag $tag, string $usage): bool
    {
        $flags = is_array($tag->usage_flags) ? $tag->usage_flags : [];
        $safety = is_array($tag->safety_flags) ? $tag->safety_flags : [];
        $primaryCategory = strtolower((string) $tag->primary_category);
        $trainingAdaptation = strtolower((string) $tag->training_adaptation);
        $programRole = strtolower((string) $tag->program_role);
        $type = strtolower((string) $tag->exercise_type);
        $muscle = strtolower((string) $tag->muscle_group);
        $title = strtolower((string) optional($tag->exercise)->title);
        $patterns = is_array($tag->movement_patterns) ? $tag->movement_patterns : [];
        $explicitUsageMatch = RoutineLibraryRules::usageMatches($flags, $usage);

        if ($usage === 'cardio_warm_up') {
            if ($explicitUsageMatch) {
                return empty($safety['unsafe_as_warmup'])
                    && ($safety['safe_for_warmup'] ?? true)
                    && $this->isLowImpactWarmUpCardio($type, $title);
            }

            return empty($safety['unsafe_as_warmup'])
                && ($safety['safe_for_warmup'] ?? true)
                && in_array($primaryCategory, ['', 'cardiovascular_training', 'warm_up_cardio'], true)
                && in_array($programRole, ['', 'warm_up_cardio'], true)
                && $this->isLowImpactWarmUpCardio($type, $title);
        }

        if ($usage === 'stretching') {
            if ($explicitUsageMatch) {
                return $this->isStretchingExercise($type, $title, $patterns);
            }

            return in_array($primaryCategory, ['', 'flexibility_stretching', 'post_workout_stretching'], true)
                && in_array($programRole, ['', 'cool_down_stretching', 'post_workout_stretching'], true)
                && $this->isStretchingExercise($type, $title, $patterns);
        }

        if ($usage === 'optional_cardio') {
            return in_array($primaryCategory, ['cardiovascular_training', 'steady_state_cardio', 'optional_additional_cardio', 'cool_down_cardio'], true)
                && in_array($programRole, ['', 'cardio', 'optional_cardio', 'finisher'], true)
                && in_array($type, ['cardio', 'cardio_warm_up'], true)
                && empty($safety['high_impact']);
        }

        if ($explicitUsageMatch) {
            if (in_array($usage, ['warm_up', 'lower_back_activation'], true) && ! empty($safety['unsafe_as_warmup'])) {
                return false;
            }
            return true;
        }

        return match ($usage) {
            'main_workout' => in_array($primaryCategory, ['resistance_training', 'power_explosive_training', 'balance_stability', 'corrective_exercise', 'circuit_training', 'hiit_cardio'], true)
                || in_array($programRole, ['main_workout', 'main_compound_exercise', 'accessory_exercise', 'isolation_exercise', 'superset_exercise', 'circuit_exercise', 'hiit_interval', 'finisher', 'core'], true)
                || in_array($type, ['strength', 'main', 'resistance', 'bodyweight', 'dumbbell', 'gym', 'power_explosive'], true),
            'warm_up' => empty($safety['unsafe_as_warmup']) && (
                in_array($primaryCategory, ['dynamic_warm_up', 'mobility'], true)
                || in_array($programRole, ['warm_up', 'dynamic_warm_up', 'activation'], true)
                || in_array($type, ['warm_up', 'warm-up'], true)
                || str_contains($title, 'warm up')
            ),
            'mobility' => $type === 'mobility'
                || $primaryCategory === 'mobility'
                || $trainingAdaptation === 'mobility'
                || str_contains($title, 'mobility')
                || in_array('mobility', $patterns, true),
            'muscle_activation' => empty($safety['unsafe_as_warmup']) && (
                $primaryCategory === 'muscle_activation'
                || $trainingAdaptation === 'muscle_activation'
                || $programRole === 'activation'
                || in_array($type, ['activation', 'warm_up', 'mobility', 'lower_back', 'abs', 'obliques'], true)
                || in_array($muscle, ['glutes', 'shoulders', 'upper back', 'lower back', 'abs'], true)
            ),
            'abs' => $muscle === 'abs' || $type === 'abs',
            'obliques' => $muscle === 'obliques' || $type === 'obliques',
            'lower_back_activation' => empty($safety['unsafe_as_warmup']) && ($muscle === 'lower back' || $type === 'lower_back'),
            'lower_back_strength' => $muscle === 'lower back' || $type === 'lower_back',
            default => false,
        };
    }

    private function isLowImpactWarmUpCardio(string $type, string $title): bool
    {
        if (! in_array($type, ['cardio', 'cardio_warm_up'], true)) {
            return false;
        }

        if (preg_match('/\b(lat|tricep|bicep|curl|row|dip|kick back|kickback|pull down|pulldown)\b/', $title)) {
            return false;
        }

        if (preg_match('/\b(hiit|jump|jumps|jumping|explosive|burpee|high knee|high knees|sprint|plyo|plyometric|skater|tuck|climber|jacks|rope|assault|run|running)\b/', $title)) {
            return false;
        }

        return preg_match('/\b(elliptical|treadmill walk|walking|walk|bike|cycling|cycle|stepper|rower|skierg|low impact|march|cardio)\b/', $title) === 1;
    }

    private function isStretchingExercise(string $type, string $title, array $patterns): bool
    {
        if (preg_match('/\b(warm up|warm-up|cardio|hiit|jump|jumps|jumping|explosive|burpee|sprint|climber|jacks)\b/', $title)) {
            return str_contains($title, 'stretch');
        }

        if (in_array('stretching', $patterns, true)) {
            return true;
        }

        if (in_array($type, ['stretching', 'stretch'], true) || str_contains($title, 'stretch')) {
            return true;
        }

        return preg_match('/\b(hold|release|opening|opener|mobility flow|hamstring|quad|calf|hip flexor|lat|chest opener|cobra|child pose)\b/', $title) === 1;
    }
}
