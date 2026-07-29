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
        foreach ($sections->flatten(1) as $row) {
            if (! $row->exerciseDetail) {
                $errors[] = [
                    'code' => 'missing_exercise_reference',
                    'workout_exercise_id' => $row->id,
                    'message' => 'Workout exercise row does not reference an existing exercise.',
                ];
                continue;
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
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }
}
