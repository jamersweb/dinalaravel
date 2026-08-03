<?php

namespace App\Services;

use App\Jobs\ProcessAiExerciseTagProposalJob;
use App\Models\AiExerciseTagProposal;
use App\Models\Exercise;
use App\Models\ExerciseLibraryTag;
use App\Models\Tag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OllamaExerciseTaggerService
{
    private $legacyExerciseTags = null;

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

        $this->syncExerciseTagsFromPayload($proposal->exercise_id, $payload);

        $proposal->fill([
            'status' => 'applied',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ])->save();

        return $tag;
    }

    public function syncExerciseTagsFromPayload(int $exerciseId, array $payload): array
    {
        $exercise = Exercise::query()->select(['id', 'tags'])->find($exerciseId);
        if (! $exercise) {
            return [];
        }

        $existingIds = $this->parseLegacyTagIds($exercise->getRawOriginal('tags'));
        $aiTagIds = $this->legacyTagIdsFromPayload($payload);
        if ($aiTagIds === []) {
            return [];
        }

        $mergedIds = array_values(array_unique(array_merge($existingIds, $aiTagIds)));
        sort($mergedIds);

        $sortedExisting = $existingIds;
        sort($sortedExisting);
        if ($mergedIds !== $sortedExisting) {
            Exercise::where('id', $exerciseId)->update(['tags' => json_encode($mergedIds)]);
        }

        return array_values(array_diff($mergedIds, $existingIds));
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
                'primary_category' => RoutineLibraryRules::PRIMARY_CATEGORIES,
                'training_adaptation' => RoutineLibraryRules::TRAINING_ADAPTATIONS,
                'program_role' => RoutineLibraryRules::PROGRAM_ROLES,
                'muscle_group' => 'string',
                'secondary_muscle_groups' => ['string'],
                'exercise_type' => ['resistance', 'main', 'bodyweight', 'dumbbell', 'gym', 'cardio', 'cardio_warm_up', 'warm_up', 'mobility', 'stretching', 'activation', 'power_explosive', 'lower_back', 'abs', 'obliques'],
                'movement_patterns' => RoutineLibraryRules::MOVEMENT_PATTERNS,
                'training_styles' => ['resistance_training', 'hypertrophy', 'muscular_endurance', 'conditioning', 'mobility', 'core', 'stretching', 'warm_up'],
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
                'safety_flags' => [
                    'safe_for_warmup' => 'boolean',
                    'safe_for_cooldown' => 'boolean',
                    'unsafe_as_warmup' => 'boolean',
                    'high_impact' => 'boolean',
                    'explosive' => 'boolean',
                ],
                'notes' => 'string',
            ],
            'confidence' => '0_to_1',
            'reasoning' => 'short explanation',
        ];

        return 'You are a fitness-library tagging expert. If an image is attached, inspect it together with the metadata. '
            . 'If no image is attached, classify from the exact metadata only. Never apply the same default tag to every exercise. '
            . 'Use title, current tag, equipment words, muscle words, safety words, and visible equipment/body position from the image when available. '
            . 'Return one JSON object only. Use ONLY the allowed values from the schema. '
            . 'Choose exactly one primary_category. Use resistance_training, never strength_training, for loaded/bodyweight resistance exercises. '
            . 'Use training_adaptation to describe why the exercise is performed: strength, hypertrophy, muscular_endurance, power, explosiveness, aerobic_conditioning, anaerobic_conditioning, mobility, flexibility, muscle_activation, movement_preparation, rehabilitation_corrective, or recovery. '
            . 'Use program_role to say where the exercise belongs in a workout: warm_up_cardio, dynamic_warm_up, activation, main_workout, cardio, finisher, core, cool_down_stretching, corrective, or recovery. '
            . 'If a current deterministic tag exists and the title does not clearly contradict it, keep its equipment_category and language. '
            . 'Deadlift, squat, row, press, curl, bridge, lunge with dumbbell/home dumbbell evidence is not bodyweight. '
            . 'Warm-up titles must use primary_category dynamic_warm_up or mobility and exercise_type warm_up or mobility, not resistance/main. '
            . 'Stretch titles must use primary_category flexibility_stretching, exercise_type stretching, program_role cool_down_stretching, usage_flags.stretching=true, and safety_flags.safe_for_cooldown=true. '
            . 'Important safety rules: HIIT, jumps, high knees, burpees, sprinting, explosive drills are NOT warm-up cardio. '
            . 'High knees, jumping, burpees, sprinting, plyometrics, explosive drills must have safety_flags.unsafe_as_warmup=true and safe_for_warmup=false. '
            . 'Warm-up cardio must be low-impact walking, marching, bike, elliptical, rower, or stepper. '
            . 'Examples: '
            . 'Frogger rockbacks stretch => flexibility_stretching | flexibility | cool_down_stretching | bodyweight | beginner | usage stretching. '
            . 'Deadlift warm up => dynamic_warm_up | movement_preparation | dynamic_warm_up | bodyweight unless current tag says home_dumbbell | beginner | usage warm_up or lower_back_activation. '
            . 'Sumo Deadlift with dumbbells => resistance_training | hypertrophy or strength | main_workout | home_dumbbell | intermediate | usage main_workout. '
            . 'High knee jumps => power_explosive_training | explosiveness or anaerobic_conditioning | finisher | bodyweight | advanced | high impact | unsafe_as_warmup. '
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
        $language = RoutineLibraryRules::normalizeLanguage($this->scalarString($payload['language'] ?? null, 'en'));
        if ($currentTagPayload && ! empty($currentTagPayload['language'])) {
            $language = RoutineLibraryRules::normalizeLanguage($this->scalarString($currentTagPayload['language'], $language));
        } elseif ($metadata && ! empty($metadata['language'])) {
            $language = RoutineLibraryRules::normalizeLanguage($this->scalarString($metadata['language'], $language));
        }

        $equipment = $this->inferEquipmentCategory($payload, $metadata, $currentTagPayload);
        $difficulty = RoutineLibraryRules::normalizeLevel($this->scalarString($payload['difficulty'] ?? null, 'beginner'));
        $impact = $this->allowedValue($this->scalarString($payload['impact_level'] ?? null, 'low'), RoutineLibraryRules::IMPACT_LEVELS, 'low');
        $intensity = $this->allowedValue($this->scalarString($payload['intensity_level'] ?? null, 'moderate'), RoutineLibraryRules::INTENSITY_LEVELS, 'moderate');
        $videoVariant = $this->allowedValue($this->scalarString($payload['video_variant'] ?? null, 'explained'), RoutineLibraryRules::VIDEO_VARIANTS, 'explained');
        $exerciseType = $this->inferExerciseType($payload, $metadata, $currentTagPayload, $equipment);
        $muscleGroup = $this->inferMuscleGroup($payload, $metadata, $currentTagPayload);
        $primaryCategory = $this->inferPrimaryCategory($payload, $metadata, $exerciseType, $muscleGroup);
        $trainingAdaptation = $this->inferTrainingAdaptation($payload, $metadata, $primaryCategory, $exerciseType, $muscleGroup);
        $safetyFlags = $this->safetyFlags($payload, $metadata, $primaryCategory, $trainingAdaptation, $exerciseType, $impact, $intensity);
        $usageFlags = $this->usageFlags((array) ($payload['usage_flags'] ?? []), $exerciseType, $muscleGroup, $metadata, $primaryCategory, $trainingAdaptation, $safetyFlags);
        $programRole = $this->inferProgramRole($payload, $usageFlags, $primaryCategory, $trainingAdaptation, $exerciseType, $safetyFlags);
        $workoutSections = array_values(array_intersect($this->stringArray($payload['workout_sections'] ?? []), array_keys(RoutineLibraryRules::WORKOUT_SECTION_LABELS)));
        if ($workoutSections === []) {
            $workoutSections = $this->sectionsFromUsageFlags($usageFlags);
        }

        return [
            'language' => $language,
            'equipment_category' => $equipment,
            'equipment_tags' => $this->stringArray($payload['equipment_tags'] ?? []),
            'primary_category' => $primaryCategory,
            'training_adaptation' => $trainingAdaptation,
            'program_role' => $programRole,
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
            'safety_flags' => $safetyFlags,
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

    private function inferPrimaryCategory(array $payload, ?array $metadata, string $exerciseType, string $muscleGroup): string
    {
        $title = strtolower($this->scalarString($metadata['title'] ?? null));
        $raw = RoutineLibraryRules::normalizeTaxonomyValue(
            $payload['primary_category'] ?? null,
            RoutineLibraryRules::PRIMARY_CATEGORIES,
            ''
        );

        if ($this->isExplosiveTitle($title)) {
            return 'power_explosive_training';
        }
        if (str_contains($title, 'stretch') || $exerciseType === 'stretching') {
            return 'flexibility_stretching';
        }
        if (preg_match('/\b(breath|breathing|recovery|relax|meditation)\b/', $title)) {
            return 'recovery_breathing';
        }
        if (preg_match('/\b(warm up|warm-up|prep|preparation)\b/', $title) || $exerciseType === 'warm_up') {
            return 'dynamic_warm_up';
        }
        if (preg_match('/\b(activation|activate|glute bridge|dead bug|bird dog)\b/', $title) || $exerciseType === 'activation') {
            return 'muscle_activation';
        }
        if (str_contains($title, 'mobility') || $exerciseType === 'mobility') {
            return 'mobility';
        }
        if (preg_match('/\b(balance|stability|stabilization|single leg hold)\b/', $title)) {
            return 'balance_stability';
        }
        if (preg_match('/\b(corrective|rehab|prehab|posture|back care)\b/', $title)) {
            return 'corrective_exercise';
        }
        if (preg_match('/\b(elliptical|treadmill|walking|walk|bike|cycling|cycle|rower|stepper|cardio)\b/', $title) || in_array($exerciseType, ['cardio', 'cardio_warm_up'], true)) {
            return 'cardiovascular_training';
        }
        if ($raw !== '') {
            return $raw;
        }
        if (in_array($muscleGroup, ['abs', 'obliques', 'lower back'], true)) {
            return 'resistance_training';
        }

        return 'resistance_training';
    }

    private function inferTrainingAdaptation(array $payload, ?array $metadata, string $primaryCategory, string $exerciseType, string $muscleGroup): string
    {
        $title = strtolower($this->scalarString($metadata['title'] ?? null));
        $raw = RoutineLibraryRules::normalizeTaxonomyValue(
            $payload['training_adaptation'] ?? $payload['training_purpose'] ?? null,
            RoutineLibraryRules::TRAINING_ADAPTATIONS,
            ''
        );

        if ($this->isExplosiveTitle($title)) {
            return str_contains($title, 'speed') ? 'speed' : 'explosiveness';
        }
        if (preg_match('/\b(hiit|interval|anaerobic|sprint)\b/', $title)) {
            return 'anaerobic_conditioning';
        }
        if (preg_match('/\b(elliptical|treadmill|walk|walking|bike|cycling|steady|aerobic)\b/', $title)) {
            return 'aerobic_conditioning';
        }
        if ($primaryCategory === 'flexibility_stretching') {
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
        if ($raw !== '') {
            return $raw;
        }
        if (in_array($muscleGroup, ['abs', 'obliques', 'lower back'], true) || in_array($exerciseType, ['abs', 'obliques', 'lower_back'], true)) {
            return 'muscular_endurance';
        }

        return 'general_fitness';
    }

    private function inferProgramRole(array $payload, array $usageFlags, string $primaryCategory, string $trainingAdaptation, string $exerciseType, array $safetyFlags): string
    {
        $raw = RoutineLibraryRules::normalizeTaxonomyValue(
            $payload['program_role'] ?? null,
            RoutineLibraryRules::PROGRAM_ROLES,
            ''
        );

        if ($primaryCategory === 'flexibility_stretching' || ! empty($usageFlags['stretching'])) {
            return 'cool_down_stretching';
        }
        if ($primaryCategory === 'dynamic_warm_up' || ! empty($usageFlags['warm_up'])) {
            return 'dynamic_warm_up';
        }
        if ($primaryCategory === 'muscle_activation' || ! empty($usageFlags['lower_back_activation'])) {
            return 'activation';
        }
        if ($primaryCategory === 'mobility' || ! empty($usageFlags['mobility'])) {
            return 'dynamic_warm_up';
        }
        if ($primaryCategory === 'cardiovascular_training' && ! empty($usageFlags['cardio_warm_up']) && empty($safetyFlags['unsafe_as_warmup'])) {
            return 'warm_up_cardio';
        }
        if ($primaryCategory === 'cardiovascular_training') {
            return 'cardio';
        }
        if ($primaryCategory === 'power_explosive_training' || in_array($trainingAdaptation, ['explosiveness', 'anaerobic_conditioning'], true)) {
            return 'finisher';
        }
        if (! empty($usageFlags['abs']) || ! empty($usageFlags['obliques']) || in_array($exerciseType, ['abs', 'obliques'], true)) {
            return 'core';
        }
        if ($primaryCategory === 'corrective_exercise') {
            return 'corrective';
        }
        if ($primaryCategory === 'recovery_breathing') {
            return 'recovery';
        }

        return $raw !== '' ? $raw : 'main_workout';
    }

    private function safetyFlags(array $payload, ?array $metadata, string $primaryCategory, string $trainingAdaptation, string $exerciseType, string $impact, string $intensity): array
    {
        $provided = is_array($payload['safety_flags'] ?? null) ? $payload['safety_flags'] : [];
        $title = strtolower($this->scalarString($metadata['title'] ?? null));
        $explosive = $this->isExplosiveTitle($title) || $primaryCategory === 'power_explosive_training' || in_array($trainingAdaptation, ['power', 'explosiveness', 'speed'], true);
        $highImpact = $impact === 'high' || $explosive || preg_match('/\b(jump|jumps|jumping|hop|burpee|plyo|plyometric|high knee|high knees|sprint|running)\b/', $title) === 1;
        $unsafeAsWarmup = $highImpact
            || $intensity === 'high'
            || in_array($trainingAdaptation, ['anaerobic_conditioning', 'explosiveness', 'power', 'speed'], true)
            || preg_match('/\b(hiit|interval|tabata|finisher|sprint|burpee|explosive)\b/', $title) === 1;

        $safeForCooldown = $primaryCategory === 'flexibility_stretching'
            || $exerciseType === 'stretching'
            || filter_var($provided['safe_for_cooldown'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $safeForWarmup = ! $unsafeAsWarmup && (
            in_array($primaryCategory, ['dynamic_warm_up', 'mobility', 'muscle_activation'], true)
            || ($primaryCategory === 'cardiovascular_training' && $impact === 'low' && $intensity !== 'high')
            || filter_var($provided['safe_for_warmup'] ?? false, FILTER_VALIDATE_BOOLEAN)
        );

        return [
            'safe_for_warmup' => $safeForWarmup,
            'safe_for_cooldown' => $safeForCooldown,
            'unsafe_as_warmup' => $unsafeAsWarmup,
            'high_impact' => $highImpact,
            'explosive' => $explosive,
        ];
    }

    private function isExplosiveTitle(string $title): bool
    {
        return preg_match('/\b(jump|jumps|jumping|hop|hops|burpee|plyo|plyometric|explosive|power|sprint|high knee|high knees|skater|tuck|climber|jacks)\b/', $title) === 1;
    }

    private function legacyTagIdsFromPayload(array $payload): array
    {
        $ids = [];

        $language = RoutineLibraryRules::normalizeLanguage($this->scalarString($payload['language'] ?? null, 'en'));
        $this->appendLegacyTagId($ids, [
            'en' => ['English'],
            'ar' => ['Arabic'],
            'no_audio' => ['No audio', 'No Audio'],
        ][$language] ?? [], ['Language']);

        $difficulty = RoutineLibraryRules::normalizeLevel($this->scalarString($payload['difficulty'] ?? null, 'beginner'));
        $this->appendLegacyTagId($ids, [ucfirst($difficulty)], ['Level']);

        $equipment = RoutineLibraryRules::normalizeEquipment($this->scalarString($payload['equipment_category'] ?? null, 'bodyweight'));
        $equipmentNames = [
            'bodyweight' => ['Body weight', 'Bodyweight'],
            'home_dumbbell' => ['Dumbbell', 'Dumbbells'],
            'gym' => ['Gym', 'Machine'],
            'full_gym' => ['Gym', 'Barbell', 'Bench', 'Machine'],
        ][$equipment] ?? [];
        foreach ($equipmentNames as $name) {
            $this->appendLegacyTagId($ids, [$name], ['Equipment', 'Gym Machines']);
        }

        foreach ($this->stringArray($payload['equipment_tags'] ?? []) as $equipmentTag) {
            $this->appendLegacyTagId($ids, $this->equipmentTagCandidates($equipmentTag), ['Equipment', 'Gym Machines']);
        }

        foreach ($this->muscleCandidates($this->scalarString($payload['muscle_group'] ?? null)) as $candidate) {
            $this->appendLegacyTagId($ids, [$candidate], ['Main muscle', 'Muscles']);
        }

        foreach ($this->stringArray($payload['secondary_muscle_groups'] ?? []) as $muscle) {
            foreach ($this->muscleCandidates($muscle) as $candidate) {
                $this->appendLegacyTagId($ids, [$candidate], ['Main muscle', 'Muscles']);
            }
        }

        foreach ($this->legacyUsageCandidates($payload) as $candidate) {
            $this->appendLegacyTagId($ids, [$candidate], ['Purpose', 'Training category', 'Training specialty', 'Training speciality']);
        }

        return array_values(array_unique(array_filter($ids)));
    }

    private function equipmentTagCandidates(string $equipmentTag): array
    {
        $key = str_replace([' ', '-'], '_', strtolower($equipmentTag));

        return [
            'bodyweight' => ['Body weight', 'Bodyweight'],
            'body_weight' => ['Body weight', 'Bodyweight'],
            'dumbbell' => ['Dumbbell', 'Dumbbells'],
            'dumbbells' => ['Dumbbell', 'Dumbbells'],
            'machine' => ['Machine'],
            'cable' => ['Cable'],
            'cables' => ['Cable'],
            'barbell' => ['Barbell'],
            'cardio_machine' => ['Cardio Machine'],
            'bench' => ['Bench'],
            'mat' => ['Mat'],
            'band' => ['Bands (handles)', 'Bands (loops)'],
            'bands' => ['Bands (handles)', 'Bands (loops)'],
            'kettlebell' => ['Kettlebell'],
        ][$key] ?? [ucwords(str_replace('_', ' ', $key))];
    }

    private function muscleCandidates(string $muscle): array
    {
        $key = preg_replace('/[^a-z0-9]+/', ' ', strtolower($muscle)) ?? '';
        $key = trim($key);
        if ($key === '' || $key === '-') {
            return [];
        }

        $map = [
            'abs' => ['Abs/Stomach'],
            'core' => ['Abs/Stomach'],
            'stomach' => ['Abs/Stomach'],
            'obliques' => ['Obliques/Side stomach'],
            'side stomach' => ['Obliques/Side stomach'],
            'lower back' => ['Back (lower)', 'Lower back'],
            'upper back' => ['Back (middle)', 'Lats/wider part of the back'],
            'mid back' => ['Back (middle)'],
            'middle back' => ['Back (middle)'],
            'back' => ['Back (middle)', 'Back (lower)'],
            'lats' => ['Lats/wider part of the back'],
            'bicep' => ['Bicep/Upper inner arm'],
            'biceps' => ['Bicep/Upper inner arm'],
            'tricep' => ['Triceps/back part of your upper arm'],
            'triceps' => ['Triceps/back part of your upper arm'],
            'glute' => ['Glutes/Butt'],
            'glutes' => ['Glutes/Butt'],
            'butt' => ['Glutes/Butt'],
            'hamstring' => ['Hamstrings/Back of the legs'],
            'hamstrings' => ['Hamstrings/Back of the legs'],
            'quads' => ['Quads/Front thighs'],
            'quad' => ['Quads/Front thighs'],
            'front thighs' => ['Quads/Front thighs'],
            'calf' => ['Calfs'],
            'calves' => ['Calfs'],
            'chest' => ['Chest (mid)', 'Chest (upper)', 'Chest (inner)'],
            'shoulder' => ['Shoulder (side)', 'Shoulder (front)', 'Shoulder (rear)'],
            'shoulders' => ['Shoulder (side)', 'Shoulder (front)', 'Shoulder (rear)'],
            'front shoulder' => ['Shoulder (front)'],
            'rear shoulder' => ['Shoulder (rear)'],
            'side shoulder' => ['Shoulder (side)'],
            'abductors' => ['Abductors/Hips'],
            'hips' => ['Abductors/Hips'],
            'adductors' => ['Adductors/Inner thigh'],
            'inner thigh' => ['Adductors/Inner thigh'],
            'forearms' => ['Forearms'],
            'neck' => ['Neck'],
            'traps' => ['Traps/Lower neck and upper back'],
        ];

        return $map[$key] ?? [ucwords($key)];
    }

    private function legacyUsageCandidates(array $payload): array
    {
        $candidates = [];
        $exerciseType = strtolower(str_replace([' ', '-'], '_', $this->scalarString($payload['exercise_type'] ?? null)));
        $primaryCategory = RoutineLibraryRules::normalizeTaxonomyValue($payload['primary_category'] ?? null, RoutineLibraryRules::PRIMARY_CATEGORIES, '');
        $trainingAdaptation = RoutineLibraryRules::normalizeTaxonomyValue($payload['training_adaptation'] ?? null, RoutineLibraryRules::TRAINING_ADAPTATIONS, '');
        $programRole = RoutineLibraryRules::normalizeTaxonomyValue($payload['program_role'] ?? null, RoutineLibraryRules::PROGRAM_ROLES, '');
        $typeMap = [
            'strength' => 'Strength',
            'resistance' => 'Strength',
            'dumbbell' => 'Strength',
            'gym' => 'Strength',
            'main' => 'Strength',
            'bodyweight' => 'Body weight',
            'cardio' => 'Cardio',
            'cardio_warm_up' => 'Cardio',
            'warm_up' => 'Warm-up',
            'mobility' => 'Mobility',
            'stretching' => 'Stretching',
        ];
        if (isset($typeMap[$exerciseType])) {
            $candidates[] = $typeMap[$exerciseType];
        }
        $taxonomyMap = [
            'resistance_training' => 'Strength',
            'cardiovascular_training' => 'Cardio',
            'power_explosive_training' => 'Power',
            'mobility' => 'Mobility',
            'dynamic_warm_up' => 'Warm-up',
            'muscle_activation' => 'Activation',
            'flexibility_stretching' => 'Stretching',
            'balance_stability' => 'Balance',
            'corrective_exercise' => 'Rehabilitation',
            'strength' => 'Strength',
            'power' => 'Power',
            'explosiveness' => 'Plyometric',
            'cardiovascular_endurance' => 'Cardio',
            'aerobic_conditioning' => 'Cardio',
            'anaerobic_conditioning' => 'High Intensity',
            'mobility' => 'Mobility',
            'flexibility' => 'Stretching',
            'muscle_activation' => 'Activation',
            'rehabilitation_corrective' => 'Rehabilitation',
            'warm_up_cardio' => 'Cardio',
            'dynamic_warm_up' => 'Warm-up',
            'activation' => 'Activation',
            'main_workout' => 'Strength',
            'cardio' => 'Cardio',
            'finisher' => 'High Intensity',
            'cool_down_stretching' => 'Stretching',
        ];
        foreach ([$primaryCategory, $trainingAdaptation, $programRole] as $taxonomyValue) {
            if (isset($taxonomyMap[$taxonomyValue])) {
                $candidates[] = $taxonomyMap[$taxonomyValue];
            }
        }

        foreach ($this->stringArray($payload['training_styles'] ?? []) as $style) {
            $candidates[] = ucwords(str_replace('_', ' ', $style));
        }

        $flags = is_array($payload['usage_flags'] ?? null) ? $payload['usage_flags'] : [];
        if (! empty($flags['cardio_warm_up'])) {
            $candidates[] = 'Cardio';
        }
        if (! empty($flags['warm_up']) || ! empty($flags['lower_back_activation'])) {
            $candidates[] = 'Warm-up';
            $candidates[] = 'Activation';
        }
        if (! empty($flags['muscle_activation'])) {
            $candidates[] = 'Activation';
        }
        if (! empty($flags['main_workout']) || ! empty($flags['lower_back_strength'])) {
            $candidates[] = 'Strength';
        }

        return array_values(array_unique($candidates));
    }

    private function appendLegacyTagId(array &$ids, array $names, array $types = []): void
    {
        $tagId = $this->findLegacyTagId($names, $types);
        if ($tagId !== null) {
            $ids[] = $tagId;
        }
    }

    private function findLegacyTagId(array $names, array $types = []): ?int
    {
        $tags = $this->legacyExerciseTags();
        $normalizedNames = array_values(array_filter(array_map(fn ($name) => $this->normalizeTagLookup($name), $names)));
        $normalizedTypes = array_values(array_filter(array_map(fn ($type) => $this->normalizeTagLookup($type), $types)));
        if ($normalizedNames === []) {
            return null;
        }

        foreach ($normalizedNames as $name) {
            foreach ($tags as $tag) {
                if ($tag['normalized_name'] === $name && ($normalizedTypes === [] || in_array($tag['normalized_type'], $normalizedTypes, true))) {
                    return $tag['id'];
                }
            }
        }

        foreach ($normalizedNames as $name) {
            foreach ($tags as $tag) {
                if ($tag['normalized_name'] === $name) {
                    return $tag['id'];
                }
            }
        }

        return null;
    }

    private function legacyExerciseTags(): array
    {
        if ($this->legacyExerciseTags !== null) {
            return $this->legacyExerciseTags;
        }

        $this->legacyExerciseTags = Tag::query()
            ->where('category', 'exercise')
            ->orderBy('id')
            ->get(['id', 'name', 'type'])
            ->map(fn (Tag $tag) => [
                'id' => (int) $tag->id,
                'normalized_name' => $this->normalizeTagLookup($tag->name),
                'normalized_type' => $this->normalizeTagLookup($tag->type),
            ])
            ->all();

        return $this->legacyExerciseTags;
    }

    private function normalizeTagLookup($value): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower($this->scalarString($value))) ?? '');
    }

    private function parseLegacyTagIds($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return array_values(array_unique(array_filter(array_map('intval', $value))));
        }

        $decoded = is_string($value) ? json_decode($value, true) : null;
        if (is_array($decoded)) {
            return array_values(array_unique(array_filter(array_map('intval', $decoded))));
        }

        return array_values(array_unique(array_filter(array_map('intval', explode(',', trim((string) $value, '[] '))))));
    }

    private function usageFlags(array $flags, string $exerciseType, string $muscleGroup, ?array $metadata, string $primaryCategory = '', string $trainingAdaptation = '', array $safetyFlags = []): array
    {
        $normalized = [];
        foreach (array_keys(RoutineLibraryRules::REQUIRED_AUDIT_USAGE) as $usage) {
            $normalized[$usage] = filter_var($flags[$usage] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        $title = strtolower((string) ($metadata['title'] ?? ''));
        $forced = array_fill_keys(array_keys(RoutineLibraryRules::REQUIRED_AUDIT_USAGE), false);
        $unsafeAsWarmup = (bool) ($safetyFlags['unsafe_as_warmup'] ?? false);

        if ($primaryCategory === 'flexibility_stretching' || $exerciseType === 'stretching') {
            $forced['stretching'] = true;
            return $forced;
        }
        if ($primaryCategory === 'dynamic_warm_up' || ($exerciseType === 'warm_up' && ! $unsafeAsWarmup)) {
            $forced[($muscleGroup === 'lower back' || str_contains($title, 'activation')) ? 'lower_back_activation' : 'warm_up'] = true;
            return $forced;
        }
        if ($primaryCategory === 'mobility' || $exerciseType === 'mobility') {
            $forced['mobility'] = true;
            return $forced;
        }
        if ($primaryCategory === 'muscle_activation' || $trainingAdaptation === 'muscle_activation' || $exerciseType === 'activation') {
            $forced['muscle_activation'] = true;
            if ($muscleGroup === 'lower back') {
                $forced['lower_back_activation'] = true;
            }
            return $forced;
        }
        if (($exerciseType === 'cardio' || $exerciseType === 'cardio_warm_up' || $primaryCategory === 'cardiovascular_training') && ! $unsafeAsWarmup) {
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

        if ($unsafeAsWarmup) {
            $normalized['cardio_warm_up'] = false;
            $normalized['warm_up'] = false;
            $normalized['lower_back_activation'] = false;
        }

        if (in_array(true, $normalized, true)) {
            return $normalized;
        }

        if (($exerciseType === 'cardio' || $exerciseType === 'cardio_warm_up' || $primaryCategory === 'cardiovascular_training') && ! $unsafeAsWarmup) {
            $normalized['cardio_warm_up'] = true;
        } elseif (($exerciseType === 'warm_up' || $primaryCategory === 'dynamic_warm_up') && ! $unsafeAsWarmup) {
            $normalized['warm_up'] = true;
        } elseif ($exerciseType === 'mobility' || $primaryCategory === 'mobility') {
            $normalized['mobility'] = true;
        } elseif ($exerciseType === 'activation' || $primaryCategory === 'muscle_activation' || $trainingAdaptation === 'muscle_activation') {
            $normalized['muscle_activation'] = true;
        } elseif ($exerciseType === 'stretching' || $primaryCategory === 'flexibility_stretching') {
            $normalized['stretching'] = true;
        } elseif ($muscleGroup === 'abs' || $exerciseType === 'abs') {
            $normalized['abs'] = true;
        } elseif ($muscleGroup === 'obliques' || $exerciseType === 'obliques') {
            $normalized['obliques'] = true;
        } elseif ($muscleGroup === 'lower back' || $exerciseType === 'lower_back') {
            $normalized[str_contains($title, 'warm') || str_contains($title, 'activation') ? 'lower_back_activation' : 'lower_back_strength'] = true;
        } elseif (in_array($primaryCategory, ['resistance_training', 'power_explosive_training', 'balance_stability', 'corrective_exercise'], true)) {
            $normalized['main_workout'] = true;
        } else {
            $normalized['main_workout'] = true;
        }

        return $normalized;
    }

    private function inferEquipmentCategory(array $payload, ?array $metadata, ?array $currentTagPayload): string
    {
        $title = strtolower($this->scalarString($metadata['title'] ?? null));
        $raw = strtolower(str_replace([' ', '-'], '_', $this->scalarString($payload['equipment_category'] ?? null)));

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
            $current = RoutineLibraryRules::normalizeEquipment($this->scalarString($currentTagPayload['equipment_category']));
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
        $title = strtolower($this->scalarString($metadata['title'] ?? null));
        $raw = strtolower(str_replace([' ', '-'], '_', $this->scalarString($payload['exercise_type'] ?? null)));

        if ($this->isExplosiveTitle($title)) {
            return 'power_explosive';
        }
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
        if ($raw === 'strength') {
            return 'resistance';
        }
        if (in_array($raw, ['main', 'resistance', 'bodyweight', 'dumbbell', 'gym', 'cardio', 'cardio_warm_up', 'warm_up', 'mobility', 'stretching', 'activation', 'power_explosive', 'lower_back', 'abs', 'obliques'], true)) {
            return $raw;
        }
        if ($currentTagPayload && ! empty($currentTagPayload['exercise_type'])) {
            $current = strtolower(str_replace([' ', '-'], '_', $this->scalarString($currentTagPayload['exercise_type'])));
            if ($current === 'strength') {
                return 'resistance';
            }
            if (in_array($current, ['main', 'resistance', 'bodyweight', 'dumbbell', 'gym', 'cardio', 'cardio_warm_up', 'warm_up', 'mobility', 'stretching', 'activation', 'power_explosive', 'lower_back', 'abs', 'obliques'], true)) {
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
        $title = strtolower($this->scalarString($metadata['title'] ?? null));
        $muscle = strtolower(trim($this->scalarString($payload['muscle_group'] ?? null)));
        if ($muscle !== '' && $muscle !== '-') {
            return $muscle;
        }
        if ($currentTagPayload && ! empty($currentTagPayload['muscle_group'])) {
            return strtolower($this->scalarString($currentTagPayload['muscle_group']));
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
            'muscle_activation' => 'muscle_activation',
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
        $value = strtolower($this->scalarString($value));

        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function scalarString($value, string $fallback = ''): string
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if (is_scalar($item) && trim((string) $item) !== '') {
                    return trim((string) $item);
                }
            }

            return $fallback;
        }

        if (is_scalar($value) && trim((string) $value) !== '') {
            return trim((string) $value);
        }

        return $fallback;
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

        return substr($this->scalarString($value), 0, $max);
    }
}
