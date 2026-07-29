<?php

namespace App\Services;

use App\Models\AiProgramWeekDay;
use App\Models\Program;
use App\Models\Workout;
use Illuminate\Support\Collection;

class AiProgramValidatorService
{
    public function __construct(
        private RoutineValidatorService $routineValidator
    ) {
    }

    public function validateProgram(Program $program): array
    {
        $errors = [];
        $definition = $this->launchDefinition($program);
        $days = $program->aiWeekDays()
            ->with(['workout.workoutExercises.exerciseDetail.libraryTag', 'programPhase'])
            ->get();
        $weeks = $days->groupBy('week_no');
        $phaseIds = $program->programPhases()->pluck('id')->all();

        if ($weeks->isEmpty()) {
            $errors[] = [
                'code' => 'missing_ai_week_days',
                'message' => 'Program has no AI week/day schedule.',
            ];
        }

        if ($definition) {
            $errors = array_merge($errors, $this->validateProgramMetadata($program, $definition));
        }

        foreach ($weeks as $weekNo => $weekDays) {
            $dayNumbers = $weekDays->pluck('day_no')->sort()->values()->all();
            if ($dayNumbers !== [1, 2, 3, 4, 5, 6, 7]) {
                $errors[] = [
                    'code' => 'incomplete_week',
                    'week_no' => (int) $weekNo,
                    'day_numbers' => $dayNumbers,
                    'message' => 'Every AI-generated program week must show days 1 through 7.',
                ];
            }

            $duplicates = $weekDays->groupBy('day_no')->filter(fn ($rows) => $rows->count() > 1)->keys()->values()->all();
            if ($duplicates !== []) {
                $errors[] = [
                    'code' => 'duplicate_week_day',
                    'week_no' => (int) $weekNo,
                    'day_numbers' => $duplicates,
                    'message' => 'Program week has duplicate day rows.',
                ];
            }

            if ($definition) {
                $errors = array_merge($errors, $this->validateWeeklyBalance($weekDays, (int) $weekNo, $definition));
                $errors = array_merge($errors, $this->validateConsecutiveTraining($weekDays, (int) $weekNo));
            }

            foreach ($weekDays as $day) {
                if (! in_array($day->day_type, $this->allowedDayTypes(), true)) {
                    $errors[] = [
                        'code' => 'invalid_day_type',
                        'week_no' => $day->week_no,
                        'day_no' => $day->day_no,
                        'day_type' => $day->day_type,
                        'message' => 'Day type must be workout, rest, or active_recovery.',
                    ];
                }

                if ($day->day_type === AiProgramWeekDay::TYPE_WORKOUT && empty($day->workout_id)) {
                    $errors[] = [
                        'code' => 'workout_day_missing_workout',
                        'week_no' => $day->week_no,
                        'day_no' => $day->day_no,
                        'message' => 'Workout days must reference a workout.',
                    ];
                }

                if ($day->day_type !== AiProgramWeekDay::TYPE_WORKOUT && ! empty($day->workout_id)) {
                    $errors[] = [
                        'code' => 'recovery_day_has_workout',
                        'week_no' => $day->week_no,
                        'day_no' => $day->day_no,
                        'message' => 'Rest and active recovery days must not reference a workout routine.',
                    ];
                }

                if ($day->program_phase_id && ! in_array($day->program_phase_id, $phaseIds, true)) {
                    $errors[] = [
                        'code' => 'invalid_program_phase_reference',
                        'week_no' => $day->week_no,
                        'day_no' => $day->day_no,
                        'program_phase_id' => $day->program_phase_id,
                        'message' => 'AI week day references a phase outside this program.',
                    ];
                }

                if ($day->day_type === AiProgramWeekDay::TYPE_WORKOUT && $day->workout) {
                    $errors = array_merge($errors, $this->validateWorkoutForProgram($program, $day->workout, $day, $definition));
                }

                if ($day->day_type === AiProgramWeekDay::TYPE_WORKOUT && empty($day->progression_notes)) {
                    $errors[] = [
                        'code' => 'missing_progression_notes',
                        'week_no' => $day->week_no,
                        'day_no' => $day->day_no,
                        'message' => 'Workout days must include progression notes.',
                    ];
                }
            }
        }

        $errors = array_merge($errors, $this->validateProgressionPattern($weeks));

        return [
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }

    public function allowedDayTypes(): array
    {
        return [
            AiProgramWeekDay::TYPE_WORKOUT,
            AiProgramWeekDay::TYPE_REST,
            AiProgramWeekDay::TYPE_ACTIVE_RECOVERY,
        ];
    }

    private function validateProgramMetadata(Program $program, array $definition): array
    {
        $errors = [];

        if ($program->level && $program->level !== $definition['level']) {
            $errors[] = [
                'code' => 'program_level_mismatch',
                'program_level' => $program->level,
                'expected_level' => $definition['level'],
                'message' => 'Program level does not match its launch matrix definition.',
            ];
        }

        if ($program->language && ! in_array($program->language, RoutineLibraryRules::CONTENT_LANGUAGES, true)) {
            $errors[] = [
                'code' => 'unsupported_program_language',
                'language' => $program->language,
                'message' => 'Launch programs must use an available content language.',
            ];
        }

        return $errors;
    }

    private function validateWeeklyBalance(Collection $weekDays, int $weekNo, array $definition): array
    {
        $errors = [];
        $workoutDays = $weekDays->where('day_type', AiProgramWeekDay::TYPE_WORKOUT)->count();
        [$minDays, $maxDays] = $this->frequencyBounds($definition['days_per_week']);

        if ($workoutDays < $minDays || $workoutDays > $maxDays) {
            $errors[] = [
                'code' => 'weekly_frequency_mismatch',
                'week_no' => $weekNo,
                'workout_days' => $workoutDays,
                'expected' => $definition['days_per_week'],
                'message' => 'Weekly workout count does not match the launch matrix frequency.',
            ];
        }

        if ($weekDays->where('day_type', AiProgramWeekDay::TYPE_REST)->isEmpty()) {
            $errors[] = [
                'code' => 'missing_rest_day',
                'week_no' => $weekNo,
                'message' => 'Every program week must include at least one rest day.',
            ];
        }

        return $errors;
    }

    private function validateConsecutiveTraining(Collection $weekDays, int $weekNo): array
    {
        $errors = [];
        $ordered = $weekDays->sortBy('day_no')->values();

        for ($index = 1; $index < $ordered->count(); $index++) {
            $previous = $ordered[$index - 1];
            $current = $ordered[$index];
            if ($previous->day_type !== AiProgramWeekDay::TYPE_WORKOUT || $current->day_type !== AiProgramWeekDay::TYPE_WORKOUT) {
                continue;
            }

            $previousProfile = $this->workoutProfile($previous->workout);
            $currentProfile = $this->workoutProfile($current->workout);
            if (! $previousProfile['high_intensity'] || ! $currentProfile['high_intensity']) {
                continue;
            }

            $overlap = array_values(array_intersect($previousProfile['primary_muscles'], $currentProfile['primary_muscles']));
            if ($overlap !== [] && $previous->workout?->workout_type === $current->workout?->workout_type) {
                $errors[] = [
                    'code' => 'consecutive_high_intensity_same_muscle',
                    'week_no' => $weekNo,
                    'day_no' => $current->day_no,
                    'previous_day_no' => $previous->day_no,
                    'muscle_groups' => $overlap,
                    'message' => 'High-intensity workouts should not train the same primary muscle group on consecutive calendar days.',
                ];
            }
        }

        return $errors;
    }

    private function validateWorkoutForProgram(Program $program, Workout $workout, AiProgramWeekDay $day, ?array $definition): array
    {
        $errors = [];
        $context = [
            'week_no' => $day->week_no,
            'day_no' => $day->day_no,
            'workout_id' => $workout->id,
        ];

        $routineValidation = $this->routineValidator->validateWorkout($workout);
        foreach ($routineValidation['errors'] as $error) {
            $errors[] = array_merge($context, [
                'code' => 'routine_validation_failed',
                'routine_error' => $error,
                'message' => 'Program contains a routine that fails routine-level validation.',
            ]);
        }

        if ($program->language && $workout->language && $program->language !== $workout->language) {
            $errors[] = array_merge($context, [
                'code' => 'program_workout_language_mismatch',
                'program_language' => $program->language,
                'workout_language' => $workout->language,
                'message' => 'Workout language does not match program language.',
            ]);
        }

        if ($definition) {
            $allowedEquipment = RoutineLibraryRules::allowedExerciseEquipment($definition['equipment_category']);
            if (! in_array($workout->equipment_category, $allowedEquipment, true)) {
                $errors[] = array_merge($context, [
                    'code' => 'program_workout_equipment_violation',
                    'program_equipment' => $definition['equipment_category'],
                    'workout_equipment' => $workout->equipment_category,
                    'message' => 'Workout equipment is not allowed for this launch program.',
                ]);
            }

            if ($workout->fitness_level && $workout->fitness_level !== $definition['level']) {
                $errors[] = array_merge($context, [
                    'code' => 'program_workout_level_mismatch',
                    'program_level' => $definition['level'],
                    'workout_level' => $workout->fitness_level,
                    'message' => 'Workout level does not match this launch program.',
                ]);
            }

            if (! $this->durationWithinBounds((int) $day->estimated_minutes, (string) $definition['minutes'])) {
                $errors[] = array_merge($context, [
                    'code' => 'program_day_duration_out_of_range',
                    'estimated_minutes' => $day->estimated_minutes,
                    'expected_minutes' => $definition['minutes'],
                    'message' => 'Scheduled workout duration does not match the launch matrix duration.',
                ]);
            }
        }

        $errors = array_merge($errors, $this->validateExerciseRowsForProgram($program, $workout, $day, $definition));

        $profile = $this->workoutProfile($workout);
        if (($definition['level'] ?? $program->level) === 'beginner' && $profile['high_impact_count'] > 3) {
            $errors[] = array_merge($context, [
                'code' => 'excessive_beginner_high_impact',
                'high_impact_count' => $profile['high_impact_count'],
                'message' => 'Beginner programs should not include multiple high-impact exercises in one routine.',
            ]);
        }

        return $errors;
    }

    private function validateExerciseRowsForProgram(Program $program, Workout $workout, AiProgramWeekDay $day, ?array $definition): array
    {
        $errors = [];
        $allowedEquipment = $definition
            ? RoutineLibraryRules::allowedExerciseEquipment($definition['equipment_category'])
            : RoutineLibraryRules::allowedExerciseEquipment((string) $workout->equipment_category);

        $rows = $workout->relationLoaded('workoutExercises')
            ? $workout->workoutExercises
            : $workout->workoutExercises()->with('exerciseDetail.libraryTag')->get();

        foreach ($rows as $row) {
            $exercise = $row->exerciseDetail;
            $tag = $exercise?->libraryTag;
            $context = [
                'week_no' => $day->week_no,
                'day_no' => $day->day_no,
                'workout_id' => $workout->id,
                'workout_exercise_id' => $row->id,
                'exercise_id' => $row->exercise_id,
            ];

            if (! $exercise) {
                continue;
            }

            if (empty($exercise->getRawOriginal('video_url')) || empty($exercise->video_type)) {
                $errors[] = array_merge($context, [
                    'code' => 'missing_exercise_video',
                    'message' => 'Program exercise is missing a video assignment.',
                ]);
            }

            if (! $tag) {
                continue;
            }

            if (! in_array($tag->equipment_category, $allowedEquipment, true)) {
                $errors[] = array_merge($context, [
                    'code' => 'program_exercise_equipment_violation',
                    'exercise_equipment' => $tag->equipment_category,
                    'program_equipment' => $definition['equipment_category'] ?? $workout->equipment_category,
                    'message' => 'Exercise equipment is not allowed for this launch program.',
                ]);
            }

            if ($program->language && $tag->language && $program->language !== $tag->language) {
                $errors[] = array_merge($context, [
                    'code' => 'program_exercise_language_mismatch',
                    'program_language' => $program->language,
                    'exercise_language' => $tag->language,
                    'message' => 'Exercise language does not match program language.',
                ]);
            }

            if (in_array('injury_review_required', (array) $tag->usage_flags, true)) {
                $errors[] = array_merge($context, [
                    'code' => 'unsafe_injury_review_required',
                    'message' => 'Exercise is tagged as requiring injury review before automated program use.',
                ]);
            }
        }

        return $errors;
    }

    private function validateProgressionPattern(Collection $weeks): array
    {
        $focusByWeek = $weeks
            ->map(fn (Collection $days) => $days->firstWhere('day_type', AiProgramWeekDay::TYPE_WORKOUT)?->progression_notes['focus'] ?? null)
            ->filter()
            ->values();

        if ($focusByWeek->isEmpty() || $focusByWeek->unique()->count() >= min(3, $focusByWeek->count())) {
            return [];
        }

        return [[
            'code' => 'flat_progression_pattern',
            'message' => 'Program progression should change across training blocks.',
        ]];
    }

    private function workoutProfile(?Workout $workout): array
    {
        if (! $workout) {
            return [
                'primary_muscles' => [],
                'high_intensity' => false,
                'high_impact_count' => 0,
            ];
        }

        $rows = $workout->relationLoaded('workoutExercises')
            ? $workout->workoutExercises
            : $workout->workoutExercises()->with('exerciseDetail.libraryTag')->get();

        $muscles = [];
        $highIntensity = false;
        $highImpactCount = 0;

        foreach ($rows as $row) {
            $exercise = $row->exerciseDetail;
            $tag = $exercise?->libraryTag;
            if (! $exercise || ! $tag) {
                continue;
            }

            if (! empty($tag->muscle_group)) {
                $muscles[] = (string) $tag->muscle_group;
            }
            if ($tag->intensity_level === 'high') {
                $highIntensity = true;
            }
            if ($tag->impact_level === 'high') {
                $highImpactCount++;
            }
        }

        return [
            'primary_muscles' => array_values(array_unique($muscles)),
            'high_intensity' => $highIntensity,
            'high_impact_count' => $highImpactCount,
        ];
    }

    private function launchDefinition(Program $program): ?array
    {
        $number = null;
        if (preg_match('/AI-LAUNCH-(\d{2})-/i', (string) $program->content_code, $matches)) {
            $number = (int) $matches[1];
        }

        return collect(RoutineLibraryRules::LAUNCH_MATRIX_PROGRAMS)
            ->first(function (array $definition) use ($number, $program) {
                if ($number !== null) {
                    return (int) $definition['number'] === $number;
                }

                return $definition['name'] === $program->title;
            });
    }

    private function frequencyBounds(int|string $daysPerWeek): array
    {
        if (is_string($daysPerWeek) && str_contains($daysPerWeek, '-')) {
            $parts = array_map('intval', explode('-', $daysPerWeek));

            return [min($parts), max($parts)];
        }

        $days = (int) $daysPerWeek;

        return [$days, $days];
    }

    private function durationWithinBounds(int $estimatedMinutes, string $minutes): bool
    {
        if ($estimatedMinutes <= 0) {
            return false;
        }

        if (! preg_match_all('/\d+/', $minutes, $matches) || $matches[0] === []) {
            return true;
        }

        $numbers = array_map('intval', $matches[0]);
        $min = min($numbers);
        $max = max($numbers);

        $tolerance = max(5, (int) round($max * 0.35));

        return $estimatedMinutes >= ($min - $tolerance) && $estimatedMinutes <= ($max + $tolerance);
    }
}
