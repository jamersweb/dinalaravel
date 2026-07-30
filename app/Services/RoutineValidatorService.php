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

        $sections = $workout->workoutExercises()->with('exerciseDetail.libraryTag')->get()->groupBy('category');
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

        $routineSections = is_array($workout->routine_sections) ? $workout->routine_sections : [];
        $meta = $routineSections['_meta'] ?? [];
        if (($workout->routine_source === 'generated' || $meta !== []) && ($meta['section_contract'] ?? null) !== 'ai_program_builder_phase_3') {
            $errors[] = [
                'code' => 'missing_phase_3_contract',
                'message' => 'Generated routine must use the AI program-builder Phase 3 workout section contract.',
            ];
        }

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

            if ($workout->language && $tag->language && $workout->language !== $tag->language) {
                $errors[] = [
                    'code' => 'language_mismatch',
                    'exercise_id' => $row->exercise_id,
                    'exercise_language' => $tag->language,
                    'routine_language' => $workout->language,
                    'message' => 'Exercise language does not match routine language.',
                ];
            }

            if (in_array($row->category, ['warm_up_cardio', 'mobility_dynamic_warm_up', 'optional_additional_cardio', 'cool_down_stretching'], true)) {
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

            if ($row->category === 'cool_down_stretching' && ! $this->isStretchingExercise($type, $title, $patterns)) {
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
