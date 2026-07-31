<?php

namespace App\Services;

use App\Jobs\ProcessAiExerciseTagProposalJob;
use App\Models\AiExerciseTagProposal;
use App\Models\Exercise;
use App\Models\ExerciseLibraryTag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OllamaExerciseTaggerService
{
    public function generateProposals(array $filters = []): array
    {
        $limit = max(1, min(50, (int) ($filters['limit'] ?? 10)));
        $model = (string) ($filters['model'] ?? config('services.ollama.model', 'qwen2.5vl:7b'));
        $query = Exercise::query()
            ->with('libraryTag')
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
                'image',
                'custom_thumbnail',
            ])
            ->whereIn('language', RoutineLibraryRules::CONTENT_LANGUAGES)
            ->when($filters['language'] ?? null, fn ($q, $language) => $q->where('language', $language))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', '%' . $search . '%')
                        ->orWhere('content_code', 'like', '%' . $search . '%');
                });
            })
            ->when(($filters['scope'] ?? 'all') === 'untagged', fn ($q) => $q->whereDoesntHave('libraryTag'))
            ->when(($filters['scope'] ?? 'all') === 'tagged', fn ($q) => $q->whereHas('libraryTag'))
            ->when($filters['equipment_category'] ?? null, function ($q, $equipment) {
                $q->whereHas('libraryTag', fn ($tagQuery) => $tagQuery->where('equipment_category', $equipment));
            })
            ->orderBy('id')
            ->limit($limit);

        $summary = [
            'requested' => $limit,
            'model' => $model,
            'created' => 0,
            'queued' => 0,
            'failed' => 0,
            'proposal_ids' => [],
        ];

        foreach ($query->get() as $exercise) {
            $metadata = $this->sourceMetadata($exercise);
            $currentTagPayload = $exercise->libraryTag ? $this->tagPayload($exercise->libraryTag) : null;

            AiExerciseTagProposal::where('exercise_id', $exercise->id)
                ->whereIn('status', ['queued', 'processing', 'proposed', 'rejected', 'failed'])
                ->delete();

            $proposal = AiExerciseTagProposal::create([
                'exercise_id' => $exercise->id,
                'provider' => 'ollama',
                'model' => $model,
                'status' => 'queued',
                'source_metadata' => $metadata,
                'current_tag_payload' => $currentTagPayload,
                'generated_by' => Auth::id(),
                'generated_at' => now(),
            ]);

            ProcessAiExerciseTagProposalJob::dispatch($proposal->id)->onQueue('ai-tags');

            $summary['created']++;
            $summary['queued']++;
            $summary['proposal_ids'][] = $proposal->id;
        }

        return $summary;
    }

    public function processQueuedProposal(AiExerciseTagProposal $proposal): void
    {
        if (! in_array($proposal->status, ['queued', 'processing'], true)) {
            return;
        }

        $proposal->fill([
            'status' => 'processing',
            'error_message' => null,
        ])->save();

        try {
            $metadata = is_array($proposal->source_metadata) ? $proposal->source_metadata : [];
            $currentTagPayload = is_array($proposal->current_tag_payload) ? $proposal->current_tag_payload : null;
            $result = $this->tagWithOllama($metadata, $currentTagPayload, $proposal->model);

            $proposal->fill([
                'status' => 'proposed',
                'proposed_payload' => $result['payload'],
                'confidence' => $result['confidence'],
                'reasoning' => $result['reasoning'],
                'raw_response' => $result['raw_response'],
                'error_message' => null,
            ])->save();
        } catch (\Throwable $e) {
            $proposal->fill([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ])->save();
        }
    }

    public function applyProposal(AiExerciseTagProposal $proposal, bool $approve): ExerciseLibraryTag
    {
        if (! is_array($proposal->proposed_payload) || $proposal->status !== 'proposed') {
            throw new RuntimeException('Only proposed AI tags can be applied.');
        }

        $payload = $this->normalizePayload(
            $proposal->proposed_payload,
            is_array($proposal->source_metadata) ? $proposal->source_metadata : null,
            is_array($proposal->current_tag_payload) ? $proposal->current_tag_payload : null
        );
        $payload['exercise_id'] = $proposal->exercise_id;
        $payload['approved_for_generation'] = $approve;
        $payload['review_status'] = $approve ? 'approved' : 'pending_review';
        $payload['notes'] = trim(($payload['notes'] ?? '') . "\nAI-tagged by {$proposal->model}; reviewed in AI Video Tags.");

        $tag = ExerciseLibraryTag::updateOrCreate(
            ['exercise_id' => $proposal->exercise_id],
            $payload
        );

        $proposal->fill([
            'status' => 'applied',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ])->save();

        return $tag;
    }

    public function rejectProposal(AiExerciseTagProposal $proposal): void
    {
        if (! in_array($proposal->status, ['queued', 'processing', 'proposed', 'failed'], true)) {
            throw new RuntimeException('Only open AI tag proposals can be removed.');
        }

        $proposal->delete();
    }

    public function clearRejectedProposals(): int
    {
        return AiExerciseTagProposal::whereIn('status', ['rejected', 'failed'])->delete();
    }

    private function tagWithOllama(array $metadata, ?array $currentTagPayload, string $model): array
    {
        $baseUrl = rtrim((string) config('services.ollama.base_url', 'http://127.0.0.1:11434'), '/');
        $timeout = (int) config('services.ollama.timeout', 120);
        $request = [
            'model' => $model,
            'stream' => false,
            'format' => 'json',
            'prompt' => $this->prompt($metadata, $currentTagPayload),
            'options' => [
                'temperature' => 0.1,
            ],
        ];

        $images = $this->ollamaImages($metadata);
        if ($images !== []) {
            $request['images'] = $images;
        }

        $response = Http::timeout($timeout)->post($baseUrl . '/api/generate', $request);

        if (! $response->successful()) {
            if ($response->status() === 404) {
                throw new RuntimeException("Ollama model '{$model}' was not found at {$baseUrl}. Pull it with: ollama pull {$model}");
            }

            throw new RuntimeException('Ollama request failed: HTTP ' . $response->status() . ' ' . $this->shortResponseBody($response->body()));
        }

        $raw = (string) ($response->json('response') ?? '');
        $decoded = $this->decodeJsonResponse($raw);
        $payload = $this->normalizePayload($decoded['tag'] ?? $decoded, $metadata, $currentTagPayload);

        return [
            'payload' => $payload,
            'confidence' => isset($decoded['confidence']) ? max(0, min(1, (float) $decoded['confidence'])) : null,
            'reasoning' => isset($decoded['reasoning']) ? (string) $decoded['reasoning'] : null,
            'raw_response' => $raw,
        ];
    }

    private function shortResponseBody(string $body): string
    {
        $body = trim(preg_replace('/\s+/', ' ', $body) ?? '');
        if ($body === '') {
            return '';
        }

        return '- ' . mb_substr($body, 0, 300);
    }

    private function prompt(array $metadata, ?array $currentTagPayload): string
    {
        $schema = [
            'tag' => [
                'language' => RoutineLibraryRules::LANGUAGES,
                'equipment_category' => RoutineLibraryRules::EQUIPMENT_CATEGORIES,
                'equipment_tags' => ['bodyweight', 'dumbbells', 'machine', 'cable', 'barbell', 'cardio_machine', 'bench', 'mat'],
                'muscle_group' => 'string',
                'secondary_muscle_groups' => ['string'],
                'exercise_type' => ['strength', 'main', 'bodyweight', 'dumbbell', 'gym', 'cardio', 'cardio_warm_up', 'warm_up', 'mobility', 'stretching', 'lower_back', 'abs', 'obliques'],
                'movement_patterns' => RoutineLibraryRules::MOVEMENT_PATTERNS,
                'training_styles' => ['strength', 'hypertrophy', 'conditioning', 'mobility', 'core', 'stretching', 'warm_up'],
                'workout_sections' => array_keys(RoutineLibraryRules::WORKOUT_SECTION_LABELS),
                'impact_level' => RoutineLibraryRules::IMPACT_LEVELS,
                'intensity_level' => RoutineLibraryRules::INTENSITY_LEVELS,
                'video_variant' => RoutineLibraryRules::VIDEO_VARIANTS,
                'recommended_duration_seconds' => 'integer_or_null',
                'recommended_repetitions' => 'string_or_null',
                'recommended_sets' => 'string_or_null',
                'recommended_rest_seconds' => 'integer_or_null',
                'safety_notes' => ['string'],
                'contraindications' => ['string'],
                'difficulty' => RoutineLibraryRules::LEVELS,
                'injury_cautions' => ['string'],
                'goal_fit' => ['string'],
                'usage_flags' => array_fill_keys(array_keys(RoutineLibraryRules::REQUIRED_AUDIT_USAGE), 'boolean'),
                'notes' => 'string',
            ],
            'confidence' => '0_to_1',
            'reasoning' => 'short explanation',
        ];

        return 'You are a fitness-library tagging expert. If an image is attached, inspect it together with the metadata. '
            . 'If no image is attached, classify from the exact metadata only. Never apply the same default tag to every exercise. '
            . 'Use title, current tag, equipment words, muscle words, safety words, and visible equipment/body position from the image when available. '
            . 'Return one JSON object only. Use ONLY the allowed values from the schema. '
            . 'If a current deterministic tag exists and the title does not clearly contradict it, keep its equipment_category and language. '
            . 'Deadlift, squat, row, press, curl, bridge, lunge with dumbbell/home dumbbell evidence is not bodyweight. '
            . 'Warm-up titles must use exercise_type warm_up or mobility, not main/strength. '
            . 'Stretch titles must use exercise_type stretching and usage_flags.stretching=true. '
            . 'Important safety rules: HIIT, jumps, high knees, burpees, sprinting, explosive drills are NOT warm-up cardio. '
            . 'Warm-up cardio must be low-impact walking, marching, bike, elliptical, rower, or stepper. '
            . 'Examples: '
            . 'Frogger rockbacks stretch => stretching | bodyweight | beginner | usage stretching. '
            . 'Deadlift warm up => warm_up | bodyweight unless current tag says home_dumbbell | beginner | usage warm_up or lower_back_activation. '
            . 'Sumo Deadlift with dumbbells => dumbbell | home_dumbbell | intermediate | usage main_workout. '
            . 'SL Wall Deadlift with home dumbbell current tag => dumbbell | home_dumbbell | beginner/intermediate | usage main_workout or lower_back_strength. '
            . "Schema:\n" . json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            . "\nExercise metadata:\n" . json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            . "\nCurrent deterministic tag if any:\n" . json_encode($currentTagPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function decodeJsonResponse(string $raw): array
    {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $raw, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new RuntimeException('Ollama response was not valid JSON.');
    }

    private function normalizePayload(array $payload, ?array $metadata = null, ?array $currentTagPayload = null): array
    {
        $language = RoutineLibraryRules::normalizeLanguage($payload['language'] ?? 'en');
        if ($currentTagPayload && ! empty($currentTagPayload['language'])) {
            $language = RoutineLibraryRules::normalizeLanguage($currentTagPayload['language']);
        } elseif ($metadata && ! empty($metadata['language'])) {
            $language = RoutineLibraryRules::normalizeLanguage($metadata['language']);
        }

        $equipment = $this->inferEquipmentCategory($payload, $metadata, $currentTagPayload);
        $difficulty = RoutineLibraryRules::normalizeLevel($payload['difficulty'] ?? 'beginner');
        $impact = $this->allowedValue($payload['impact_level'] ?? 'low', RoutineLibraryRules::IMPACT_LEVELS, 'low');
        $intensity = $this->allowedValue($payload['intensity_level'] ?? 'moderate', RoutineLibraryRules::INTENSITY_LEVELS, 'moderate');
        $videoVariant = $this->allowedValue($payload['video_variant'] ?? 'explained', RoutineLibraryRules::VIDEO_VARIANTS, 'explained');
        $exerciseType = $this->inferExerciseType($payload, $metadata, $currentTagPayload, $equipment);
        $muscleGroup = $this->inferMuscleGroup($payload, $metadata, $currentTagPayload);
        $usageFlags = $this->usageFlags((array) ($payload['usage_flags'] ?? []), $exerciseType, $muscleGroup, $metadata);
        $workoutSections = array_values(array_intersect($this->stringArray($payload['workout_sections'] ?? []), array_keys(RoutineLibraryRules::WORKOUT_SECTION_LABELS)));
        if ($workoutSections === []) {
            $workoutSections = $this->sectionsFromUsageFlags($usageFlags);
        }

        return [
            'language' => $language,
            'equipment_category' => $equipment,
            'equipment_tags' => $this->stringArray($payload['equipment_tags'] ?? []),
            'muscle_group' => $muscleGroup,
            'secondary_muscle_groups' => $this->stringArray($payload['secondary_muscle_groups'] ?? []),
            'exercise_type' => $exerciseType,
            'movement_patterns' => array_values(array_intersect($this->stringArray($payload['movement_patterns'] ?? []), RoutineLibraryRules::MOVEMENT_PATTERNS)),
            'training_styles' => $this->stringArray($payload['training_styles'] ?? []),
            'workout_sections' => $workoutSections,
            'impact_level' => $impact,
            'intensity_level' => $intensity,
            'video_variant' => $videoVariant,
            'recommended_duration_seconds' => $this->nullableInteger($payload['recommended_duration_seconds'] ?? null, 0, 3600),
            'recommended_repetitions' => $this->nullableString($payload['recommended_repetitions'] ?? null, 64),
            'recommended_sets' => $this->nullableString($payload['recommended_sets'] ?? null, 64),
            'recommended_rest_seconds' => $this->nullableInteger($payload['recommended_rest_seconds'] ?? null, 0, 600),
            'safety_notes' => $this->stringArray($payload['safety_notes'] ?? []),
            'contraindications' => $this->stringArray($payload['contraindications'] ?? []),
            'difficulty' => $difficulty,
            'injury_cautions' => $this->stringArray($payload['injury_cautions'] ?? []),
            'goal_fit' => $this->stringArray($payload['goal_fit'] ?? []),
            'usage_flags' => $usageFlags,
            'approved_for_generation' => false,
            'review_status' => 'pending_review',
            'notes' => $this->nullableString($payload['notes'] ?? 'AI tag proposal from Ollama.', 1000),
        ];
    }

    private function sourceMetadata(Exercise $exercise): array
    {
        return [
            'id' => $exercise->id,
            'title' => $exercise->title,
            'content_code' => $exercise->content_code,
            'type' => $exercise->type,
            'exercise_type' => $exercise->exercise_type,
            'language' => $exercise->language,
            'tags' => $exercise->tags,
            'video_type' => $exercise->video_type,
            'video_url' => $exercise->getRawOriginal('video_url'),
            'image_url' => $exercise->image,
            'raw_image' => $exercise->getRawOriginal('image'),
            'custom_thumbnail' => $exercise->getRawOriginal('custom_thumbnail'),
            'video_duration' => $exercise->video_duration,
            'rest_period' => $exercise->rest_period,
        ];
    }

    private function ollamaImages(array $metadata): array
    {
        $imageUrl = $metadata['image_url'] ?? null;
        if (! is_string($imageUrl) || trim($imageUrl) === '') {
            return [];
        }

        try {
            $response = Http::timeout(20)->get($imageUrl);
            if (! $response->successful()) {
                return [];
            }

            $body = $response->body();
            $contentType = strtolower((string) $response->header('Content-Type'));
            if (strlen($body) > 5 * 1024 * 1024 || ($contentType !== '' && ! str_contains($contentType, 'image'))) {
                return [];
            }

            return [base64_encode($body)];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function tagPayload(ExerciseLibraryTag $tag): array
    {
        return collect($tag->toArray())
            ->only((new ExerciseLibraryTag())->getFillable())
            ->except(['exercise_id'])
            ->all();
    }

    private function usageFlags(array $flags, string $exerciseType, string $muscleGroup, ?array $metadata): array
    {
        $normalized = [];
        foreach (array_keys(RoutineLibraryRules::REQUIRED_AUDIT_USAGE) as $usage) {
            $normalized[$usage] = filter_var($flags[$usage] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        $title = strtolower((string) ($metadata['title'] ?? ''));
        $forced = array_fill_keys(array_keys(RoutineLibraryRules::REQUIRED_AUDIT_USAGE), false);

        if ($exerciseType === 'stretching') {
            $forced['stretching'] = true;
            return $forced;
        }
        if ($exerciseType === 'warm_up') {
            $forced[($muscleGroup === 'lower back' || str_contains($title, 'activation')) ? 'lower_back_activation' : 'warm_up'] = true;
            return $forced;
        }
        if ($exerciseType === 'mobility') {
            $forced['mobility'] = true;
            return $forced;
        }
        if ($exerciseType === 'cardio' || $exerciseType === 'cardio_warm_up') {
            $forced['cardio_warm_up'] = true;
            return $forced;
        }
        if ($muscleGroup === 'abs' || $exerciseType === 'abs') {
            $forced['abs'] = true;
            return $forced;
        }
        if ($muscleGroup === 'obliques' || $exerciseType === 'obliques') {
            $forced['obliques'] = true;
            return $forced;
        }
        if ($muscleGroup === 'lower back' || $exerciseType === 'lower_back') {
            $forced[str_contains($title, 'warm') || str_contains($title, 'activation') ? 'lower_back_activation' : 'lower_back_strength'] = true;
            return $forced;
        }

        if (in_array(true, $normalized, true)) {
            return $normalized;
        }

        if ($exerciseType === 'cardio' || $exerciseType === 'cardio_warm_up') {
            $normalized['cardio_warm_up'] = true;
        } elseif ($exerciseType === 'warm_up') {
            $normalized['warm_up'] = true;
        } elseif ($exerciseType === 'mobility') {
            $normalized['mobility'] = true;
        } elseif ($exerciseType === 'stretching') {
            $normalized['stretching'] = true;
        } elseif ($muscleGroup === 'abs' || $exerciseType === 'abs') {
            $normalized['abs'] = true;
        } elseif ($muscleGroup === 'obliques' || $exerciseType === 'obliques') {
            $normalized['obliques'] = true;
        } elseif ($muscleGroup === 'lower back' || $exerciseType === 'lower_back') {
            $normalized[str_contains($title, 'warm') || str_contains($title, 'activation') ? 'lower_back_activation' : 'lower_back_strength'] = true;
        } else {
            $normalized['main_workout'] = true;
        }

        return $normalized;
    }

    private function inferEquipmentCategory(array $payload, ?array $metadata, ?array $currentTagPayload): string
    {
        $title = strtolower((string) ($metadata['title'] ?? ''));
        $raw = strtolower(str_replace([' ', '-'], '_', (string) ($payload['equipment_category'] ?? '')));

        if (preg_match('/\b(db|dumbbell|dumbbells)\b/', $title)) {
            return 'home_dumbbell';
        }
        if (preg_match('/\b(machine|cable|smith|leg press|lat pulldown|seated row|elliptical|treadmill)\b/', $title)) {
            return 'gym';
        }
        if (preg_match('/\b(barbell|bench press|rack)\b/', $title)) {
            return 'full_gym';
        }
        if ($currentTagPayload && ! empty($currentTagPayload['equipment_category'])) {
            $current = RoutineLibraryRules::normalizeEquipment($currentTagPayload['equipment_category']);
            if ($current !== 'bodyweight' || $raw === '' || $raw === 'bodyweight') {
                return $current;
            }
        }
        if ($raw === 'dumbbell' || $raw === 'dumbbells') {
            return 'home_dumbbell';
        }

        $equipment = RoutineLibraryRules::normalizeEquipment($raw ?: 'bodyweight');
        if ($equipment === 'bodyweight' && preg_match('/\b(deadlift|rdl|stiff|sumo|split deadlift|wall deadlift)\b/', $title)) {
            return 'home_dumbbell';
        }

        return $equipment;
    }

    private function inferExerciseType(array $payload, ?array $metadata, ?array $currentTagPayload, string $equipment): string
    {
        $title = strtolower((string) ($metadata['title'] ?? ''));
        $raw = strtolower(str_replace([' ', '-'], '_', (string) ($payload['exercise_type'] ?? '')));

        if (str_contains($title, 'stretch')) {
            return 'stretching';
        }
        if (preg_match('/\b(warm up|warm-up|activation|prep|rockback|rockbacks)\b/', $title)) {
            return str_contains($title, 'mobility') ? 'mobility' : 'warm_up';
        }
        if (preg_match('/\b(elliptical|treadmill|walk|walking|bike|cycling|stepper|rower|cardio)\b/', $title)
            && ! preg_match('/\b(hiit|jump|sprint|burpee|high knee)\b/', $title)) {
            return 'cardio';
        }
        if (in_array($raw, ['strength', 'main', 'resistance', 'bodyweight', 'dumbbell', 'gym', 'cardio', 'warm_up', 'mobility', 'stretching', 'lower_back', 'abs', 'obliques'], true)) {
            return $raw;
        }
        if ($currentTagPayload && ! empty($currentTagPayload['exercise_type'])) {
            $current = strtolower(str_replace([' ', '-'], '_', (string) $currentTagPayload['exercise_type']));
            if (in_array($current, ['strength', 'main', 'resistance', 'bodyweight', 'dumbbell', 'gym', 'cardio', 'warm_up', 'mobility', 'stretching', 'lower_back', 'abs', 'obliques'], true)) {
                return $current;
            }
        }
        if ($equipment === 'home_dumbbell') {
            return 'dumbbell';
        }
        if (in_array($equipment, ['gym', 'full_gym'], true)) {
            return 'gym';
        }

        return 'bodyweight';
    }

    private function inferMuscleGroup(array $payload, ?array $metadata, ?array $currentTagPayload): string
    {
        $title = strtolower((string) ($metadata['title'] ?? ''));
        $muscle = strtolower(trim((string) ($payload['muscle_group'] ?? '')));
        if ($muscle !== '' && $muscle !== '-') {
            return $muscle;
        }
        if ($currentTagPayload && ! empty($currentTagPayload['muscle_group'])) {
            return strtolower((string) $currentTagPayload['muscle_group']);
        }
        if (preg_match('/\b(deadlift|rdl|lower back|back extension)\b/', $title)) {
            return 'lower back';
        }
        if (preg_match('/\b(abs|core|crunch|plank)\b/', $title)) {
            return 'abs';
        }
        if (preg_match('/\b(oblique|side plank|windshield)\b/', $title)) {
            return 'obliques';
        }
        if (preg_match('/\b(glute|bridge|hip thrust)\b/', $title)) {
            return 'glutes';
        }
        if (preg_match('/\b(hamstring|stiff)\b/', $title)) {
            return 'hamstrings';
        }
        if (preg_match('/\b(quad|squat|lunge)\b/', $title)) {
            return 'quads';
        }

        return '';
    }

    private function sectionsFromUsageFlags(array $usageFlags): array
    {
        $map = [
            'cardio_warm_up' => 'warm_up_cardio',
            'warm_up' => 'mobility_dynamic_warm_up',
            'mobility' => 'mobility_dynamic_warm_up',
            'lower_back_activation' => 'core_lower_back_preparation',
            'main_workout' => 'main_workout',
            'abs' => 'core_obliques',
            'obliques' => 'core_obliques',
            'lower_back_strength' => 'lower_back_strengthening',
            'stretching' => 'cool_down_stretching',
        ];

        $sections = [];
        foreach ($usageFlags as $usage => $enabled) {
            if ($enabled && isset($map[$usage])) {
                $sections[] = $map[$usage];
            }
        }

        return array_values(array_unique($sections));
    }

    private function allowedValue($value, array $allowed, string $fallback): string
    {
        $value = strtolower((string) $value);

        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function stringArray($value): array
    {
        if (! is_array($value)) {
            $value = [$value];
        }

        return array_values(array_unique(array_filter(array_map(
            fn ($item) => strtolower(trim((string) $item)),
            $value
        ))));
    }

    private function nullableInteger($value, int $min, int $max): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return max($min, min($max, (int) $value));
    }

    private function nullableString($value, int $max): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr((string) $value, 0, $max);
    }
}
