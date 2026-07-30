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
                for ($variation = 1; $variation <= $filters['variations_per_type']; $variation++) {
                    foreach ($typeCodes as $typeCode) {
                        if (count($created) >= $filters['limit']) {
                            break 2;
                        }

                        $routineId = RoutineLibraryRules::routineId(
                            $filters['equipment_category'],
                            $filters['fitness_level'],
                            $typeCode,
                            $filters['language'],
                            $variation
                        );

                        if (Workout::where('content_code', $routineId)->exists()) {
                            continue;
                        }

                        $workout = $this->createRoutine($batch, $filters, $typeCode, $variation, $routineId);
                        $created[] = $workout->id;
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
        $sections = [
            'warm_up_cardio' => $this->pick($filters, 'cardio_warm_up', 1, $variation),
            'mobility_dynamic_warm_up' => array_merge(
                $this->pick($filters, 'warm_up', 1, $variation),
                $this->pick($filters, 'mobility', 1, $variation + 1)
            ),
            'muscle_activation' => $this->pick($filters, 'muscle_activation', 1, $variation),
            'core_lower_back_preparation' => $this->pick($filters, 'lower_back_activation', 1, $variation),
            'main_workout' => $this->pick($filters, 'main_workout', $mainExerciseCount, $variation),
            'core_obliques' => array_merge(
                $this->pick($filters, 'abs', 1, $variation),
                $this->pick($filters, 'obliques', 1, $variation + 1)
            ),
            'lower_back_strengthening' => $this->pick($filters, 'lower_back_strength', 1, $variation),
            'optional_additional_cardio' => $this->pick($filters, 'cardio_warm_up', 1, $variation + 3),
            'cool_down_stretching' => $this->pick($filters, 'stretching', 1, $variation),
        ];
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
            foreach ($tags as $index => $tag) {
                WorkoutExercise::create($this->workoutExercisePayload($workout->id, $tag, $section, $index, $filters));
            }
        }

        return $workout;
    }

    private function pick(array $filters, string $usage, int $count, int $offset): array
    {
        $allowedEquipment = RoutineLibraryRules::allowedExerciseEquipment($filters['equipment_category']);
        $tags = ExerciseLibraryTag::with('exercise:id,title,video_type,video_url,image,custom_thumbnail')
            ->where('language', $filters['language'])
            ->where('approved_for_generation', true)
            ->whereIn('equipment_category', $allowedEquipment)
            ->get()
            ->filter(fn ($tag) => $this->levelCanServeRoutine((string) $tag->difficulty, $filters['fitness_level']))
            ->filter(fn ($tag) => $this->tagMatchesUsage($tag, $usage))
            ->values();

        if ($tags->count() < $count) {
            throw new RuntimeException("Not enough approved exercises for usage {$usage}.");
        }

        $selected = [];
        for ($i = 0; $i < $count; $i++) {
            $selected[] = $tags[($offset + $i) % $tags->count()];
        }

        return $selected;
    }

    private function workoutExercisePayload(int $workoutId, ExerciseLibraryTag $tag, string $section, int $index, array $filters): array
    {
        $isTimed = in_array($section, [
            'warm_up_cardio',
            'mobility_dynamic_warm_up',
            'optional_additional_cardio',
            'cool_down_stretching',
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
            'group_order' => $this->sectionOrder($section) + $index,
        ];
    }

    private function coverImage(array $sections): ?string
    {
        foreach ($sections as $tags) {
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

        return [
            'language' => RoutineLibraryRules::normalizeLanguage($filters['language'] ?? 'en'),
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
                'section_contract' => 'ai_program_builder_phase_3',
                'dina_methodology' => [
                    'mandatory_usage' => RoutineLibraryRules::DINA_MANDATORY_USAGE,
                    'mobility_focus' => $this->mobilityFocus($filters),
                    'coaching_cue' => $this->dinaCoachingCue($filters),
                    'rules' => [
                        'abs_obliques_lower_back_every_session',
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
            'warm_up_cardio' => 'Keep the pace easy to moderate and gradually increase body temperature.',
            'mobility_dynamic_warm_up' => 'Move smoothly through a pain-free range and prepare the joints used today.',
            'muscle_activation' => 'Use controlled tempo to activate the main muscles before heavier work.',
            'core_lower_back_preparation' => 'Brace gently and prepare the core and lower back without fatigue.',
            'main_workout' => 'Use clean form and leave 1-2 reps in reserve unless the program states otherwise.',
            'core_obliques' => 'Brace and avoid pulling through the neck or lower back.',
            'lower_back_strengthening' => 'Strengthen the posterior core with controlled tempo and no painful range.',
            'optional_additional_cardio' => 'Optional calorie-expenditure support; skip when recovery, pain, or time is a concern.',
            'cool_down_stretching' => 'Hold each stretch without bouncing and breathe slowly.',
            default => '',
        };
    }

    private function sectionOrder(string $section): int
    {
        return array_search($section, [
            'warm_up_cardio',
            'mobility_dynamic_warm_up',
            'muscle_activation',
            'core_lower_back_preparation',
            'main_workout',
            'core_obliques',
            'lower_back_strengthening',
            'optional_additional_cardio',
            'cool_down_stretching',
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
            $minutes <= 15 => 2,
            $minutes <= 20 => 3,
            $minutes <= 30 => 4,
            $minutes <= 45 => 5,
            default => 6,
        };

        if ($filters['fitness_level'] === 'advanced' && $minutes >= 45) {
            return min(7, $base + 1);
        }

        return $base;
    }

    private function displayDuration(ExerciseLibraryTag $tag, string $section): string
    {
        if ($section === 'warm_up_cardio') {
            return '3-8 min';
        }
        if ($section === 'optional_additional_cardio') {
            return '10-15 min';
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
            if ($section === 'optional_additional_cardio') {
                continue;
            }
            $seconds += $this->estimateSectionMinutes($section, $tags, $filters) * 60;
        }

        return (int) max(1, round($seconds / 60));
    }

    private function estimateSectionMinutes(string $section, array $tags, array $filters): int
    {
        if ((int) $filters['target_minutes'] <= 15) {
            return match ($section) {
                'warm_up_cardio' => 2,
                'mobility_dynamic_warm_up' => 2,
                'muscle_activation' => 1,
                'core_lower_back_preparation' => 1,
                'main_workout' => 5,
                'core_obliques' => 2,
                'lower_back_strengthening' => 1,
                'cool_down_stretching' => 2,
                'optional_additional_cardio' => 5,
                default => 1,
            };
        }

        if ((int) $filters['target_minutes'] <= 30) {
            return match ($section) {
                'warm_up_cardio' => 4,
                'mobility_dynamic_warm_up' => 3,
                'muscle_activation' => 2,
                'core_lower_back_preparation' => 2,
                'main_workout' => 12,
                'core_obliques' => 4,
                'lower_back_strengthening' => 2,
                'cool_down_stretching' => 3,
                'optional_additional_cardio' => 8,
                default => 1,
            };
        }

        if ($section === 'warm_up_cardio') {
            return 5;
        }
        if ($section === 'optional_additional_cardio') {
            return 10;
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

        if (in_array($section, ['muscle_activation', 'core_lower_back_preparation', 'core_obliques', 'lower_back_strengthening'], true)) {
            return '2';
        }

        return (string) ($tag->recommended_sets ?: '1');
    }

    private function estimatedSets(ExerciseLibraryTag $tag, string $section, array $filters): int
    {
        if ($section === 'main_workout') {
            return (int) $filters['target_minutes'] <= 20 ? 2 : 3;
        }
        if (in_array($section, ['muscle_activation', 'core_lower_back_preparation', 'core_obliques', 'lower_back_strengthening'], true)) {
            return 2;
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

    private function tagMatchesUsage(ExerciseLibraryTag $tag, string $usage): bool
    {
        $flags = is_array($tag->usage_flags) ? $tag->usage_flags : [];
        $type = strtolower((string) $tag->exercise_type);
        $muscle = strtolower((string) $tag->muscle_group);
        $title = strtolower((string) optional($tag->exercise)->title);

        if ($usage === 'cardio_warm_up') {
            return $this->isCardioExercise($type, $title);
        }

        if (RoutineLibraryRules::usageMatches($flags, $usage)) {
            return true;
        }

        return match ($usage) {
            'main_workout' => in_array($type, ['strength', 'main', 'resistance', 'bodyweight', 'dumbbell', 'gym'], true),
            'warm_up' => in_array($type, ['warm_up', 'warm-up'], true) || str_contains($title, 'warm up'),
            'mobility' => $type === 'mobility'
                || str_contains($title, 'mobility')
                || in_array('mobility', is_array($tag->movement_patterns) ? $tag->movement_patterns : [], true),
            'stretching' => in_array($type, ['stretching', 'stretch'], true) || str_contains($title, 'stretch'),
            'muscle_activation' => in_array($type, ['warm_up', 'mobility', 'lower_back', 'abs', 'obliques'], true)
                || in_array($muscle, ['glutes', 'shoulders', 'upper back', 'lower back', 'abs'], true),
            'abs' => $muscle === 'abs' || $type === 'abs',
            'obliques' => $muscle === 'obliques' || $type === 'obliques',
            'lower_back_activation', 'lower_back_strength' => $muscle === 'lower back' || $type === 'lower_back',
            default => false,
        };
    }

    private function isCardioExercise(string $type, string $title): bool
    {
        if (! in_array($type, ['cardio', 'cardio_warm_up', 'hiit'], true)) {
            return false;
        }

        if (preg_match('/\b(lat|tricep|bicep|curl|row|dip|kick back|kickback|pull down|pulldown)\b/', $title)) {
            return false;
        }

        return preg_match('/\b(elliptical|treadmill|bike|cycling|run|walk|jump|jacks|knee|climber|rope|step|hiit|cardio|skierg|assault|burpee)\b/', $title) === 1;
    }
}
