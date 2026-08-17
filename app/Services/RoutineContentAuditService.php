<?php

namespace App\Services;

use App\Models\Exercise;
use App\Models\ExerciseLibraryTag;
use Illuminate\Support\Collection;

class RoutineContentAuditService
{
    public function audit(array $filters = []): array
    {
        $language = $filters['language'] ?? null;
        $equipment = $filters['equipment_category'] ?? null;

        $tags = ExerciseLibraryTag::query()
            ->with('exercise:id,title,language,video_url,video_type')
            ->when($language, fn ($q) => $q->where('language', $language))
            ->when($equipment, function ($q) use ($equipment) {
                $q->whereIn('equipment_category', RoutineLibraryRules::allowedExerciseEquipment($equipment));
            })
            ->get();

        $approved = $tags->where('approved_for_generation', true);
        $launchTags = ExerciseLibraryTag::query()
            ->with('exercise:id,title,language,video_url,video_type')
            ->get();
        $untaggedExerciseCount = Exercise::query()
            ->whereDoesntHave('libraryTag')
            ->count();

        $coverage = $this->coverage($approved);
        $missing = $this->missingCoverage($tags, $approved, $filters);

        return [
            'status' => $missing === [] ? 'ready' : 'blocked',
            'filters' => $filters,
            'total_tagged_exercises' => $tags->count(),
            'approved_for_generation' => $approved->count(),
            'untagged_exercises' => $untaggedExerciseCount,
            'coverage' => $coverage,
            'missing_content' => $missing,
            'launch_matrix_readiness' => $this->launchMatrixReadiness($launchTags),
            'launch_matrix_scope' => 'all_exercise_library_tags',
            'required_usage_flags' => RoutineLibraryRules::REQUIRED_AUDIT_USAGE,
        ];
    }

    private function coverage(Collection $tags): array
    {
        $coverage = [
            'by_language' => [],
            'by_equipment' => [],
            'by_usage' => [],
        ];

        foreach ($tags as $tag) {
            $coverage['by_language'][$tag->language] = ($coverage['by_language'][$tag->language] ?? 0) + 1;
            $coverage['by_equipment'][$tag->equipment_category] = ($coverage['by_equipment'][$tag->equipment_category] ?? 0) + 1;

            foreach (array_keys(RoutineLibraryRules::REQUIRED_AUDIT_USAGE) as $usage) {
                if ($this->tagMatchesUsage($tag, $usage)) {
                    $coverage['by_usage'][$usage] = ($coverage['by_usage'][$usage] ?? 0) + 1;
                }
            }
        }

        return $coverage;
    }

    private function missingCoverage(Collection $tags, Collection $approved, array $filters): array
    {
        $languages = isset($filters['language'])
            ? [$filters['language']]
            : RoutineLibraryRules::CONTENT_LANGUAGES;

        $equipmentCategories = isset($filters['equipment_category'])
            ? [$filters['equipment_category']]
            : RoutineLibraryRules::EQUIPMENT_CATEGORIES;

        $missing = [];
        foreach ($languages as $language) {
            foreach ($equipmentCategories as $equipment) {
                $allowedEquipment = RoutineLibraryRules::allowedExerciseEquipment($equipment);
                $preferredEquipment = RoutineLibraryRules::preferredExerciseEquipment($equipment);
                $pool = $approved
                    ->where('language', $language)
                    ->whereIn('equipment_category', $allowedEquipment);
                $reviewablePool = $tags
                    ->where('language', $language)
                    ->whereIn('equipment_category', $allowedEquipment)
                    ->reject(fn (ExerciseLibraryTag $tag) => $tag->review_status === 'rejected');

                foreach (RoutineLibraryRules::REQUIRED_AUDIT_USAGE as $usage => $label) {
                    $minimum = $this->minimumForUsage($usage, ['level' => 'beginner']);
                    $usagePool = $usage === 'main_workout'
                        ? $pool->whereIn('equipment_category', $preferredEquipment)
                        : $pool;
                    $reviewableUsagePool = $usage === 'main_workout'
                        ? $reviewablePool->whereIn('equipment_category', $preferredEquipment)
                        : $reviewablePool;
                    $count = $usagePool->filter(fn ($tag) => $this->tagMatchesUsage($tag, $usage))->count();

                    if ($count < $minimum) {
                        $reviewableCount = $reviewableUsagePool
                            ->filter(fn ($tag) => $this->tagMatchesUsage($tag, $usage))
                            ->count();
                        $pendingReviewCount = $reviewableUsagePool
                            ->where('review_status', 'pending_review')
                            ->filter(fn ($tag) => $this->tagMatchesUsage($tag, $usage))
                            ->count();

                        $missing[] = [
                            'language' => $language,
                            'equipment_category' => $equipment,
                            'usage' => $usage,
                            'label' => $label,
                            'approved_count' => $count,
                            'reviewable_count' => $reviewableCount,
                            'pending_review_count' => $pendingReviewCount,
                            'minimum_required' => $minimum,
                        ];
                    }
                }
            }
        }

        return $missing;
    }

    private function launchMatrixReadiness(Collection $tags): array
    {
        return collect(RoutineLibraryRules::LAUNCH_MATRIX_PROGRAMS)
            ->map(function (array $program) use ($tags) {
                $languageReadiness = [];
                foreach (RoutineLibraryRules::CONTENT_LANGUAGES as $language) {
                    $languageReadiness[$language] = $this->programReadiness($tags, $program, $language);
                }

                $readyLanguages = collect($languageReadiness)
                    ->filter(fn (array $readiness) => $readiness['status'] === 'ready')
                    ->keys()
                    ->values()
                    ->all();
                $reviewableLanguages = collect($languageReadiness)
                    ->filter(fn (array $readiness) => in_array($readiness['status'], ['ready', 'needs_review'], true))
                    ->keys()
                    ->values()
                    ->all();

                return [
                    'number' => $program['number'],
                    'name' => $program['name'],
                    'level' => $program['level'],
                    'equipment_category' => $program['equipment_category'],
                    'days_per_week' => $program['days_per_week'],
                    'minutes' => $program['minutes'],
                    'status' => count($readyLanguages) >= 2
                        ? 'ready'
                        : (count($reviewableLanguages) >= 2 ? 'needs_review' : 'blocked'),
                    'deferred_languages' => ['no_audio'],
                    'ready_languages' => $readyLanguages,
                    'reviewable_languages' => $reviewableLanguages,
                    'languages' => $languageReadiness,
                ];
            })
            ->values()
            ->all();
    }

    private function programReadiness(Collection $tags, array $program, string $language): array
    {
        $allowedEquipment = RoutineLibraryRules::allowedExerciseEquipment($program['equipment_category']);
        $preferredEquipment = RoutineLibraryRules::preferredExerciseEquipment($program['equipment_category']);
        $pool = $tags
            ->where('language', $language)
            ->whereIn('equipment_category', $allowedEquipment)
            ->filter(function (ExerciseLibraryTag $tag) use ($program) {
                return $this->levelCanServeProgram((string) $tag->difficulty, (string) $program['level']);
            });
        $approvedPool = $pool->where('approved_for_generation', true);
        $reviewablePool = $pool->reject(fn (ExerciseLibraryTag $tag) => $tag->review_status === 'rejected');

        $missingApproved = [];
        $missingReviewable = [];
        $coverage = [];
        foreach (RoutineLibraryRules::REQUIRED_AUDIT_USAGE as $usage => $label) {
            $minimum = $this->minimumForProgramUsage($usage, $program);
            $approvedUsagePool = $usage === 'main_workout'
                ? $approvedPool->whereIn('equipment_category', $preferredEquipment)
                : $approvedPool;
            $reviewableUsagePool = $usage === 'main_workout'
                ? $reviewablePool->whereIn('equipment_category', $preferredEquipment)
                : $reviewablePool;
            $approvedCount = $approvedUsagePool->filter(fn ($tag) => $this->tagMatchesUsage($tag, $usage))->count();
            $reviewableCount = $reviewableUsagePool->filter(fn ($tag) => $this->tagMatchesUsage($tag, $usage))->count();
            $pendingReviewCount = $pool
                ->where('review_status', 'pending_review')
                ->when($usage === 'main_workout', fn ($items) => $items->whereIn('equipment_category', $preferredEquipment))
                ->filter(fn ($tag) => $this->tagMatchesUsage($tag, $usage))
                ->count();
            $coverage[$usage] = [
                'label' => $label,
                'approved_count' => $approvedCount,
                'reviewable_count' => $reviewableCount,
                'pending_review_count' => $pendingReviewCount,
                'minimum_required' => $minimum,
            ];

            if ($approvedCount < $minimum) {
                $missingApproved[] = [
                    'usage' => $usage,
                    'label' => $label,
                    'approved_count' => $approvedCount,
                    'reviewable_count' => $reviewableCount,
                    'minimum_required' => $minimum,
                ];
            }
            if ($reviewableCount < $minimum) {
                $missingReviewable[] = [
                    'usage' => $usage,
                    'label' => $label,
                    'reviewable_count' => $reviewableCount,
                    'minimum_required' => $minimum,
                ];
            }
        }

        return [
            'status' => $missingApproved === []
                ? 'ready'
                : ($missingReviewable === [] ? 'needs_review' : 'blocked'),
            'total_pool' => $pool->count(),
            'approved_pool' => $approvedPool->count(),
            'reviewable_pool' => $reviewablePool->count(),
            'coverage' => $coverage,
            'missing_content' => $missingApproved,
            'missing_reviewable_content' => $missingReviewable,
        ];
    }

    private function minimumForProgramUsage(string $usage, array $program): int
    {
        $minimum = $this->minimumForUsage($usage, $program);
        if ($usage !== 'main_workout') {
            return $minimum;
        }

        if ($usage === 'main_workout') {
            return match ($program['level']) {
                'advanced' => 10,
                'intermediate' => 8,
                default => 5,
            };
        }

        return $minimum;
    }

    private function minimumForUsage(string $usage, array $program): int
    {
        if ($usage === 'main_workout') {
            return 5;
        }

        if ($usage === 'stretching') {
            return RoutineLibraryRules::SECTION_MINIMUM_EXERCISES['post_workout_stretching'];
        }

        if ($usage === 'warm_up') {
            $targetMinutes = (int) ($program['target_minutes'] ?? 30);

            return $targetMinutes <= 15 ? 2 : 3;
        }

        if ($usage === 'mobility') {
            $targetMinutes = (int) ($program['target_minutes'] ?? 30);

            return $targetMinutes <= 15 ? 1 : 2;
        }

        if (in_array($usage, ['abs', 'obliques', 'lower_back_strength'], true) && $program['level'] !== 'beginner') {
            return 2;
        }

        return 1;
    }

    private function levelCanServeProgram(string $exerciseLevel, string $programLevel): bool
    {
        $order = [
            'beginner' => 1,
            'intermediate' => 2,
            'advanced' => 3,
        ];

        $exerciseRank = $order[$exerciseLevel] ?? 1;
        $programRank = $order[$programLevel] ?? 1;

        if ($programLevel === 'beginner') {
            return $exerciseRank === 1;
        }

        return $exerciseRank <= $programRank;
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
                    && ($safety['safe_for_warmup'] ?? true);
            }

            return empty($safety['unsafe_as_warmup'])
                && ($safety['safe_for_warmup'] ?? true)
                && in_array($primaryCategory, ['', 'cardiovascular_training', 'warm_up_cardio'], true)
                && in_array($programRole, ['', 'warm_up_cardio'], true)
                && $this->isLowImpactWarmUpCardio($type, $title);
        }

        if ($usage === 'stretching') {
            if ($explicitUsageMatch) {
                return true;
            }

            return in_array($primaryCategory, ['', 'flexibility_stretching', 'post_workout_stretching'], true)
                && in_array($programRole, ['', 'cool_down_stretching', 'post_workout_stretching'], true)
                && $this->isStretchingExercise($type, $title, $patterns);
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
            ),
            'mobility' => $type === 'mobility'
                || $primaryCategory === 'mobility'
                || $trainingAdaptation === 'mobility'
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
