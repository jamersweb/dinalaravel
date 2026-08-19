<?php

namespace App\Services;

use App\Models\Workout;

class RoutineValidatorService
{
    public function validateWorkout(Workout $workout): array
    {
        $errors = [];

        foreach (['content_code', 'equipment_category', 'fitness_level', 'workout_type', 'language'] as $field) {
            if (empty($workout->{$field})) {
                $errors[] = [
                    'code' => 'missing_metadata',
                    'field' => $field,
                    'message' => "Missing routine metadata: {$field}.",
                ];
            }
        }

        $rows = $workout->workoutExercises()->with('exerciseDetail.libraryTag')->orderBy('group_order')->orderBy('id')->get();
        $sections = $rows->groupBy('category');
        $routineSections = is_array($workout->routine_sections) ? $workout->routine_sections : [];
        $meta = $routineSections['_meta'] ?? [];
        $targetMinutes = (int) ($meta['target_minutes'] ?? 30);
        foreach (RoutineLibraryRules::REQUIRED_WORKOUT_SECTIONS as $section) {
            if (! $sections->has($section) || $sections->get($section)->isEmpty()) {
                $errors[] = [
                    'code' => 'missing_section',
                    'section' => $section,
                    'message' => "Routine is missing required section {$section}.",
                ];
            }
        }

        foreach (RoutineLibraryRules::SECTION_MINIMUM_EXERCISES as $section => $minimum) {
            $minimum = $this->sectionMinimum($section, $targetMinutes);
            $count = $sections->get($section, collect())->count();
            if ($count < $minimum) {
                $errors[] = [
                    'code' => 'section_minimum_not_met',
                    'section' => $section,
                    'current_count' => $count,
                    'minimum_required' => $minimum,
                    'message' => "Routine section {$section} needs at least {$minimum} exercise(s).",
                ];
            }
        }

        if (($workout->routine_source === 'generated' || $meta !== []) && ($meta['section_contract'] ?? null) !== RoutineLibraryRules::ROUTINE_SECTION_CONTRACT) {
            $errors[] = [
                'code' => 'missing_master_ai_prompt_contract',
                'expected_contract' => RoutineLibraryRules::ROUTINE_SECTION_CONTRACT,
                'actual_contract' => $meta['section_contract'] ?? null,
                'message' => 'Generated routine must use the master AI prompt workout section contract.',
            ];
        }

        $this->validateSectionOrder($sections, $errors);
        $this->validateLowerBackCoreSuperset($sections, $errors);
        $this->validateDynamicWarmUpRelevance($sections, $errors);
        $this->validateFullBodyStretching($sections, $errors);

        if (isset($meta['target_minutes'], $meta['estimated_minutes'])) {
            $target = (int) $meta['target_minutes'];
            $estimated = (int) $meta['estimated_minutes'];
            $allowedDelta = max(5, (int) round($target * 0.35));
            if (abs($estimated - $target) > $allowedDelta) {
                $errors[] = [
                    'code' => 'duration_out_of_range',
                    'target_minutes' => $target,
                    'estimated_minutes' => $estimated,
                    'message' => 'Estimated workout duration is too far from the selected target duration.',
                ];
            }
        }

        $allowedEquipment = RoutineLibraryRules::allowedExerciseEquipment((string) $workout->equipment_category);
        $preferredEquipment = RoutineLibraryRules::preferredExerciseEquipment((string) $workout->equipment_category);
        $seenExerciseIds = [];
        $seenTitlesBySection = [];
        $seenMainFamilies = [];
        foreach ($sections->flatten(1) as $row) {
            if (! $row->exerciseDetail) {
                $errors[] = [
                    'code' => 'missing_exercise_reference',
                    'workout_exercise_id' => $row->id,
                    'message' => 'Workout exercise row does not reference an existing exercise.',
                ];
                continue;
            }

            $exerciseId = (int) $row->exercise_id;
            if (isset($seenExerciseIds[$exerciseId])) {
                $errors[] = [
                    'code' => 'duplicate_exercise_in_routine',
                    'exercise_id' => $exerciseId,
                    'section' => $row->category,
                    'first_section' => $seenExerciseIds[$exerciseId],
                    'message' => 'Generated routine repeats the same exercise inside one workout.',
                ];
            } else {
                $seenExerciseIds[$exerciseId] = $row->category;
            }

            $titleKey = $this->normalizeTitle((string) $row->exerciseDetail->title);
            if ($titleKey !== '') {
                $sectionTitleKey = $row->category.'|'.$titleKey;
                if (isset($seenTitlesBySection[$sectionTitleKey])) {
                    $errors[] = [
                        'code' => 'duplicate_exercise_title_in_section',
                        'exercise_id' => $exerciseId,
                        'section' => $row->category,
                        'message' => 'Generated routine repeats the same exercise title inside one section.',
                    ];
                }
                $seenTitlesBySection[$sectionTitleKey] = true;
            }

            $tag = $row->exerciseDetail->libraryTag;
            if (! $tag) {
                $errors[] = [
                    'code' => 'missing_exercise_library_tag',
                    'exercise_id' => $row->exercise_id,
                    'message' => 'Exercise is not tagged for routine library validation.',
                ];
                continue;
            }

            if (! in_array($tag->equipment_category, $allowedEquipment, true)) {
                $errors[] = [
                    'code' => 'equipment_violation',
                    'exercise_id' => $row->exercise_id,
                    'exercise_equipment' => $tag->equipment_category,
                    'routine_equipment' => $workout->equipment_category,
                    'message' => 'Exercise equipment is not allowed in this routine category.',
                ];
            }

            if ($row->category === 'main_workout' && ! in_array($tag->equipment_category, $preferredEquipment, true)) {
                $errors[] = [
                    'code' => 'main_workout_equipment_mismatch',
                    'exercise_id' => $row->exercise_id,
                    'exercise_equipment' => $tag->equipment_category,
                    'routine_equipment' => $workout->equipment_category,
                    'message' => 'Main workout exercise does not match the routine equipment category.',
                ];
            }

            if ($row->category === 'main_workout' && ! empty($tag->exercise_family)) {
                $family = strtolower((string) $tag->exercise_family);
                if (isset($seenMainFamilies[$family])) {
                    $errors[] = [
                        'code' => 'duplicate_main_workout_family',
                        'exercise_id' => $row->exercise_id,
                        'first_exercise_id' => $seenMainFamilies[$family],
                        'exercise_family' => $family,
                        'message' => 'Main workout repeats the same exercise family; use a different movement or variation family.',
                    ];
                } else {
                    $seenMainFamilies[$family] = $row->exercise_id;
                }
            }

            $safetyFlags = is_array($tag->safety_flags) ? $tag->safety_flags : [];
            if ($workout->fitness_level === 'beginner' && ((string) $tag->impact_level === 'high' || ! empty($safetyFlags['high_impact']))) {
                $errors[] = [
                    'code' => 'beginner_high_impact_exercise',
                    'exercise_id' => $row->exercise_id,
                    'section' => $row->category,
                    'message' => 'Beginner routines cannot include high-impact exercises.',
                ];
            }

            if ($workout->language && $tag->language && $tag->language !== 'no_audio' && $workout->language !== $tag->language) {
                $errors[] = [
                    'code' => 'language_mismatch',
                    'exercise_id' => $row->exercise_id,
                    'exercise_language' => $tag->language,
                    'routine_language' => $workout->language,
                    'message' => 'Exercise language does not match routine language.',
                ];
            }

            if (in_array($row->category, ['dynamic_warm_up', 'warm_up_cardio', 'optional_additional_cardio', 'post_workout_stretching'], true)) {
                if (empty($row->time)) {
                    $errors[] = [
                        'code' => 'missing_exercise_duration',
                        'workout_exercise_id' => $row->id,
                        'section' => $row->category,
                        'message' => 'Timed workout sections must include exercise duration.',
                    ];
                }
            } elseif (empty($row->sets) && empty($row->reps)) {
                $errors[] = [
                    'code' => 'missing_sets_or_reps',
                    'workout_exercise_id' => $row->id,
                    'section' => $row->category,
                    'message' => 'Strength/core sections must include sets and repetitions.',
                ];
            }

            $type = strtolower((string) $tag->exercise_type);
            $title = strtolower((string) $row->exerciseDetail->title);
            $patterns = is_array($tag->movement_patterns) ? $tag->movement_patterns : [];
            if ($row->category === 'warm_up_cardio' && ! $this->isLowImpactWarmUpCardio($type, $title)) {
                $errors[] = [
                    'code' => 'unsafe_warm_up_cardio',
                    'exercise_id' => $row->exercise_id,
                    'message' => 'Warm-up cardio must be low-impact and cannot use HIIT, jumping, sprinting, or explosive drills.',
                ];
            }

            if ($row->category === 'post_workout_stretching' && ! $this->isStretchingExercise($type, $title, $patterns)) {
                $errors[] = [
                    'code' => 'non_stretching_cooldown_exercise',
                    'exercise_id' => $row->exercise_id,
                    'message' => 'Cool-down stretching must use real stretching exercises, not warm-up or cardio movements.',
                ];
            }
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }

    private function sectionMinimum(string $section, int $targetMinutes): int
    {
        if ($section === 'dynamic_warm_up') {
            if ($targetMinutes <= 15) {
                return 3;
            }
            if ($targetMinutes <= 20) {
                return 4;
            }
        }

        if ($section === 'main_workout' && $targetMinutes <= 20) {
            return 3;
        }

        return RoutineLibraryRules::SECTION_MINIMUM_EXERCISES[$section] ?? 1;
    }

    private function validateSectionOrder($sections, array &$errors): void
    {
        $lastOrder = -1;
        foreach (RoutineLibraryRules::REQUIRED_WORKOUT_SECTIONS as $section) {
            $rows = $sections->get($section, collect());
            if ($rows->isEmpty()) {
                continue;
            }

            $order = (int) $rows->min(fn ($row) => $row->group_order ?? $row->id);
            if ($order <= $lastOrder) {
                $errors[] = [
                    'code' => 'section_order_violation',
                    'section' => $section,
                    'message' => 'Routine sections must follow the master prompt order: dynamic warm-up, warm-up cardio, activation, lower-back/core superset, main workout, optional cardio, post-workout stretching.',
                ];
            }
            $lastOrder = $order;
        }

        $optional = $sections->get('optional_additional_cardio', collect());
        if ($optional->isNotEmpty()) {
            $optionalOrder = (int) $optional->min(fn ($row) => $row->group_order ?? $row->id);
            $mainOrder = (int) $sections->get('main_workout', collect())->min(fn ($row) => $row->group_order ?? $row->id);
            $stretchOrder = (int) $sections->get('post_workout_stretching', collect())->min(fn ($row) => $row->group_order ?? $row->id);
            if ($mainOrder && $stretchOrder && ($optionalOrder <= $mainOrder || $optionalOrder >= $stretchOrder)) {
                $errors[] = [
                    'code' => 'optional_cardio_order_violation',
                    'message' => 'Optional cardio must appear after the main workout and before post-workout stretching.',
                ];
            }
        }
    }

    private function validateLowerBackCoreSuperset($sections, array &$errors): void
    {
        $rows = $sections->get('lower_back_core_superset', collect());
        if ($rows->isEmpty()) {
            return;
        }

        $hasLowerBack = false;
        $hasCore = false;
        $groupIds = [];
        foreach ($rows as $row) {
            $tag = $row->exerciseDetail?->libraryTag;
            if (! $tag) {
                continue;
            }
            $hasLowerBack = $hasLowerBack || $this->isLowerBackTag($tag);
            $hasCore = $hasCore || $this->isCoreTag($tag);
            if (! empty($row->group_id)) {
                $groupIds[] = $row->group_id;
            }
        }

        if (! $hasLowerBack || ! $hasCore) {
            $errors[] = [
                'code' => 'missing_lower_back_core_pair',
                'message' => 'Lower-back/core superset must contain at least one lower-back exercise and one core exercise.',
            ];
        }

        if (count(array_unique($groupIds)) !== 1 || count($groupIds) < 2) {
            $errors[] = [
                'code' => 'lower_back_core_not_grouped',
                'message' => 'Lower-back/core preparation must be paired as one superset group.',
            ];
        }
    }

    private function validateDynamicWarmUpRelevance($sections, array &$errors): void
    {
        $dynamicRows = $sections->get('dynamic_warm_up', collect());
        $mainRows = $sections->get('main_workout', collect());
        if ($dynamicRows->isEmpty() || $mainRows->isEmpty()) {
            return;
        }

        $dynamicProfile = $this->profileForRows($dynamicRows);
        $mainProfile = $this->profileForRows($mainRows);
        $hasOverlap = array_intersect($dynamicProfile['body_regions'], $mainProfile['body_regions']) !== []
            || array_intersect($dynamicProfile['movement_patterns'], $mainProfile['movement_patterns']) !== []
            || in_array('full_body', $dynamicProfile['body_regions'], true);

        if (! $hasOverlap) {
            $errors[] = [
                'code' => 'dynamic_warm_up_not_relevant',
                'message' => 'Dynamic warm-up should prepare at least one body region or movement pattern used in the main workout.',
            ];
        }
    }

    private function validateFullBodyStretching($sections, array &$errors): void
    {
        $rows = $sections->get('post_workout_stretching', collect());
        if ($rows->isEmpty()) {
            return;
        }

        $regions = [];
        foreach ($rows as $row) {
            $regions = array_merge($regions, $this->rowBodyRegions($row));
        }
        $regions = array_values(array_unique($regions));

        $hasFullBody = in_array('full_body', $regions, true);
        $hasUpper = $hasFullBody || array_intersect($regions, ['upper_body', 'chest', 'back', 'shoulders', 'arms']) !== [];
        $hasLower = $hasFullBody || array_intersect($regions, ['lower_body', 'glutes', 'quadriceps', 'hamstrings', 'calves']) !== [];
        $hasCoreBack = $hasFullBody || array_intersect($regions, ['core', 'abs', 'obliques', 'lower_back', 'back']) !== [];

        if (! $hasUpper || ! $hasLower || ! $hasCoreBack) {
            $errors[] = [
                'code' => 'post_workout_stretching_not_full_body',
                'covered_regions' => $regions,
                'message' => 'Post-workout stretching must cover upper body, lower body, and core/back areas.',
            ];
        }
    }

    private function profileForRows($rows): array
    {
        $profile = [
            'body_regions' => [],
            'movement_patterns' => [],
        ];

        foreach ($rows as $row) {
            $profile['body_regions'] = array_merge($profile['body_regions'], $this->rowBodyRegions($row));
            $tag = $row->exerciseDetail?->libraryTag;
            if ($tag && is_array($tag->movement_patterns)) {
                $profile['movement_patterns'] = array_merge($profile['movement_patterns'], array_map('strtolower', $tag->movement_patterns));
            }
        }

        return array_map(fn ($items) => array_values(array_unique(array_filter($items))), $profile);
    }

    private function rowBodyRegions($row): array
    {
        $tag = $row->exerciseDetail?->libraryTag;
        $regions = $tag && is_array($tag->body_regions) ? array_map('strtolower', $tag->body_regions) : [];
        $muscles = [];
        if ($tag) {
            $muscles[] = strtolower((string) $tag->muscle_group);
            foreach (is_array($tag->secondary_muscle_groups) ? $tag->secondary_muscle_groups : [] as $muscle) {
                $muscles[] = strtolower((string) $muscle);
            }
        }

        foreach ($muscles as $muscle) {
            $regions = array_merge($regions, $this->bodyRegionsForMuscle($muscle));
        }

        $title = strtolower((string) optional($row->exerciseDetail)->title);
        foreach ([
            'upper_body' => '/\b(chest|shoulder|lat|back|bicep|tricep|arm|pec)\b/',
            'lower_body' => '/\b(quad|hamstring|calf|glute|hip flexor|adductor|leg)\b/',
            'core' => '/\b(core|abs|oblique|plank)\b/',
            'back' => '/\b(back|lat|cobra|child pose)\b/',
            'lower_back' => '/\b(lower back|lumbar)\b/',
        ] as $region => $pattern) {
            if (preg_match($pattern, $title)) {
                $regions[] = $region;
            }
        }

        return array_values(array_unique(array_intersect($regions, RoutineLibraryRules::BODY_REGIONS)));
    }

    private function bodyRegionsForMuscle(string $muscle): array
    {
        $key = trim(strtolower($muscle));

        return [
            'abs' => ['core', 'abs'],
            'core' => ['core', 'abs'],
            'obliques' => ['core', 'obliques'],
            'lower back' => ['lower_back', 'back', 'core'],
            'back' => ['back', 'upper_body'],
            'lats' => ['back', 'upper_body'],
            'chest' => ['chest', 'upper_body'],
            'shoulder' => ['shoulders', 'upper_body'],
            'shoulders' => ['shoulders', 'upper_body'],
            'biceps' => ['arms', 'upper_body'],
            'triceps' => ['arms', 'upper_body'],
            'glutes' => ['glutes', 'lower_body'],
            'hamstrings' => ['hamstrings', 'lower_body'],
            'quads' => ['quadriceps', 'lower_body'],
            'quadriceps' => ['quadriceps', 'lower_body'],
            'calves' => ['calves', 'lower_body'],
        ][$key] ?? [];
    }

    private function isLowerBackTag($tag): bool
    {
        $flags = is_array($tag->usage_flags) ? $tag->usage_flags : [];
        $regions = is_array($tag->body_regions) ? $tag->body_regions : [];

        return strtolower((string) $tag->muscle_group) === 'lower back'
            || strtolower((string) $tag->exercise_type) === 'lower_back'
            || in_array('lower_back', $regions, true)
            || ! empty($flags['lower_back_activation'])
            || ! empty($flags['lower_back_strength']);
    }

    private function isCoreTag($tag): bool
    {
        $flags = is_array($tag->usage_flags) ? $tag->usage_flags : [];
        $regions = is_array($tag->body_regions) ? $tag->body_regions : [];
        $muscle = strtolower((string) $tag->muscle_group);
        $type = strtolower((string) $tag->exercise_type);

        return in_array($muscle, ['abs', 'obliques', 'core'], true)
            || in_array($type, ['abs', 'obliques'], true)
            || array_intersect(['core', 'abs', 'obliques'], $regions) !== []
            || ! empty($flags['abs'])
            || ! empty($flags['obliques']);
    }

    private function normalizeTitle(string $title): string
    {
        $title = strtolower(trim($title));
        $title = preg_replace('/\s+/', ' ', $title) ?? $title;

        return preg_replace('/\b(day|part|variation)\s*\d+\b/', '', $title) ?? $title;
    }

    private function isLowImpactWarmUpCardio(string $type, string $title): bool
    {
        if (! in_array($type, ['cardio', 'cardio_warm_up'], true)) {
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
