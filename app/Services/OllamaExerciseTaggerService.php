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

        $proposedPayload = $proposal->proposed_payload;
        if (
            (! array_key_exists('confidence', $proposedPayload) || $proposedPayload['confidence'] === null || $proposedPayload['confidence'] === '')
            && $proposal->confidence !== null
        ) {
            $proposedPayload['confidence'] = $proposal->confidence;
        }

        $payload = $this->normalizePayload(
            $proposedPayload,
            is_array($proposal->source_metadata) ? $proposal->source_metadata : null,
            is_array($proposal->current_tag_payload) ? $proposal->current_tag_payload : null
        );
        $reviewBlockers = is_array($payload['review_blockers'] ?? null) ? $payload['review_blockers'] : [];
        if ($approve && $reviewBlockers !== []) {
            throw new RuntimeException('AI proposal has review blockers and must stay in manual review: ' . implode('; ', $reviewBlockers));
        }

        $payload['exercise_id'] = $proposal->exercise_id;
        $payload['approved_for_generation'] = $approve && $reviewBlockers === [];
        $payload['review_status'] = $approve
            ? 'approved'
            : ($reviewBlockers === [] ? 'pending_review' : 'needs_fix');
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
        $numCtx = max(4096, (int) config('services.ollama.num_ctx', 8192));
        $numPredict = max(256, (int) config('services.ollama.num_predict', 1024));
        $request = [
            'model' => $model,
            'stream' => false,
            'format' => 'json',
            'prompt' => $this->prompt($metadata, $currentTagPayload),
            'options' => [
                'temperature' => 0.1,
                'num_ctx' => $numCtx,
                'num_predict' => $numPredict,
            ],
        ];

        if ((bool) config('services.ollama.use_images', true)) {
            $images = $this->ollamaImages($metadata);
            if ($images !== []) {
                $request['images'] = $images;
            }
        }

        $response = Http::timeout($timeout)->post($baseUrl . '/api/generate', $request);

        if (! $response->successful()) {
            if ($response->status() === 404) {
                throw new RuntimeException("Ollama model '{$model}' was not found at {$baseUrl}. Pull it with: ollama pull {$model}");
            }

            throw new RuntimeException('Ollama request failed: HTTP ' . $response->status() . ' ' . $this->shortResponseBody($response->body()));
        }

        $raw = (string) ($response->json('response') ?? '');
        try {
            $decoded = $this->decodeJsonResponse($raw);
        } catch (RuntimeException $e) {
            $decoded = $this->repairJsonResponseWithOllama($baseUrl, $timeout, $model, $raw, $request['options']);
        }
        $tagPayload = is_array($decoded['tag'] ?? null) ? $decoded['tag'] : $decoded;
        $confidence = $this->normalizedConfidence($decoded['confidence'] ?? $tagPayload['confidence'] ?? null);
        $confidence ??= $this->confidenceFromBucket($decoded['confidence_bucket'] ?? $tagPayload['confidence_bucket'] ?? null);
        $confidence ??= 0.5;
        if (! isset($tagPayload['confidence'])) {
            $tagPayload['confidence'] = $confidence;
        }
        $payload = $this->normalizePayload($tagPayload, $metadata, $currentTagPayload);
        $reasoning = trim($this->scalarString($decoded['reasoning'] ?? null));

        return [
            'payload' => $payload,
            'confidence' => $confidence,
            'reasoning' => $reasoning !== '' ? $reasoning : 'AI-generated proposal normalized by the Dina taxonomy guardrails.',
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

    private function shortModelOutput(string $raw): string
    {
        $raw = trim(preg_replace('/\s+/', ' ', $raw) ?? '');
        if ($raw === '') {
            return '[empty]';
        }

        return mb_substr($raw, 0, 700);
    }

    private function repairJsonResponseWithOllama(string $baseUrl, int $timeout, string $model, string $raw, array $options): array
    {
        if (trim($raw) === '') {
            throw new RuntimeException('Ollama response was empty.');
        }

        $repairRequest = [
            'model' => $model,
            'stream' => false,
            'format' => 'json',
            'prompt' => $this->jsonRepairPrompt($raw),
            'options' => array_merge($options, [
                'temperature' => 0,
                'num_ctx' => max(4096, (int) ($options['num_ctx'] ?? 8192)),
                'num_predict' => max(512, (int) ($options['num_predict'] ?? 1024)),
            ]),
        ];

        $response = Http::timeout($timeout)->post($baseUrl . '/api/generate', $repairRequest);
        if (! $response->successful()) {
            throw new RuntimeException(
                'Ollama response was not valid JSON and repair request failed: HTTP '
                . $response->status()
                . ' '
                . $this->shortResponseBody($response->body())
                . ' Raw output: '
                . $this->shortModelOutput($raw)
            );
        }

        $repairRaw = (string) ($response->json('response') ?? '');
        try {
            return $this->decodeJsonResponse($repairRaw);
        } catch (RuntimeException $e) {
            throw new RuntimeException(
                'Ollama response was not valid JSON. Raw output: '
                . $this->shortModelOutput($raw)
                . ' Repair output: '
                . $this->shortModelOutput($repairRaw)
            );
        }
    }

    private function jsonRepairPrompt(string $raw): string
    {
        $schema = [
            'top_level' => ['tag' => 'object', 'confidence' => '0_to_1', 'reasoning' => 'short string'],
            'allowed_tag_values' => [
                'language' => RoutineLibraryRules::LANGUAGES,
                'equipment_category' => RoutineLibraryRules::EQUIPMENT_CATEGORIES,
                'primary_category' => RoutineLibraryRules::PRIMARY_CATEGORIES,
                'training_adaptation' => RoutineLibraryRules::TRAINING_ADAPTATIONS,
                'program_role' => RoutineLibraryRules::PROGRAM_ROLES,
                'exercise_type' => RoutineLibraryRules::EXERCISE_TYPES,
                'body_regions' => RoutineLibraryRules::BODY_REGIONS,
                'workout_sections' => array_keys(RoutineLibraryRules::WORKOUT_SECTION_LABELS),
                'impact_level' => RoutineLibraryRules::IMPACT_LEVELS,
                'intensity_level' => RoutineLibraryRules::INTENSITY_LEVELS,
                'difficulty' => RoutineLibraryRules::LEVELS,
                'confidence_bucket' => RoutineLibraryRules::CONFIDENCE_BUCKETS,
            ],
        ];

        return 'Repair this malformed AI exercise-tagging response into exactly one valid JSON object. '
            . 'Return JSON only, no markdown and no trailing text. '
            . 'The output must have top-level keys tag, confidence, and reasoning. '
            . 'Preserve useful tag fields from the raw response. If confidence or reasoning is missing, set confidence to 0.5 and reasoning to "Recovered from malformed model output; pending manual review." '
            . 'Use only approved values from this compact schema where listed: '
            . json_encode($schema, JSON_UNESCAPED_SLASHES)
            . "\nRaw response:\n"
            . mb_substr($raw, 0, 6000);
    }

    private function prompt(array $metadata, ?array $currentTagPayload): string
    {
        $schema = [
            'tag' => [
                'language' => RoutineLibraryRules::LANGUAGES,
                'equipment_category' => RoutineLibraryRules::EQUIPMENT_CATEGORIES,
                'equipment_tags' => RoutineLibraryRules::EQUIPMENT_TAGS,
                'primary_category' => RoutineLibraryRules::PRIMARY_CATEGORIES,
                'secondary_categories' => RoutineLibraryRules::PRIMARY_CATEGORIES,
                'training_adaptation' => RoutineLibraryRules::TRAINING_ADAPTATIONS,
                'program_role' => RoutineLibraryRules::PROGRAM_ROLES,
                'muscle_group' => 'string',
                'secondary_muscle_groups' => ['string'],
                'body_regions' => RoutineLibraryRules::BODY_REGIONS,
                'exercise_type' => RoutineLibraryRules::EXERCISE_TYPES,
                'exercise_family' => 'string_or_null',
                'movement_direction' => RoutineLibraryRules::MOVEMENT_DIRECTIONS,
                'stability_demand' => RoutineLibraryRules::STABILITY_DEMANDS,
                'variation_type' => RoutineLibraryRules::VARIATION_TYPES,
                'movement_patterns' => RoutineLibraryRules::MOVEMENT_PATTERNS,
                'training_styles' => RoutineLibraryRules::TRAINING_STYLES,
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
                'compatibility_flags' => [
                    'beginner_compatible' => 'boolean',
                    'warmup_compatible' => 'boolean',
                    'cooldown_compatible' => 'boolean',
                    'main_workout_compatible' => 'boolean',
                    'low_impact_compatible' => 'boolean',
                ],
                'regression_exercise_id' => 'integer_or_null',
                'progression_exercise_id' => 'integer_or_null',
                'alternative_exercise_ids' => ['integer'],
                'confidence_bucket' => RoutineLibraryRules::CONFIDENCE_BUCKETS,
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

        return 'You are a fitness-library tagging expert for an automated exercise tagging and workout generation system. If an image is attached, inspect it together with the metadata. '
            . 'If no image is attached, classify from the exact metadata only. Never apply the same default tag to every exercise. '
            . 'Use title, current tag, equipment words, muscle words, safety words, and visible equipment/body position from the image when available. '
            . 'Return one JSON object only. Use ONLY the allowed values from the schema. '
            . 'Choose exactly one primary_category and zero or more secondary_categories. Use resistance_training, never strength_training, for loaded/bodyweight resistance exercises. '
            . 'Approved primary categories include resistance_training, cardiovascular_training, power_explosive_training, mobility, dynamic_warm_up, muscle_activation, flexibility_stretching, balance_stability, corrective_exercise, recovery_breathing, warm_up_cardio, cool_down_cardio, steady_state_cardio, optional_additional_cardio, hiit_cardio, post_workout_stretching, and circuit_training. '
            . 'Use training_adaptation to describe why the exercise is performed: strength, hypertrophy, muscular_endurance, power, explosiveness, aerobic_conditioning, anaerobic_conditioning, mobility, flexibility, muscle_activation, movement_preparation, rehabilitation_corrective, or recovery. '
            . 'Use program_role to say where the exercise belongs in a workout: warm_up, activation, lower_back_core_preparation, main_workout, main_compound_exercise, accessory_exercise, isolation_exercise, superset_exercise, circuit_exercise, hiit_interval, optional_cardio, cool_down, post_workout_stretching, corrective, or recovery. '
            . 'Set body_regions from the body-region list and movement_patterns from the movement-pattern list so the builder can create balanced routines. '
            . 'Set exercise_family, movement_direction, stability_demand, and variation_type so the builder can avoid duplicate variations in one workout. '
            . 'Use only schema values for equipment_tags, exercise_type, movement_patterns, training_styles, workout_sections, movement_direction, stability_demand, variation_type, and confidence_bucket. '
            . 'If a current deterministic tag exists and the title does not clearly contradict it, keep its equipment_category and language. '
            . 'Deadlift, squat, row, press, curl, bridge, lunge with dumbbell/home dumbbell evidence is not bodyweight. '
            . 'Do not classify unilateral or balance-demand resistance exercises as muscular_endurance from movement alone. Use muscular_endurance only when metadata shows high repetitions, timed/prolonged work, light-load endurance, conditioning, or circuit programming. Otherwise loaded resistance exercises default to strength or hypertrophy, with balance_stability as a secondary category when relevant. '
            . 'Single-leg deadlift, SL wall deadlift, Bulgarian split squat, step-up, pistol squat, and single-leg RDL are resistance_training with strength or hypertrophy unless programming evidence says endurance. '
            . 'Warm-up titles must use primary_category dynamic_warm_up, mobility, muscle_activation, or warm_up_cardio and exercise_type warm_up, mobility, activation, or cardio, not resistance/main. '
            . 'Stretch titles must use primary_category post_workout_stretching or flexibility_stretching, exercise_type stretching, program_role post_workout_stretching or cool_down_stretching, usage_flags.stretching=true, and safety_flags.safe_for_cooldown=true. '
            . 'Important safety rules: HIIT, jumps, high knees, burpees, sprinting, explosive drills are NOT warm-up cardio. '
            . 'High knees, jumping, burpees, sprinting, plyometrics, explosive drills must have safety_flags.unsafe_as_warmup=true and safe_for_warmup=false. '
            . 'Warm-up cardio must be low-impact walking, marching, bike, elliptical, rower, or stepper. '
            . 'Examples: '
            . 'Frogger rockbacks stretch => post_workout_stretching | flexibility | post_workout_stretching | bodyweight | beginner | usage stretching. '
            . 'Deadlift warm up => dynamic_warm_up | movement_preparation | dynamic_warm_up | bodyweight unless current tag says home_dumbbell | beginner | usage warm_up or lower_back_activation. '
            . 'Sumo Deadlift with dumbbells => resistance_training | hypertrophy or strength | main_compound_exercise | home_dumbbell | intermediate | usage main_workout. '
            . 'SL wall deadlift => resistance_training | strength | main_compound_exercise | home_dumbbell | secondary balance_stability | movement_direction unilateral. '
            . 'High knee jumps => power_explosive_training with secondary hiit_cardio | explosiveness or anaerobic_conditioning | hiit_interval | bodyweight | advanced | high impact | unsafe_as_warmup. '
            . "Schema:\n" . json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            . "\nExercise metadata:\n" . json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            . "\nCurrent deterministic tag if any:\n" . json_encode($currentTagPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function decodeJsonResponse(string $raw): array
    {
        $candidates = [$raw];
        $balanced = $this->firstBalancedJsonObject($raw);
        if ($balanced !== null) {
            array_unshift($candidates, $balanced);
        }

        if (preg_match('/\{.*\}/s', $raw, $matches)) {
            $candidates[] = $matches[0];
        }

        foreach (array_unique(array_filter(array_map('trim', $candidates))) as $candidate) {
            $decoded = $this->decodeJsonCandidate($candidate);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $recovered = $this->recoverTagObject($raw);
        if ($recovered !== null) {
            return $recovered;
        }

        throw new RuntimeException('Ollama response was not valid JSON.');
    }

    private function decodeJsonCandidate(string $candidate): ?array
    {
        foreach ([$candidate, $this->escapeControlCharactersInJsonStrings($candidate)] as $variant) {
            $decoded = json_decode($variant, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function recoverTagObject(string $raw): ?array
    {
        if (! preg_match('/"tag"\s*:/', $raw, $match, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $offset = $match[0][1] + strlen($match[0][0]);
        $tagStart = strpos($raw, '{', $offset);
        if ($tagStart === false) {
            return null;
        }

        $tagJson = $this->firstBalancedJsonObject(substr($raw, $tagStart));
        if ($tagJson === null) {
            return null;
        }

        $tag = $this->decodeJsonCandidate($tagJson);
        if (! is_array($tag)) {
            return null;
        }

        $decoded = [
            'tag' => $tag,
            'reasoning' => 'Recovered tag object from malformed Ollama JSON; proposal requires manual review.',
        ];

        if (preg_match('/"confidence"\s*:\s*(0(?:\.\d+)?|1(?:\.0+)?)/', $raw, $confidenceMatch)) {
            $decoded['confidence'] = (float) $confidenceMatch[1];
        }

        return $decoded;
    }

    private function firstBalancedJsonObject(string $text): ?string
    {
        $start = null;
        $depth = 0;
        $inString = false;
        $escaped = false;
        $length = strlen($text);

        for ($index = 0; $index < $length; $index++) {
            $char = $text[$index];
            if ($start === null) {
                if ($char === '{') {
                    $start = $index;
                    $depth = 1;
                }
                continue;
            }

            if ($escaped) {
                $escaped = false;
                continue;
            }
            if ($char === '\\') {
                $escaped = true;
                continue;
            }
            if ($char === '"') {
                $inString = ! $inString;
                continue;
            }
            if ($inString) {
                continue;
            }
            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($text, $start, $index - $start + 1);
                }
            }
        }

        return null;
    }

    private function escapeControlCharactersInJsonStrings(string $text): string
    {
        $result = '';
        $inString = false;
        $escaped = false;
        $length = strlen($text);

        for ($index = 0; $index < $length; $index++) {
            $char = $text[$index];
            $ord = ord($char);

            if ($escaped) {
                $result .= $char;
                $escaped = false;
                continue;
            }
            if ($char === '\\') {
                $result .= $char;
                $escaped = true;
                continue;
            }
            if ($char === '"') {
                $result .= $char;
                $inString = ! $inString;
                continue;
            }
            if ($inString && $ord < 32) {
                $result .= match ($char) {
                    "\n" => '\\n',
                    "\r" => '\\r',
                    "\t" => '\\t',
                    default => ' ',
                };
                continue;
            }

            $result .= $char;
        }

        return $result;
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
        $secondaryCategories = $this->inferSecondaryCategories($payload, $metadata, $primaryCategory, $exerciseType);
        $trainingAdaptation = $this->inferTrainingAdaptation($payload, $metadata, $primaryCategory, $exerciseType, $muscleGroup);
        $safetyFlags = $this->safetyFlags($payload, $metadata, $primaryCategory, $trainingAdaptation, $exerciseType, $impact, $intensity);
        $usageFlags = $this->usageFlags((array) ($payload['usage_flags'] ?? []), $exerciseType, $muscleGroup, $metadata, $primaryCategory, $trainingAdaptation, $safetyFlags);
        $programRole = $this->inferProgramRole($payload, $metadata, $usageFlags, $primaryCategory, $trainingAdaptation, $exerciseType, $safetyFlags);
        $bodyRegions = $this->inferBodyRegions($payload, $muscleGroup, $this->stringArray($payload['secondary_muscle_groups'] ?? []), $metadata);
        $equipmentTags = $this->allowedStringArray($payload['equipment_tags'] ?? [], RoutineLibraryRules::EQUIPMENT_TAGS);
        $movementPatterns = $this->allowedStringArray($payload['movement_patterns'] ?? [], RoutineLibraryRules::MOVEMENT_PATTERNS);
        $trainingStyles = $this->allowedStringArray($payload['training_styles'] ?? [], RoutineLibraryRules::TRAINING_STYLES);
        $workoutSections = $this->allowedStringArray($payload['workout_sections'] ?? [], array_keys(RoutineLibraryRules::WORKOUT_SECTION_LABELS));
        if ($workoutSections === []) {
            $workoutSections = $this->sectionsFromUsageFlags($usageFlags);
        }
        $exerciseFamily = $this->inferExerciseFamily($payload, $metadata, $exerciseType, $muscleGroup, $movementPatterns);
        $movementDirection = $this->inferMovementDirection($payload, $metadata, $movementPatterns);
        $stabilityDemand = $this->inferStabilityDemand($payload, $metadata, $movementPatterns);
        $variationType = $this->inferVariationType($payload, $metadata);
        $compatibilityFlags = $this->compatibilityFlags($payload, $difficulty, $primaryCategory, $usageFlags, $safetyFlags, $impact);
        $confidenceBucket = $this->confidenceBucket($payload);
        $reviewBlockers = $this->reviewBlockers(
            $payload,
            $confidenceBucket,
            $primaryCategory,
            $usageFlags,
            $safetyFlags,
            [
                'equipment_tags' => RoutineLibraryRules::EQUIPMENT_TAGS,
                'movement_patterns' => RoutineLibraryRules::MOVEMENT_PATTERNS,
                'training_styles' => RoutineLibraryRules::TRAINING_STYLES,
                'workout_sections' => array_keys(RoutineLibraryRules::WORKOUT_SECTION_LABELS),
            ]
        );

        return [
            'language' => $language,
            'equipment_category' => $equipment,
            'equipment_tags' => $equipmentTags,
            'primary_category' => $primaryCategory,
            'secondary_categories' => $secondaryCategories,
            'training_adaptation' => $trainingAdaptation,
            'program_role' => $programRole,
            'muscle_group' => $muscleGroup,
            'secondary_muscle_groups' => $this->stringArray($payload['secondary_muscle_groups'] ?? []),
            'body_regions' => $bodyRegions,
            'exercise_type' => $exerciseType,
            'exercise_family' => $exerciseFamily,
            'movement_direction' => $movementDirection,
            'stability_demand' => $stabilityDemand,
            'variation_type' => $variationType,
            'movement_patterns' => $movementPatterns,
            'training_styles' => $trainingStyles,
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
            'compatibility_flags' => $compatibilityFlags,
            'regression_exercise_id' => $this->nullablePositiveInteger($payload['regression_exercise_id'] ?? null),
            'progression_exercise_id' => $this->nullablePositiveInteger($payload['progression_exercise_id'] ?? null),
            'alternative_exercise_ids' => $this->integerArray($payload['alternative_exercise_ids'] ?? []),
            'usage_flags' => $usageFlags,
            'safety_flags' => $safetyFlags,
            'approved_for_generation' => false,
            'confidence_bucket' => $confidenceBucket,
            'review_status' => $reviewBlockers === [] ? 'pending_review' : 'needs_fix',
            'review_blockers' => $reviewBlockers,
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

        if (preg_match('/\b(hiit|tabata|interval)\b/', $title)) {
            return 'hiit_cardio';
        }
        if ($this->isExplosiveTitle($title)) {
            return 'power_explosive_training';
        }
        if (str_contains($title, 'stretch') || $exerciseType === 'stretching') {
            return preg_match('/\b(post|cool|cooldown|cool down|end|finish)\b/', $title) ? 'post_workout_stretching' : 'flexibility_stretching';
        }
        if (preg_match('/\b(breath|breathing|recovery|relax|meditation)\b/', $title)) {
            return 'recovery_breathing';
        }
        if (preg_match('/\b(circuit)\b/', $title)) {
            return 'circuit_training';
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
        if (preg_match('/\b(steady|zone 2|zone two|aerobic)\b/', $title)) {
            return 'steady_state_cardio';
        }
        if (preg_match('/\b(cool down cardio|cool-down cardio)\b/', $title)) {
            return 'cool_down_cardio';
        }
        if (preg_match('/\b(elliptical|treadmill|walking|walk|bike|cycling|cycle|rower|stepper|cardio)\b/', $title) || in_array($exerciseType, ['cardio', 'cardio_warm_up'], true)) {
            if (preg_match('/\b(warm|warmup|warm-up)\b/', $title) || $exerciseType === 'cardio_warm_up') {
                return 'warm_up_cardio';
            }
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

    private function inferSecondaryCategories(array $payload, ?array $metadata, string $primaryCategory, string $exerciseType): array
    {
        $title = strtolower($this->scalarString($metadata['title'] ?? null));
        $secondary = array_values(array_filter(array_map(
            fn ($value) => RoutineLibraryRules::normalizeTaxonomyValue($value, RoutineLibraryRules::PRIMARY_CATEGORIES, ''),
            $this->stringArray($payload['secondary_categories'] ?? [])
        )));
        if (count($secondary) > 4) {
            $secondary = [];
        }

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
        if ($primaryCategory === 'resistance_training' && preg_match('/\b(single leg|one leg|sl\b|wall deadlift|split squat|bulgarian|step up|pistol squat|single-leg|single arm|one arm|unilateral)\b/', $title)) {
            $secondary[] = 'balance_stability';
        }
        if ($primaryCategory === 'resistance_training' && preg_match('/\b(activation|activate|prehab|rehab|corrective|hip stability|pelvic stability)\b/', $title)) {
            $secondary[] = str_contains($title, 'activation') || str_contains($title, 'activate') ? 'muscle_activation' : 'corrective_exercise';
        }

        return array_values(array_diff(array_unique($secondary), [$primaryCategory, '']));
    }

    private function inferTrainingAdaptation(array $payload, ?array $metadata, string $primaryCategory, string $exerciseType, string $muscleGroup): string
    {
        $title = strtolower($this->scalarString($metadata['title'] ?? null));
        $rawKey = $this->normalizeControlledKey($payload['training_adaptation'] ?? $payload['training_purpose'] ?? null);
        $rawKey = [
            'muscle_gain' => 'hypertrophy',
            'muscle_growth' => 'hypertrophy',
            'muscle_building' => 'hypertrophy',
            'toning' => 'muscular_endurance',
            'lower_back_strength' => 'strength',
            'core_strength' => 'strength',
            'core' => 'stability',
        ][$rawKey] ?? $rawKey;
        $raw = RoutineLibraryRules::normalizeTaxonomyValue(
            $rawKey,
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
        if (in_array($primaryCategory, ['flexibility_stretching', 'post_workout_stretching'], true)) {
            return 'flexibility';
        }
        if ($primaryCategory === 'mobility') {
            return 'mobility';
        }
        if (in_array($primaryCategory, ['dynamic_warm_up', 'warm_up_cardio'], true)) {
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
        if (in_array($primaryCategory, ['cardiovascular_training', 'steady_state_cardio', 'cool_down_cardio'], true)) {
            return 'aerobic_conditioning';
        }
        if ($primaryCategory === 'hiit_cardio') {
            return 'anaerobic_conditioning';
        }
        if ($primaryCategory === 'circuit_training') {
            return 'muscular_endurance';
        }
        if ($primaryCategory === 'recovery_breathing') {
            return 'recovery';
        }
        if ($raw === 'muscular_endurance' && $primaryCategory === 'resistance_training' && ! $this->hasMuscularEnduranceProgrammingEvidence($payload, $metadata, $exerciseType)) {
            return $this->defaultResistanceTrainingAdaptation($payload, $metadata, $exerciseType, $muscleGroup);
        }
        if ($raw !== '') {
            return $raw;
        }
        if ($primaryCategory === 'resistance_training') {
            return $this->defaultResistanceTrainingAdaptation($payload, $metadata, $exerciseType, $muscleGroup);
        }
        if (in_array($muscleGroup, ['abs', 'obliques'], true) || in_array($exerciseType, ['abs', 'obliques'], true)) {
            return 'stability';
        }
        if ($muscleGroup === 'lower back' || $exerciseType === 'lower_back') {
            return 'strength';
        }

        return 'general_fitness';
    }

    private function hasMuscularEnduranceProgrammingEvidence(array $payload, ?array $metadata, string $exerciseType): bool
    {
        $title = strtolower($this->scalarString($metadata['title'] ?? null));
        $reps = strtolower($this->scalarString($payload['recommended_repetitions'] ?? null));
        $sets = strtolower($this->scalarString($payload['recommended_sets'] ?? null));
        $role = RoutineLibraryRules::normalizeTaxonomyValue($payload['program_role'] ?? null, RoutineLibraryRules::PROGRAM_ROLES, '');
        $text = trim(implode(' ', array_filter([$title, $reps, $sets])));

        if (preg_match('/\b(circuit|amrap|emom|tabata|conditioning|endurance|burnout|finisher|high reps?|many reps?|light load|light weight|very light|time under tension|for time|timed)\b/', $text)) {
            return true;
        }
        if (in_array($role, ['circuit_exercise', 'hiit_interval', 'finisher'], true) || $exerciseType === 'cardio') {
            return true;
        }
        if (array_intersect($this->allowedStringArray($payload['training_styles'] ?? [], RoutineLibraryRules::TRAINING_STYLES), ['circuit', 'conditioning', 'hiit']) !== []) {
            return true;
        }
        if (array_intersect($this->allowedStringArray($payload['workout_sections'] ?? [], array_keys(RoutineLibraryRules::WORKOUT_SECTION_LABELS)), ['optional_additional_cardio']) !== []) {
            return true;
        }
        if ($this->maxNumberInText($reps) >= 20) {
            return true;
        }

        return $this->nullableInteger($payload['recommended_duration_seconds'] ?? null, 0, 3600) >= 60;
    }

    private function defaultResistanceTrainingAdaptation(array $payload, ?array $metadata, string $exerciseType, string $muscleGroup): string
    {
        $title = strtolower($this->scalarString($metadata['title'] ?? null));
        if (preg_match('/\b(plank|dead bug|bird dog|pallof|anti rotation|anti-rotation|balance|stability|stabilization)\b/', $title)
            || in_array($muscleGroup, ['abs', 'obliques'], true)
            || in_array($exerciseType, ['abs', 'obliques'], true)) {
            return 'stability';
        }
        if (preg_match('/\b(curl|raise|fly|extension|kickback|abduction|adduction|calf raise)\b/', $title)) {
            return 'hypertrophy';
        }

        return 'strength';
    }

    private function maxNumberInText(string $text): int
    {
        preg_match_all('/\d+/', $text, $matches);
        if (empty($matches[0])) {
            return 0;
        }

        return max(array_map('intval', $matches[0]));
    }

    private function inferProgramRole(array $payload, ?array $metadata, array $usageFlags, string $primaryCategory, string $trainingAdaptation, string $exerciseType, array $safetyFlags): string
    {
        $rawKey = $this->normalizeControlledKey($payload['program_role'] ?? null);
        $rawKey = [
            'abs' => 'core',
            'main_laying' => 'main_workout',
            'main_strength_weps_and_weights' => 'main_workout',
            'main_strength_reps_and_weights' => 'main_workout',
            'main_strength_weights' => 'main_workout',
            'lower_back_superset' => 'lower_back_core_preparation',
            'lower_back_core_superset' => 'lower_back_core_preparation',
            'core_lower_back_superset' => 'lower_back_core_preparation',
        ][$rawKey] ?? $rawKey;
        $raw = RoutineLibraryRules::normalizeTaxonomyValue(
            $rawKey,
            RoutineLibraryRules::PROGRAM_ROLES,
            ''
        );

        if (in_array($primaryCategory, ['flexibility_stretching', 'post_workout_stretching'], true) || ! empty($usageFlags['stretching'])) {
            if (in_array($raw, ['post_workout_stretching', 'cool_down_stretching'], true)) {
                return $raw;
            }
            return $primaryCategory === 'post_workout_stretching' ? 'post_workout_stretching' : 'cool_down_stretching';
        }
        if ($primaryCategory === 'cool_down_cardio') {
            return 'cool_down';
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
        if ($primaryCategory === 'flexibility_stretching' || ! empty($usageFlags['stretching'])) {
            return 'cool_down_stretching';
        }
        if ($primaryCategory === 'dynamic_warm_up' || ! empty($usageFlags['warm_up'])) {
            return $raw === 'warm_up' ? 'warm_up' : 'dynamic_warm_up';
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
            return $raw === 'optional_cardio' ? 'optional_cardio' : 'cardio';
        }
        if ($primaryCategory === 'power_explosive_training' || in_array($trainingAdaptation, ['explosiveness', 'anaerobic_conditioning'], true)) {
            return $raw === 'hiit_interval' ? 'hiit_interval' : 'finisher';
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

        if ($raw !== '') {
            return $raw;
        }

        return $this->mainWorkoutRoleFromTitle($metadata);
    }

    private function mainWorkoutRoleFromTitle(?array $metadata): string
    {
        $title = strtolower($this->scalarString($metadata['title'] ?? null));
        if (preg_match('/\b(curl|extension|kickback|raise|fly|abduction|adduction|calf raise)\b/', $title)) {
            return 'isolation_exercise';
        }
        if (preg_match('/\b(squat|deadlift|rdl|lunge|press|row|pull up|pulldown|hip thrust|step up)\b/', $title)) {
            return 'main_compound_exercise';
        }

        return 'main_workout';
    }

    private function inferBodyRegions(array $payload, string $muscleGroup, array $secondaryMuscleGroups, ?array $metadata): array
    {
        $regions = array_values(array_filter(array_map(
            fn ($value) => $this->normalizeBodyRegion($value),
            $this->stringArray($payload['body_regions'] ?? [])
        )));

        foreach (array_merge([$muscleGroup], $secondaryMuscleGroups) as $muscle) {
            foreach ($this->bodyRegionsForMuscle($muscle) as $region) {
                $regions[] = $region;
            }
        }

        $title = strtolower($this->scalarString($metadata['title'] ?? null));
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

    private function normalizeBodyRegion($value): string
    {
        $value = strtolower($this->scalarString($value));
        $value = str_replace(['&', '/', '-'], ' ', $value);
        $value = trim(preg_replace('/[^a-z0-9]+/', '_', $value) ?? '', '_');
        $aliases = [
            'quad' => 'quadriceps',
            'quads' => 'quadriceps',
            'arms_biceps_triceps' => 'arms',
            'stomach' => 'abs',
            'low_back' => 'lower_back',
            'back_lower' => 'lower_back',
            'upper' => 'upper_body',
            'lower' => 'lower_body',
            'hip' => 'lower_body',
            'hips' => 'lower_body',
            'posterior_chain' => 'lower_body',
            'midsection' => 'core',
            'abdominals' => 'abs',
        ];
        $value = $aliases[$value] ?? $value;

        return in_array($value, RoutineLibraryRules::BODY_REGIONS, true) ? $value : '';
    }

    private function bodyRegionsForMuscle(string $muscle): array
    {
        $key = trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower($muscle)) ?? '');

        return [
            'abs' => ['core', 'abs'],
            'core' => ['core'],
            'obliques' => ['core', 'obliques'],
            'lower back' => ['back', 'lower_back'],
            'upper back' => ['back', 'upper_body'],
            'back' => ['back'],
            'lats' => ['back', 'upper_body'],
            'biceps' => ['arms', 'upper_body'],
            'bicep' => ['arms', 'upper_body'],
            'triceps' => ['arms', 'upper_body'],
            'tricep' => ['arms', 'upper_body'],
            'glutes' => ['lower_body', 'glutes'],
            'glute' => ['lower_body', 'glutes'],
            'hamstrings' => ['lower_body', 'hamstrings'],
            'hamstring' => ['lower_body', 'hamstrings'],
            'quads' => ['lower_body', 'quadriceps'],
            'quad' => ['lower_body', 'quadriceps'],
            'quadriceps' => ['lower_body', 'quadriceps'],
            'calves' => ['lower_body', 'calves'],
            'calf' => ['lower_body', 'calves'],
            'chest' => ['upper_body', 'chest'],
            'shoulders' => ['upper_body', 'shoulders'],
            'shoulder' => ['upper_body', 'shoulders'],
        ][$key] ?? [];
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

        $safeForCooldown = in_array($primaryCategory, ['flexibility_stretching', 'post_workout_stretching', 'recovery_breathing', 'cool_down_cardio'], true)
            || $exerciseType === 'stretching'
            || filter_var($provided['safe_for_cooldown'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $safeForWarmup = ! $unsafeAsWarmup && (
            in_array($primaryCategory, ['dynamic_warm_up', 'mobility', 'muscle_activation', 'warm_up_cardio'], true)
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
            'warm_up_cardio' => 'Cardio',
            'cool_down_cardio' => 'Cardio',
            'steady_state_cardio' => 'Cardio',
            'optional_additional_cardio' => 'Cardio',
            'hiit_cardio' => 'High Intensity',
            'post_workout_stretching' => 'Stretching',
            'circuit_training' => 'Circuit',
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
            'warm_up' => 'Warm-up',
            'activation' => 'Activation',
            'lower_back_core_preparation' => 'Activation',
            'main_workout' => 'Strength',
            'main_compound_exercise' => 'Strength',
            'accessory_exercise' => 'Strength',
            'isolation_exercise' => 'Strength',
            'superset_exercise' => 'Strength',
            'circuit_exercise' => 'Circuit',
            'hiit_interval' => 'High Intensity',
            'cardio' => 'Cardio',
            'optional_cardio' => 'Cardio',
            'finisher' => 'High Intensity',
            'cool_down' => 'Recovery',
            'cool_down_stretching' => 'Stretching',
            'post_workout_stretching' => 'Stretching',
        ];
        $taxonomyValues = [$primaryCategory, $trainingAdaptation, $programRole];
        foreach ($this->stringArray($payload['secondary_categories'] ?? []) as $category) {
            $taxonomyValues[] = RoutineLibraryRules::normalizeTaxonomyValue($category, RoutineLibraryRules::PRIMARY_CATEGORIES, '');
        }
        foreach ($taxonomyValues as $taxonomyValue) {
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

        if (in_array($primaryCategory, ['flexibility_stretching', 'post_workout_stretching'], true) || $exerciseType === 'stretching') {
            $forced['stretching'] = true;
            return $forced;
        }
        if ($primaryCategory === 'warm_up_cardio' && ! $unsafeAsWarmup) {
            $forced['cardio_warm_up'] = true;
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
        if (($exerciseType === 'cardio' || $exerciseType === 'cardio_warm_up' || in_array($primaryCategory, ['cardiovascular_training', 'steady_state_cardio', 'cool_down_cardio'], true)) && ! $unsafeAsWarmup) {
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

        if (($exerciseType === 'cardio' || $exerciseType === 'cardio_warm_up' || in_array($primaryCategory, ['cardiovascular_training', 'warm_up_cardio'], true)) && ! $unsafeAsWarmup) {
            $normalized['cardio_warm_up'] = true;
        } elseif (($exerciseType === 'warm_up' || $primaryCategory === 'dynamic_warm_up') && ! $unsafeAsWarmup) {
            $normalized['warm_up'] = true;
        } elseif ($exerciseType === 'mobility' || $primaryCategory === 'mobility') {
            $normalized['mobility'] = true;
        } elseif ($exerciseType === 'activation' || $primaryCategory === 'muscle_activation' || $trainingAdaptation === 'muscle_activation') {
            $normalized['muscle_activation'] = true;
        } elseif ($exerciseType === 'stretching' || in_array($primaryCategory, ['flexibility_stretching', 'post_workout_stretching'], true)) {
            $normalized['stretching'] = true;
        } elseif ($muscleGroup === 'abs' || $exerciseType === 'abs') {
            $normalized['abs'] = true;
        } elseif ($muscleGroup === 'obliques' || $exerciseType === 'obliques') {
            $normalized['obliques'] = true;
        } elseif ($muscleGroup === 'lower back' || $exerciseType === 'lower_back') {
            $normalized[str_contains($title, 'warm') || str_contains($title, 'activation') ? 'lower_back_activation' : 'lower_back_strength'] = true;
        } elseif (in_array($primaryCategory, ['resistance_training', 'power_explosive_training', 'balance_stability', 'corrective_exercise', 'circuit_training', 'hiit_cardio'], true)) {
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
        $raw = [
            'lower' => 'resistance',
            'lower_body' => 'resistance',
            'upper' => 'resistance',
            'upper_body' => 'resistance',
            'strength' => 'resistance',
            'strength_training' => 'resistance',
        ][$raw] ?? $raw;

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
            'warm_up' => 'dynamic_warm_up',
            'mobility' => 'dynamic_warm_up',
            'muscle_activation' => 'muscle_activation',
            'lower_back_activation' => 'lower_back_core_superset',
            'main_workout' => 'main_workout',
            'abs' => 'lower_back_core_superset',
            'obliques' => 'lower_back_core_superset',
            'lower_back_strength' => 'lower_back_core_superset',
            'stretching' => 'post_workout_stretching',
        ];

        $sections = [];
        foreach ($usageFlags as $usage => $enabled) {
            if ($enabled && isset($map[$usage])) {
                $sections[] = $map[$usage];
            }
        }

        return array_values(array_unique($sections));
    }

    private function inferExerciseFamily(array $payload, ?array $metadata, string $exerciseType, string $muscleGroup, array $movementPatterns): ?string
    {
        $raw = $this->nullableString($payload['exercise_family'] ?? null, 128);
        if ($raw) {
            return $this->familyKey($raw);
        }

        $title = strtolower($this->scalarString($metadata['title'] ?? null));
        $title = preg_replace('/\b(with|using)?\s*(dumbbells?|db|barbell|cable|machine|bench|mat|band|bands)\b/', ' ', $title) ?? $title;
        $title = preg_replace('/\b(beginner|intermediate|advanced|level\s*\d+|variation|progression|regression|modified|assisted|weighted|bodyweight)\b/', ' ', $title) ?? $title;
        $title = preg_replace('/\b(left|right|alternating|single arm|single leg|one arm|one leg|bilateral|unilateral|seated|standing|kneeling|incline|decline)\b/', ' ', $title) ?? $title;
        $title = trim(preg_replace('/\s+/', ' ', $title) ?? '');
        if ($title !== '') {
            return $this->familyKey($title);
        }

        $pattern = $movementPatterns[0] ?? null;
        $parts = array_filter([$muscleGroup, $pattern, $exerciseType]);

        return $parts === [] ? null : $this->familyKey(implode(' ', $parts));
    }

    private function inferMovementDirection(array $payload, ?array $metadata, array $movementPatterns): ?string
    {
        $raw = $this->normalizedAllowedValue($payload['movement_direction'] ?? null, RoutineLibraryRules::MOVEMENT_DIRECTIONS, '');
        if ($raw !== '') {
            return $raw;
        }

        $title = strtolower($this->scalarString($metadata['title'] ?? null));
        if (preg_match('/\b(sl|single|single-leg|one arm|one leg|alternating|split|side plank)\b/', $title)) {
            return 'unilateral';
        }
        if (preg_match('/\b(carry|farmer|suitcase)\b/', $title)) {
            return 'loaded_carry';
        }
        if (preg_match('/\b(lateral|side step|side lunge|side plank)\b/', $title)) {
            return 'lateral';
        }
        if (preg_match('/\b(row|pull down|pulldown|pull-up|pull up)\b/', $title)) {
            return str_contains($title, 'pulldown') || str_contains($title, 'pull up') || str_contains($title, 'pull-up') ? 'vertical_pull' : 'horizontal_pull';
        }
        if (preg_match('/\b(press|push up|push-up)\b/', $title)) {
            return preg_match('/\b(overhead|shoulder)\b/', $title) ? 'vertical_push' : 'horizontal_push';
        }

        foreach (['squat', 'hinge', 'lunge', 'rotation', 'anti_rotation', 'locomotion'] as $direction) {
            if (in_array($direction, $movementPatterns, true)) {
                return $direction;
            }
        }
        if (in_array('stabilization', $movementPatterns, true)) {
            return 'static_hold';
        }

        return null;
    }

    private function inferStabilityDemand(array $payload, ?array $metadata, array $movementPatterns): ?string
    {
        $raw = $this->normalizedAllowedValue($payload['stability_demand'] ?? null, RoutineLibraryRules::STABILITY_DEMANDS, '');
        if ($raw !== '') {
            return $raw;
        }

        $title = strtolower($this->scalarString($metadata['title'] ?? null));
        if (preg_match('/\b(bench|machine|supported|chest supported|seated)\b/', $title)) {
            return 'supported';
        }
        if (preg_match('/\b(bosu|stability ball|unstable)\b/', $title)) {
            return 'unstable';
        }
        if (preg_match('/\b(sl|single leg|single-leg|one leg|split squat|side plank)\b/', $title)) {
            return 'single_leg';
        }
        if (in_array('anti_rotation', $movementPatterns, true)) {
            return 'anti_rotation';
        }
        if (in_array('stabilization', $movementPatterns, true)) {
            return 'unsupported';
        }

        return 'stable';
    }

    private function inferVariationType(array $payload, ?array $metadata): string
    {
        $raw = $this->normalizedAllowedValue($payload['variation_type'] ?? null, RoutineLibraryRules::VARIATION_TYPES, '');
        if ($raw !== '') {
            return $raw;
        }

        $title = strtolower($this->scalarString($metadata['title'] ?? null));
        if (preg_match('/\b(regression|modified|assisted|easy|beginner)\b/', $title)) {
            return 'regression';
        }
        if (preg_match('/\b(progression|advanced|hard|explosive|weighted)\b/', $title)) {
            return 'progression';
        }
        if (preg_match('/\b(single|one arm|one leg|unilateral)\b/', $title)) {
            return 'unilateral_variant';
        }
        if (preg_match('/\b(alternative|substitute|variation)\b/', $title)) {
            return 'alternative';
        }

        return 'base';
    }

    private function compatibilityFlags(array $payload, string $difficulty, string $primaryCategory, array $usageFlags, array $safetyFlags, string $impact): array
    {
        $provided = is_array($payload['compatibility_flags'] ?? null) ? $payload['compatibility_flags'] : [];
        $flags = [];
        foreach ($provided as $key => $value) {
            $normalizedKey = $this->normalizeKey($key);
            if ($normalizedKey !== '') {
                $flags[$normalizedKey] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
        }

        return array_merge($flags, [
            'beginner_compatible' => $difficulty === 'beginner',
            'intermediate_compatible' => in_array($difficulty, ['beginner', 'intermediate'], true),
            'advanced_compatible' => true,
            'warmup_compatible' => empty($safetyFlags['unsafe_as_warmup']) && ! empty($safetyFlags['safe_for_warmup']),
            'cooldown_compatible' => ! empty($safetyFlags['safe_for_cooldown']),
            'main_workout_compatible' => ! empty($usageFlags['main_workout'])
                || in_array($primaryCategory, ['resistance_training', 'power_explosive_training', 'circuit_training', 'hiit_cardio'], true),
            'low_impact_compatible' => $impact !== 'high',
        ]);
    }

    private function confidenceBucket(array $payload): string
    {
        $rawBucket = $this->normalizedAllowedValue($payload['confidence_bucket'] ?? null, RoutineLibraryRules::CONFIDENCE_BUCKETS, '');
        if ($rawBucket !== '') {
            return $rawBucket;
        }

        if (! isset($payload['confidence']) || $payload['confidence'] === '') {
            return 'medium';
        }

        $confidence = max(0, min(1, (float) $payload['confidence']));
        if ($confidence >= 0.8) {
            return 'high';
        }
        if ($confidence >= 0.55) {
            return 'medium';
        }

        return 'low';
    }

    private function reviewBlockers(array $payload, string $confidenceBucket, string $primaryCategory, array $usageFlags, array $safetyFlags, array $controlledArrays): array
    {
        $blockers = [];
        if ($confidenceBucket === 'low') {
            $blockers[] = 'Low AI confidence; manual review required before generation approval.';
        }

        foreach ($controlledArrays as $field => $allowed) {
            $dropped = $this->droppedControlledValues($payload[$field] ?? [], $allowed);
            if ($dropped !== []) {
                $blockers[] = "{$field} contained unsupported value(s): " . implode(', ', $dropped);
            }
        }

        if (! empty($safetyFlags['unsafe_as_warmup']) && (! empty($usageFlags['cardio_warm_up']) || ! empty($usageFlags['warm_up']))) {
            $blockers[] = 'Exercise is marked unsafe as warm-up but still has warm-up usage enabled.';
        }

        if (in_array($primaryCategory, ['flexibility_stretching', 'post_workout_stretching'], true) && empty($usageFlags['stretching'])) {
            $blockers[] = 'Stretching category must have stretching usage enabled.';
        }

        return array_values(array_unique($blockers));
    }

    private function allowedValue($value, array $allowed, string $fallback): string
    {
        $value = strtolower($this->scalarString($value));

        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function normalizedAllowedValue($value, array $allowed, string $fallback): string
    {
        $value = $this->normalizeControlledKey($this->scalarString($value));

        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function allowedStringArray($value, array $allowed): array
    {
        return array_values(array_unique(array_intersect(
            array_map(fn ($item) => $this->normalizeAllowedArrayValue($item, $allowed), $this->stringArray($value)),
            $allowed
        )));
    }

    private function droppedControlledValues($value, array $allowed): array
    {
        $raw = $this->stringArray($value);
        $normalized = array_map(fn ($item) => $this->normalizeAllowedArrayValue($item, $allowed), $raw);

        return array_values(array_unique(array_filter($normalized, fn ($item) => $item !== '' && ! in_array($item, $allowed, true))));
    }

    private function normalizeAllowedArrayValue($value, array $allowed): string
    {
        $key = $this->normalizeControlledKey($value);
        if (in_array($key, $allowed, true)) {
            return $key;
        }

        $taxonomyValue = RoutineLibraryRules::normalizeTaxonomyValue($key, $allowed, '');
        if ($taxonomyValue !== '') {
            return $taxonomyValue;
        }

        $aliases = [
            'warm_up' => 'dynamic_warm_up',
            'warmup' => 'dynamic_warm_up',
            'dynamic_warmup' => 'dynamic_warm_up',
            'mobility_warm_up' => 'mobility_dynamic_warm_up',
            'post_workout' => 'post_workout_stretching',
            'post_workout_stretch' => 'post_workout_stretching',
            'post_workout_stretches' => 'post_workout_stretching',
            'cooldown_stretching' => 'cool_down_stretching',
            'cool_down_stretch' => 'cool_down_stretching',
            'main' => 'main_workout',
            'main_laying' => 'main_workout',
            'main_strength_weps_and_weights' => 'main_workout',
            'main_strength_reps_and_weights' => 'main_workout',
            'lower_back_superset' => 'lower_back_core_superset',
            'lower_back_core_preparation' => 'lower_back_core_superset',
            'lower_back_and_core_preparation' => 'lower_back_core_superset',
            'core_lower_back_superset' => 'lower_back_core_superset',
            'abs' => 'core_obliques',
            'obliques' => 'core_obliques',
            'optional_cardio' => 'optional_additional_cardio',
            'static_hold' => 'stabilization',
        ];
        $candidate = $aliases[$key] ?? $key;

        return in_array($candidate, $allowed, true) ? $candidate : $key;
    }

    private function integerArray($value): array
    {
        $items = [];
        foreach ($this->flattenScalarValues($value) as $item) {
            $id = (int) $item;
            if ($id > 0) {
                $items[] = $id;
            }
        }

        return array_values(array_unique($items));
    }

    private function nullablePositiveInteger($value): ?int
    {
        $value = $this->scalarString($value);
        if ($value === null || $value === '') {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    private function normalizeKey($value): string
    {
        $value = strtolower($this->scalarString($value));
        $value = str_replace(['&', '/', '-'], ' ', $value);
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? '';

        return trim($value, '_');
    }

    private function normalizeControlledKey($value): string
    {
        $key = $this->normalizeKey($value);
        $aliases = [
            'body_weight' => 'bodyweight',
            'dumbbell' => 'dumbbells',
            'db' => 'dumbbells',
            'machines' => 'machine',
            'gym_machine' => 'machine',
            'gym_machines' => 'machine',
            'cables' => 'cable',
            'barbells' => 'barbell',
            'cardio_machine' => 'cardio_machine',
            'cardio_machines' => 'cardio_machine',
            'band' => 'bands',
            'resistance_band' => 'bands',
            'resistance_bands' => 'bands',
            'warmup' => 'warm_up',
            'dynamic_warmup' => 'dynamic_warm_up',
            'cooldown' => 'cool_down',
            'post_workout_stretch' => 'post_workout_stretching',
        ];

        return $aliases[$key] ?? $key;
    }

    private function familyKey(string $value): string
    {
        return substr($this->normalizeKey($value), 0, 128);
    }

    private function scalarString($value, string $fallback = ''): string
    {
        if (is_array($value)) {
            foreach ($this->flattenScalarValues($value) as $item) {
                return trim((string) $item);
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
        $items = [];
        foreach ($this->flattenScalarValues($value) as $item) {
            $string = strtolower(trim((string) $item));
            if ($string !== '') {
                $items[] = $string;
            }
        }

        return array_values(array_unique($items));
    }

    private function flattenScalarValues($value): array
    {
        if (! is_array($value)) {
            return is_scalar($value) && trim((string) $value) !== '' ? [$value] : [];
        }

        $items = [];
        foreach ($value as $item) {
            foreach ($this->flattenScalarValues($item) as $nested) {
                $items[] = $nested;
            }
        }

        return $items;
    }

    private function nullableInteger($value, int $min, int $max): ?int
    {
        $value = $this->scalarString($value);
        if ($value === null || $value === '') {
            return null;
        }

        return max($min, min($max, (int) $value));
    }

    private function normalizedConfidence($value): ?float
    {
        $value = $this->scalarString($value);
        if ($value === null || $value === '') {
            return null;
        }

        return max(0, min(1, (float) $value));
    }

    private function confidenceFromBucket($value): ?float
    {
        $bucket = $this->normalizedAllowedValue($value, RoutineLibraryRules::CONFIDENCE_BUCKETS, '');

        return [
            'high' => 0.85,
            'medium' => 0.7,
            'low' => 0.4,
        ][$bucket] ?? null;
    }

    private function nullableString($value, int $max): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr($this->scalarString($value), 0, $max);
    }
}
