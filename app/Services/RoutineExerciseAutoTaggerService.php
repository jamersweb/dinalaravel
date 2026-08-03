<?php

namespace App\Services;

use App\Models\Exercise;
use App\Models\ExerciseLibraryTag;
use Illuminate\Support\Facades\DB;

class RoutineExerciseAutoTaggerService
{
    private const MAIN_MUSCLE_TAGS = [
        1 => 'hips',
        2 => 'abs',
        3 => 'adductors',
        4 => 'lower back',
        5 => 'middle back',
        6 => 'biceps',
        7 => 'calves',
        8 => 'chest',
        9 => 'chest',
        10 => 'chest',
        11 => 'forearms',
        12 => 'glutes',
        13 => 'hamstrings',
        14 => 'lats',
        15 => 'neck',
        16 => 'obliques',
        17 => 'quads',
        18 => 'shoulders',
        19 => 'shoulders',
        20 => 'shoulders',
        21 => 'traps',
        22 => 'triceps',
    ];

    private const STRETCH_MOBILITY_TAGS = [
        160 => 'adductors',
        161 => 'hips',
        162 => 'feet',
        163 => 'hip flexors',
        165 => 'adductors',
        166 => 'middle back',
        167 => 'upper back',
        168 => 'lower back',
        169 => 'wrists',
        170 => 'ankles',
        171 => 'neck',
        172 => 'shoulders',
        173 => 'hips',
        174 => 'lower back',
        175 => 'glutes',
        176 => 'quads',
        178 => 'lower back',
        179 => 'shoulders',
        180 => 'chest',
        181 => 'obliques',
        182 => 'hamstrings',
        183 => 'serratus',
        184 => 'middle back',
        185 => 'triceps',
        186 => 'calves',
        187 => 'middle back',
        189 => 'neck',
        190 => 'forearms',
    ];

    private const BODYWEIGHT_EQUIPMENT = [28, 41];
    private const DUMBBELL_EQUIPMENT = [33];
    private const GYM_EQUIPMENT = [25, 26, 29, 31, 32, 34, 37, 39, 40, 45, 48, 50, 51, 52, 586, 686];
    private const HOME_ACCESSORY_EQUIPMENT = [23, 24, 27, 30, 35, 36, 38, 42, 43, 44, 46, 47, 49, 177, 191, 688, 689];

    public function tag(array $options = []): array
    {
        $approve = (bool) ($options['approve'] ?? false);
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $replace = (bool) ($options['replace'] ?? false);
        $includeNoAudio = (bool) ($options['include_no_audio'] ?? false);
        $preserveReviewStatus = (bool) ($options['preserve_review_status'] ?? false);

        $query = Exercise::query()
            ->select([
                'id',
                'title',
                'type',
                'tags',
                'language',
                'video_url',
                'video_type',
                'video_duration',
                'content_code',
                'exercise_type',
                'rest_period',
            ])
            ->orderBy('id');

        if (! $includeNoAudio) {
            $query->whereIn('language', RoutineLibraryRules::CONTENT_LANGUAGES);
        }

        if (! $replace) {
            $query->whereDoesntHave('libraryTag');
        }

        $summary = [
            'dry_run' => $dryRun,
            'approve' => $approve,
            'replace' => $replace,
            'include_no_audio' => $includeNoAudio,
            'scanned' => 0,
            'tagged' => 0,
            'skipped' => 0,
            'by_language' => [],
            'by_equipment' => [],
            'by_usage' => [],
            'skipped_reasons' => [],
            'skipped_examples' => [],
        ];

        $query->chunkById(100, function ($exercises) use ($approve, $dryRun, $replace, $preserveReviewStatus, &$summary) {
            foreach ($exercises as $exercise) {
                $summary['scanned']++;
                $classification = $this->classify($exercise, $approve);

                if (! $classification['taggable']) {
                    $summary['skipped']++;
                    $reason = $classification['reason'];
                    $summary['skipped_reasons'][$reason] = ($summary['skipped_reasons'][$reason] ?? 0) + 1;
                    if (count($summary['skipped_examples']) < 25) {
                        $summary['skipped_examples'][] = [
                            'id' => $exercise->id,
                            'title' => $exercise->title,
                            'reason' => $reason,
                        ];
                    }
                    continue;
                }

                $payload = $classification['payload'];
                $summary['tagged']++;
                $summary['by_language'][$payload['language']] = ($summary['by_language'][$payload['language']] ?? 0) + 1;
                $summary['by_equipment'][$payload['equipment_category']] = ($summary['by_equipment'][$payload['equipment_category']] ?? 0) + 1;
                foreach ($payload['usage_flags'] as $usage => $enabled) {
                    if ($enabled) {
                        $summary['by_usage'][$usage] = ($summary['by_usage'][$usage] ?? 0) + 1;
                    }
                }

                if (! $dryRun) {
                    if ($replace && $preserveReviewStatus && ! $approve) {
                        $existing = ExerciseLibraryTag::where('exercise_id', $exercise->id)->first();
                        if ($existing) {
                            $payload['review_status'] = $existing->review_status;
                            $payload['approved_for_generation'] = $existing->approved_for_generation;
                        }
                    }

                    ExerciseLibraryTag::updateOrCreate(
                        ['exercise_id' => $exercise->id],
                        $payload
                    );
                }
            }
        });

        return $summary;
    }

    public function report(): array
    {
        return [
            'tagged_by_language' => ExerciseLibraryTag::query()
                ->select('language', DB::raw('count(*) as total'))
                ->groupBy('language')
                ->orderBy('language')
                ->get(),
            'tagged_by_equipment' => ExerciseLibraryTag::query()
                ->select('equipment_category', DB::raw('count(*) as total'))
                ->groupBy('equipment_category')
                ->orderBy('equipment_category')
                ->get(),
            'tagged_by_type' => ExerciseLibraryTag::query()
                ->select('exercise_type', DB::raw('count(*) as total'))
                ->groupBy('exercise_type')
                ->orderBy('exercise_type')
                ->get(),
            'approved_count' => ExerciseLibraryTag::where('approved_for_generation', true)->count(),
            'unapproved_count' => ExerciseLibraryTag::where('approved_for_generation', false)->count(),
            'untagged_en_ar_count' => Exercise::query()
                ->whereIn('language', RoutineLibraryRules::CONTENT_LANGUAGES)
                ->whereDoesntHave('libraryTag')
                ->count(),
        ];
    }

    private function classify(Exercise $exercise, bool $approve): array
    {
        $language = RoutineLibraryRules::normalizeLanguage($exercise->language);
        $rawLanguage = str_replace('-', '_', strtolower(trim((string) $exercise->language)));
        if (! in_array($rawLanguage, RoutineLibraryRules::CONTENT_LANGUAGES, true) && $language !== 'no_audio') {
            return $this->skip('excluded_language');
        }

        if (empty($exercise->video_url) && $exercise->video_type !== 'image') {
            return $this->skip('missing_video_reference');
        }

        $tagIds = $this->tagIds($exercise->tags);
        $equipment = $this->equipmentCategory($tagIds, $exercise);
        if (! $equipment) {
            return $this->skip('unknown_equipment');
        }

        $muscle = $this->muscleGroup($tagIds, $exercise);
        $exerciseType = $this->exerciseType($exercise, $tagIds);
        $primaryCategory = $this->primaryCategory($exercise, $exerciseType);
        $trainingAdaptation = $this->trainingAdaptation($exercise, $primaryCategory, $exerciseType, $muscle);
        $impact = $this->impactLevel($exercise, $exerciseType);
        $intensity = $this->intensityLevel($exercise, $exerciseType);
        $safetyFlags = $this->safetyFlags($exercise, $primaryCategory, $trainingAdaptation, $impact, $intensity);
        $usage = $this->usageFlags($exercise, $tagIds, $muscle, $exerciseType);
        if (! in_array(true, $usage, true)) {
            return $this->skip('unknown_usage');
        }

        return [
            'taggable' => true,
            'payload' => [
                'exercise_id' => $exercise->id,
                'language' => $language,
                'equipment_category' => $equipment,
                'equipment_tags' => $this->equipmentTags($tagIds, $exercise, $equipment),
                'primary_category' => $primaryCategory,
                'secondary_categories' => $this->secondaryCategories($exercise, $primaryCategory, $exerciseType),
                'training_adaptation' => $trainingAdaptation,
                'program_role' => $this->programRole($usage, $primaryCategory, $trainingAdaptation, $exerciseType, $safetyFlags),
                'muscle_group' => $muscle,
                'secondary_muscle_groups' => $this->secondaryMuscleGroups($tagIds, $muscle, $exercise),
                'body_regions' => $this->bodyRegions($exercise, $muscle),
                'exercise_type' => $exerciseType,
                'movement_patterns' => $this->movementPatterns($exercise, $tagIds, $exerciseType),
                'training_styles' => $this->trainingStyles($exerciseType, $muscle),
                'workout_sections' => $this->workoutSections($usage),
                'impact_level' => $impact,
                'intensity_level' => $intensity,
                'video_variant' => $language === 'no_audio' ? 'no_audio' : 'explained',
                'recommended_duration_seconds' => $this->recommendedDurationSeconds($exercise, $exerciseType),
                'recommended_repetitions' => $this->recommendedRepetitions($exerciseType),
                'recommended_sets' => $this->recommendedSets($exerciseType),
                'recommended_rest_seconds' => $this->recommendedRestSeconds($exercise, $exerciseType),
                'safety_notes' => $this->safetyNotes($exercise, $muscle, $exerciseType),
                'contraindications' => $this->contraindications($exercise, $muscle),
                'difficulty' => $this->difficulty($tagIds),
                'injury_cautions' => $this->injuryCautions($tagIds, $exercise),
                'goal_fit' => $this->goalFit($exerciseType, $muscle),
                'usage_flags' => $usage,
                'safety_flags' => $safetyFlags,
                'approved_for_generation' => $approve,
                'review_status' => $approve ? 'approved' : 'pending_review',
                'notes' => $approve
                    ? 'Auto-tagged from existing CMS exercise tags and approved for generation.'
                    : 'Auto-tagged from existing CMS exercise tags. Review before approving for generation.',
            ],
        ];
    }

    private function tagIds($raw): array
    {
        if (is_array($raw)) {
            return array_values(array_map('intval', $raw));
        }

        $decoded = json_decode((string) $raw, true);
        if (is_array($decoded)) {
            return array_values(array_map('intval', $decoded));
        }

        return [];
    }

    private function equipmentCategory(array $tagIds, Exercise $exercise): ?string
    {
        $title = strtolower((string) $exercise->title);
        $type = strtolower((string) $exercise->type);

        if (array_intersect($tagIds, self::GYM_EQUIPMENT)) {
            return 'gym';
        }
        if (array_intersect($tagIds, self::DUMBBELL_EQUIPMENT)) {
            return 'home_dumbbell';
        }
        if (array_intersect($tagIds, self::HOME_ACCESSORY_EQUIPMENT)) {
            return 'home_dumbbell';
        }
        if (array_intersect($tagIds, self::BODYWEIGHT_EQUIPMENT)) {
            return 'bodyweight';
        }

        if (str_contains($title, 'dumbbell') || preg_match('/\bdb\b/', $title)) {
            return 'home_dumbbell';
        }
        if (
            str_contains($title, 'machine')
            || str_contains($title, 'cable')
            || str_contains($title, 'smith')
            || str_contains($title, 'elliptical')
            || str_contains($title, 'treadmill')
            || str_contains($title, 'bike')
        ) {
            return 'gym';
        }
        if (
            str_contains($type, 'warm')
            || str_contains($type, 'stretch')
            || str_contains($type, 'mobility')
            || str_contains($type, 'cardio')
            || array_intersect($tagIds, array_keys(self::STRETCH_MOBILITY_TAGS))
        ) {
            return 'bodyweight';
        }

        return null;
    }

    private function equipmentTags(array $tagIds, Exercise $exercise, string $equipment): array
    {
        $title = strtolower((string) $exercise->title);
        $tags = [];

        if ($equipment === 'bodyweight') {
            $tags[] = 'bodyweight';
            $tags[] = 'no equipment';
        }
        if ($equipment === 'home_dumbbell' || str_contains($title, 'dumbbell') || preg_match('/\bdb\b/', $title)) {
            $tags[] = 'dumbbells';
        }
        if ($equipment === 'gym') {
            $tags[] = 'gym machines';
        }
        if (str_contains($title, 'cable')) {
            $tags[] = 'cable machines';
        }
        if (str_contains($title, 'barbell') || str_contains($title, 'smith')) {
            $tags[] = 'barbells';
        }
        if (str_contains($title, 'bench')) {
            $tags[] = 'bench';
        }
        if (
            str_contains($title, 'treadmill')
            || str_contains($title, 'bike')
            || str_contains($title, 'elliptical')
            || str_contains($title, 'row')
            || in_array(579, $tagIds, true)
        ) {
            $tags[] = 'cardio machine';
        }
        if (array_intersect($tagIds, self::HOME_ACCESSORY_EQUIPMENT)) {
            $tags[] = 'other available equipment';
        }

        return array_values(array_unique($tags ?: [$equipment]));
    }

    private function muscleGroup(array $tagIds, Exercise $exercise): ?string
    {
        foreach (self::MAIN_MUSCLE_TAGS as $id => $muscle) {
            if (in_array($id, $tagIds, true)) {
                return $muscle;
            }
        }

        foreach (self::STRETCH_MOBILITY_TAGS as $id => $muscle) {
            if (in_array($id, $tagIds, true)) {
                return $muscle;
            }
        }

        $title = strtolower((string) $exercise->title);
        return match (true) {
            str_contains($title, 'oblique'), str_contains($title, 'side plank') => 'obliques',
            str_contains($title, 'abs'), str_contains($title, 'crunch'), str_contains($title, 'plank') => 'abs',
            str_contains($title, 'lower back'), str_contains($title, 'superman') => 'lower back',
            str_contains($title, 'squat'), str_contains($title, 'lunge') => 'quads',
            str_contains($title, 'deadlift'), str_contains($title, 'hamstring') => 'hamstrings',
            default => null,
        };
    }

    private function secondaryMuscleGroups(array $tagIds, ?string $primary, Exercise $exercise): array
    {
        $muscles = [];
        foreach (self::MAIN_MUSCLE_TAGS + self::STRETCH_MOBILITY_TAGS as $id => $muscle) {
            if (in_array($id, $tagIds, true) && $muscle !== $primary) {
                $muscles[] = $muscle;
            }
        }

        $title = strtolower((string) $exercise->title);
        if (str_contains($title, 'squat') || str_contains($title, 'lunge')) {
            array_push($muscles, 'glutes', 'hamstrings', 'core');
        }
        if (str_contains($title, 'deadlift') || str_contains($title, 'hinge')) {
            array_push($muscles, 'glutes', 'hamstrings', 'lower back');
        }
        if (str_contains($title, 'push up') || str_contains($title, 'press')) {
            array_push($muscles, 'shoulders', 'triceps', 'core');
        }
        if (str_contains($title, 'row') || str_contains($title, 'pull')) {
            array_push($muscles, 'biceps', 'upper back', 'core');
        }
        if ($primary) {
            $muscles = array_values(array_diff($muscles, [$primary]));
        }

        return array_values(array_unique(array_filter($muscles)));
    }

    private function exerciseType(Exercise $exercise, array $tagIds): string
    {
        $type = strtolower((string) $exercise->type);
        $title = strtolower((string) $exercise->title);

        if (str_contains($type, 'cardio') || in_array(579, $tagIds, true)) {
            return 'cardio';
        }
        if (str_contains($type, 'hiit') || str_contains($type, 'sprint') || in_array(580, $tagIds, true)) {
            return 'cardio';
        }
        if (str_contains($type, 'warm') || in_array(575, $tagIds, true) || in_array(685, $tagIds, true)) {
            return 'warm_up';
        }
        if (str_contains($type, 'mobility') || in_array(581, $tagIds, true)) {
            return 'mobility';
        }
        if (str_contains($type, 'stretch') || in_array(582, $tagIds, true)) {
            return 'stretching';
        }
        if (str_contains($title, 'oblique') || in_array(16, $tagIds, true)) {
            return 'obliques';
        }
        if (str_contains($title, 'abs') || str_contains($title, 'crunch') || str_contains($title, 'plank') || in_array(2, $tagIds, true)) {
            return 'abs';
        }
        if (in_array(4, $tagIds, true) || str_contains($title, 'lower back') || str_contains($title, 'superman')) {
            return 'lower_back';
        }

        return 'strength';
    }

    private function difficulty(array $tagIds): string
    {
        if (in_array(75, $tagIds, true)) {
            return 'advanced';
        }
        if (in_array(73, $tagIds, true)) {
            return 'beginner';
        }
        if (in_array(74, $tagIds, true)) {
            return 'intermediate';
        }

        return 'beginner';
    }

    private function primaryCategory(Exercise $exercise, string $exerciseType): string
    {
        $title = strtolower((string) $exercise->title);
        $type = strtolower((string) $exercise->type);

        if (str_contains($type, 'hiit') || preg_match('/\b(hiit|tabata|interval)\b/', $title)) {
            return 'hiit_cardio';
        }
        if ($this->isExplosiveTitle($title)) {
            return 'power_explosive_training';
        }
        if ($exerciseType === 'stretching') {
            return preg_match('/\b(post|cool|cooldown|cool down|end|finish)\b/', $title) ? 'post_workout_stretching' : 'flexibility_stretching';
        }
        if ($exerciseType === 'mobility') {
            return 'mobility';
        }
        if ($exerciseType === 'warm_up') {
            return 'dynamic_warm_up';
        }
        if (str_contains($title, 'activation') || str_contains($title, 'activate')) {
            return 'muscle_activation';
        }
        if (preg_match('/\b(circuit)\b/', $title)) {
            return 'circuit_training';
        }
        if ($exerciseType === 'cardio') {
            if (preg_match('/\b(warm|warmup|warm-up)\b/', $title)) {
                return 'warm_up_cardio';
            }
            if (preg_match('/\b(steady|zone 2|zone two|aerobic)\b/', $title)) {
                return 'steady_state_cardio';
            }
            return 'cardiovascular_training';
        }
        if (preg_match('/\b(balance|stability|stabilization)\b/', $title)) {
            return 'balance_stability';
        }
        if (preg_match('/\b(corrective|rehab|prehab|posture|back care)\b/', $title)) {
            return 'corrective_exercise';
        }
        if (preg_match('/\b(breath|breathing|recovery|meditation)\b/', $title)) {
            return 'recovery_breathing';
        }

        return 'resistance_training';
    }

    private function trainingAdaptation(Exercise $exercise, string $primaryCategory, string $exerciseType, ?string $muscle): string
    {
        $title = strtolower((string) $exercise->title);
        $type = strtolower((string) $exercise->type);

        if ($this->isExplosiveTitle($title)) {
            return str_contains($title, 'speed') ? 'speed' : 'explosiveness';
        }
        if (preg_match('/\b(hiit|interval|tabata|sprint)\b/', $title . ' ' . $type)) {
            return 'anaerobic_conditioning';
        }
        if (in_array($primaryCategory, ['cardiovascular_training', 'warm_up_cardio', 'steady_state_cardio', 'cool_down_cardio'], true)) {
            return 'aerobic_conditioning';
        }
        if ($primaryCategory === 'hiit_cardio') {
            return 'anaerobic_conditioning';
        }
        if (in_array($primaryCategory, ['flexibility_stretching', 'post_workout_stretching'], true)) {
            return 'flexibility';
        }
        if ($primaryCategory === 'mobility') {
            return 'mobility';
        }
        if ($primaryCategory === 'dynamic_warm_up') {
            return 'movement_preparation';
        }
        if ($primaryCategory === 'muscle_activation') {
            return 'muscle_activation';
        }
        if ($primaryCategory === 'balance_stability') {
            return str_contains($title, 'balance') ? 'balance' : 'stability';
        }
        if ($primaryCategory === 'corrective_exercise') {
            return 'rehabilitation_corrective';
        }
        if ($primaryCategory === 'recovery_breathing') {
            return 'recovery';
        }
        if ($primaryCategory === 'circuit_training') {
            return 'muscular_endurance';
        }
        if (in_array($muscle, ['abs', 'obliques', 'lower back'], true) || in_array($exerciseType, ['abs', 'obliques', 'lower_back'], true)) {
            return 'muscular_endurance';
        }

        return 'general_fitness';
    }

    private function programRole(array $usage, string $primaryCategory, string $trainingAdaptation, string $exerciseType, array $safetyFlags): string
    {
        if (! empty($usage['stretching']) || in_array($primaryCategory, ['flexibility_stretching', 'post_workout_stretching'], true)) {
            return $primaryCategory === 'post_workout_stretching' ? 'post_workout_stretching' : 'cool_down_stretching';
        }
        if ($primaryCategory === 'warm_up_cardio') {
            return empty($safetyFlags['unsafe_as_warmup']) ? 'warm_up_cardio' : 'hiit_interval';
        }
        if ($primaryCategory === 'hiit_cardio') {
            return 'hiit_interval';
        }
        if ($primaryCategory === 'circuit_training') {
            return 'circuit_exercise';
        }
        if (! empty($usage['warm_up']) || $primaryCategory === 'dynamic_warm_up') {
            return 'dynamic_warm_up';
        }
        if (! empty($usage['lower_back_activation']) || $primaryCategory === 'muscle_activation') {
            return 'activation';
        }
        if (! empty($usage['mobility']) || $primaryCategory === 'mobility') {
            return 'dynamic_warm_up';
        }
        if (! empty($usage['cardio_warm_up']) && empty($safetyFlags['unsafe_as_warmup'])) {
            return 'warm_up_cardio';
        }
        if ($primaryCategory === 'cardiovascular_training') {
            return 'cardio';
        }
        if ($primaryCategory === 'power_explosive_training' || in_array($trainingAdaptation, ['explosiveness', 'anaerobic_conditioning'], true)) {
            return 'hiit_interval';
        }
        if (! empty($usage['abs']) || ! empty($usage['obliques']) || in_array($exerciseType, ['abs', 'obliques'], true)) {
            return 'core';
        }
        if ($primaryCategory === 'corrective_exercise') {
            return 'corrective';
        }
        if ($primaryCategory === 'recovery_breathing') {
            return 'recovery';
        }

        return 'main_workout';
    }

    private function secondaryCategories(Exercise $exercise, string $primaryCategory, string $exerciseType): array
    {
        $title = strtolower((string) $exercise->title);
        $secondary = [];

        if ($primaryCategory === 'power_explosive_training') {
            $secondary[] = 'resistance_training';
            if (preg_match('/\b(hiit|interval|high knee|burpee|sprint|jumping jack|jacks|climber)\b/', $title)) {
                $secondary[] = 'hiit_cardio';
            }
        }
        if ($primaryCategory === 'hiit_cardio') {
            $secondary[] = 'cardiovascular_training';
            if ($this->isExplosiveTitle($title)) {
                $secondary[] = 'power_explosive_training';
            }
        }
        if ($primaryCategory === 'warm_up_cardio') {
            $secondary[] = 'cardiovascular_training';
        }
        if ($primaryCategory === 'post_workout_stretching') {
            $secondary[] = 'flexibility_stretching';
        }
        if ($exerciseType === 'mobility' && $primaryCategory !== 'mobility') {
            $secondary[] = 'mobility';
        }

        return array_values(array_diff(array_unique($secondary), [$primaryCategory]));
    }

    private function bodyRegions(Exercise $exercise, ?string $muscle): array
    {
        $title = strtolower((string) $exercise->title);
        $regions = [];
        $key = trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower((string) $muscle)) ?? '');
        $regions = array_merge($regions, [
            'abs' => ['core', 'abs'],
            'core' => ['core'],
            'obliques' => ['core', 'obliques'],
            'lower back' => ['back', 'lower_back'],
            'upper back' => ['back', 'upper_body'],
            'back' => ['back'],
            'lats' => ['back', 'upper_body'],
            'biceps' => ['arms', 'upper_body'],
            'triceps' => ['arms', 'upper_body'],
            'glutes' => ['lower_body', 'glutes'],
            'hamstrings' => ['lower_body', 'hamstrings'],
            'quads' => ['lower_body', 'quadriceps'],
            'calves' => ['lower_body', 'calves'],
            'chest' => ['upper_body', 'chest'],
            'shoulders' => ['upper_body', 'shoulders'],
        ][$key] ?? []);

        foreach ([
            'full_body' => '/\b(full body|burpee|thruster|mountain climber)\b/',
            'upper_body' => '/\b(push up|press|row|curl|tricep|shoulder|chest|back)\b/',
            'lower_body' => '/\b(squat|lunge|deadlift|rdl|glute|hamstring|quad|calf|step up)\b/',
            'core' => '/\b(core|plank|crunch|dead bug|bird dog)\b/',
            'obliques' => '/\b(oblique|side plank|windshield|rotation)\b/',
            'lower_back' => '/\b(lower back|back extension|bird dog|deadlift|rdl)\b/',
        ] as $region => $pattern) {
            if (preg_match($pattern, $title)) {
                $regions[] = $region;
            }
        }

        return array_values(array_unique(array_intersect($regions, RoutineLibraryRules::BODY_REGIONS)));
    }

    private function movementPatterns(Exercise $exercise, array $tagIds, string $exerciseType): array
    {
        $title = strtolower((string) $exercise->title);
        $patterns = [];

        if (str_contains($title, 'squat')) {
            $patterns[] = 'squat';
        }
        if (str_contains($title, 'deadlift') || str_contains($title, 'hinge') || str_contains($title, 'rdl')) {
            $patterns[] = 'hinge';
        }
        if (str_contains($title, 'lunge') || str_contains($title, 'split squat')) {
            $patterns[] = 'lunge';
        }
        if (str_contains($title, 'push') || str_contains($title, 'press')) {
            $patterns[] = 'push';
        }
        if (str_contains($title, 'pull') || str_contains($title, 'row') || str_contains($title, 'pulldown')) {
            $patterns[] = 'pull';
        }
        if (str_contains($title, 'twist') || str_contains($title, 'rotation')) {
            $patterns[] = 'rotation';
        }
        if (str_contains($title, 'pallof') || str_contains($title, 'anti rotation')) {
            $patterns[] = 'anti_rotation';
        }
        if (str_contains($title, 'crunch') || str_contains($title, 'sit up')) {
            $patterns[] = 'flexion';
        }
        if (str_contains($title, 'extension') || str_contains($title, 'superman')) {
            $patterns[] = 'extension';
        }
        if (str_contains($title, 'abduction') || str_contains($title, 'lateral')) {
            $patterns[] = 'abduction';
        }
        if (str_contains($title, 'adduction') || str_contains($title, 'adductor')) {
            $patterns[] = 'adduction';
        }
        if (str_contains($title, 'plank') || str_contains($title, 'bird dog') || str_contains($title, 'dead bug')) {
            $patterns[] = 'stabilization';
        }
        if (str_contains($title, 'walk') || str_contains($title, 'march') || str_contains($title, 'step')) {
            $patterns[] = 'locomotion';
        }
        if (str_contains($title, 'jump') || str_contains($title, 'hop')) {
            $patterns[] = 'jumping';
        }
        if ($exerciseType === 'mobility') {
            $patterns[] = 'mobility';
        }
        if ($exerciseType === 'stretching') {
            $patterns[] = 'stretching';
        }

        return array_values(array_unique($patterns ?: [$exerciseType]));
    }

    private function trainingStyles(string $exerciseType, ?string $muscle): array
    {
        $styles = match ($exerciseType) {
            'cardio' => ['low-impact cardio', 'conditioning'],
            'warm_up' => ['mobility', 'recovery'],
            'mobility' => ['mobility', 'functional training'],
            'stretching' => ['flexibility', 'recovery'],
            'abs', 'obliques', 'lower_back' => ['core stability', 'resistance training'],
            default => ['resistance training'],
        };

        if (in_array($muscle, ['abs', 'obliques', 'lower back'], true)) {
            $styles[] = 'core stability';
        }

        return array_values(array_unique($styles));
    }

    private function workoutSections(array $usage): array
    {
        $labels = [
            'cardio_warm_up' => 'cardio warm-up',
            'warm_up' => 'dynamic warm-up',
            'mobility' => 'mobility',
            'muscle_activation' => 'muscle activation',
            'lower_back_activation' => 'lower-back activation',
            'main_workout' => 'main strength workout',
            'abs' => 'core',
            'obliques' => 'core and obliques',
            'lower_back_strength' => 'lower-back strengthening',
            'stretching' => 'cool-down and stretching',
        ];

        $sections = [];
        foreach ($usage as $flag => $enabled) {
            if ($enabled && isset($labels[$flag])) {
                $sections[] = $labels[$flag];
            }
        }

        return array_values(array_unique($sections));
    }

    private function impactLevel(Exercise $exercise, string $exerciseType): string
    {
        $title = strtolower((string) $exercise->title);

        if ($this->isExplosiveTitle($title)) {
            return 'high';
        }
        if ($exerciseType === 'cardio' || str_contains($title, 'lunge')) {
            return 'moderate';
        }

        return 'low';
    }

    private function intensityLevel(Exercise $exercise, string $exerciseType): string
    {
        $title = strtolower((string) $exercise->title);
        $type = strtolower((string) $exercise->type);

        if (str_contains($title, 'hiit') || str_contains($type, 'hiit') || str_contains($title, 'sprint')) {
            return 'high';
        }
        if (in_array($exerciseType, ['strength', 'cardio', 'abs', 'obliques', 'lower_back'], true)) {
            return 'moderate';
        }

        return 'low';
    }

    private function safetyFlags(Exercise $exercise, string $primaryCategory, string $trainingAdaptation, string $impact, string $intensity): array
    {
        $title = strtolower((string) $exercise->title);
        $type = strtolower((string) $exercise->type);
        $explosive = $this->isExplosiveTitle($title) || $primaryCategory === 'power_explosive_training';
        $highImpact = $impact === 'high' || $explosive;
        $unsafeAsWarmup = $highImpact
            || $intensity === 'high'
            || in_array($trainingAdaptation, ['anaerobic_conditioning', 'explosiveness', 'power', 'speed'], true)
            || preg_match('/\b(hiit|interval|tabata|finisher|sprint|burpee|explosive)\b/', $title . ' ' . $type) === 1;

        return [
            'safe_for_warmup' => ! $unsafeAsWarmup && in_array($primaryCategory, ['cardiovascular_training', 'warm_up_cardio', 'dynamic_warm_up', 'mobility', 'muscle_activation'], true),
            'safe_for_cooldown' => in_array($primaryCategory, ['flexibility_stretching', 'post_workout_stretching', 'cool_down_cardio', 'recovery_breathing'], true),
            'unsafe_as_warmup' => $unsafeAsWarmup,
            'high_impact' => $highImpact,
            'explosive' => $explosive,
        ];
    }

    private function isExplosiveTitle(string $title): bool
    {
        return preg_match('/\b(jump|jumps|jumping|hop|hops|burpee|plyo|plyometric|explosive|power|sprint|high knee|high knees|skater|tuck|climber|jacks)\b/', $title) === 1;
    }

    private function recommendedDurationSeconds(Exercise $exercise, string $exerciseType): ?int
    {
        if (in_array($exerciseType, ['cardio', 'warm_up', 'mobility', 'stretching'], true)) {
            $duration = (int) ($exercise->video_duration ?: 0);

            return $duration > 0 ? min($duration, 600) : 45;
        }

        return null;
    }

    private function recommendedRepetitions(string $exerciseType): ?string
    {
        return match ($exerciseType) {
            'cardio', 'warm_up', 'mobility', 'stretching' => null,
            'abs', 'obliques', 'lower_back' => '8-15',
            default => '8-12',
        };
    }

    private function recommendedSets(string $exerciseType): ?string
    {
        return match ($exerciseType) {
            'cardio', 'warm_up', 'mobility', 'stretching' => null,
            'abs', 'obliques', 'lower_back' => '2-3',
            default => '2-4',
        };
    }

    private function recommendedRestSeconds(Exercise $exercise, string $exerciseType): int
    {
        $rest = (int) ($exercise->rest_period ?: 0);
        if ($rest > 0) {
            return min($rest, 300);
        }

        return match ($exerciseType) {
            'cardio', 'warm_up', 'mobility', 'stretching' => 0,
            'abs', 'obliques', 'lower_back' => 30,
            default => 60,
        };
    }

    private function safetyNotes(Exercise $exercise, ?string $muscle, string $exerciseType): array
    {
        $notes = [];
        if ($muscle === 'lower back' || $exerciseType === 'lower_back') {
            $notes[] = 'Keep the spine neutral and stop if sharp back pain appears.';
        }
        if (in_array($muscle, ['quads', 'hamstrings', 'glutes'], true)) {
            $notes[] = 'Keep knees tracking with toes and use a pain-free range of motion.';
        }
        if (in_array($muscle, ['shoulders', 'chest', 'triceps'], true)) {
            $notes[] = 'Avoid forcing shoulder range and keep control through the full movement.';
        }
        if ($this->impactLevel($exercise, $exerciseType) === 'high') {
            $notes[] = 'High-impact movement; avoid when joint pain or recovery limitations are present.';
        }

        return $notes;
    }

    private function contraindications(Exercise $exercise, ?string $muscle): array
    {
        $title = strtolower((string) $exercise->title);
        $contraindications = [];

        if ($muscle === 'lower back' || str_contains($title, 'deadlift') || str_contains($title, 'superman')) {
            $contraindications[] = 'acute lower-back pain';
        }
        if (str_contains($title, 'jump') || str_contains($title, 'hop')) {
            $contraindications[] = 'uncontrolled knee, ankle, or hip pain';
        }
        if (str_contains($title, 'overhead')) {
            $contraindications[] = 'uncontrolled shoulder pain';
        }

        return array_values(array_unique($contraindications));
    }

    private function usageFlags(Exercise $exercise, array $tagIds, ?string $muscle, string $exerciseType): array
    {
        $title = strtolower((string) $exercise->title);
        $type = strtolower((string) $exercise->type);
        $unsafeAsWarmup = $this->isExplosiveTitle($title)
            || preg_match('/\b(hiit|interval|tabata|sprint|burpee|explosive)\b/', $title . ' ' . $type) === 1;

        return [
            'cardio_warm_up' => $exerciseType === 'cardio' && ! $unsafeAsWarmup,
            'warm_up' => $exerciseType === 'warm_up' && ! $unsafeAsWarmup,
            'mobility' => $exerciseType === 'mobility' || (bool) array_intersect($tagIds, range(162, 174)),
            'muscle_activation' => ! $unsafeAsWarmup
                && (str_contains($title, 'activation') || str_contains($title, 'activate') || in_array($muscle, ['glutes', 'shoulders', 'upper back', 'abs'], true)),
            'lower_back_activation' => ! $unsafeAsWarmup && $muscle === 'lower back'
                && ($exerciseType === 'mobility' || str_contains($title, 'activation') || str_contains($title, 'warm')),
            'main_workout' => in_array($exerciseType, ['strength', 'abs', 'obliques', 'lower_back'], true) || $unsafeAsWarmup,
            'abs' => $muscle === 'abs' || $exerciseType === 'abs',
            'obliques' => $muscle === 'obliques' || $exerciseType === 'obliques',
            'lower_back_strength' => $muscle === 'lower back' && in_array($exerciseType, ['strength', 'lower_back'], true),
            'stretching' => $exerciseType === 'stretching' || str_contains($type, 'stretch') || in_array(582, $tagIds, true),
        ];
    }

    private function injuryCautions(array $tagIds, Exercise $exercise): array
    {
        $cautions = [];
        $title = strtolower((string) $exercise->title);
        $muscle = $this->muscleGroup($tagIds, $exercise);

        if ($muscle === 'lower back' || str_contains($title, 'deadlift') || str_contains($title, 'superman')) {
            $cautions[] = 'lower back pain';
        }
        if (in_array($muscle, ['quads', 'hamstrings', 'glutes'], true) || str_contains($title, 'squat') || str_contains($title, 'lunge')) {
            $cautions[] = 'knee pain';
        }
        if (in_array($muscle, ['shoulders', 'chest', 'triceps'], true) || str_contains($title, 'overhead') || str_contains($title, 'push up')) {
            $cautions[] = 'shoulder pain';
        }
        if (str_contains($title, 'jump') || str_contains($title, 'explosive') || str_contains($title, 'hiit')) {
            $cautions[] = 'high impact';
        }

        return array_values(array_unique($cautions));
    }

    private function goalFit(string $exerciseType, ?string $muscle): array
    {
        $goals = ['general_fitness'];

        if (in_array($exerciseType, ['strength', 'lower_back'], true)) {
            $goals[] = 'muscle_gain';
        }
        if ($exerciseType === 'cardio') {
            $goals[] = 'fat_loss';
            $goals[] = 'conditioning';
        }
        if (in_array($exerciseType, ['mobility', 'stretching'], true)) {
            $goals[] = 'mobility';
            $goals[] = 'recovery';
        }
        if (in_array($muscle, ['abs', 'obliques', 'lower back'], true)) {
            $goals[] = 'core_strength';
        }

        return array_values(array_unique($goals));
    }

    private function skip(string $reason): array
    {
        return [
            'taggable' => false,
            'reason' => $reason,
        ];
    }
}
