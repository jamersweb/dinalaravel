<?php

namespace App\Services;

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
        $model = (string) ($filters['model'] ?? config('services.ollama.model', 'qwen3:latest'));
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
            'failed' => 0,
            'proposal_ids' => [],
        ];

        foreach ($query->get() as $exercise) {
            $metadata = $this->sourceMetadata($exercise);
            $currentTagPayload = $exercise->libraryTag ? $this->tagPayload($exercise->libraryTag) : null;

            try {
                $result = $this->tagWithOllama($metadata, $currentTagPayload, $model);
                $proposal = AiExerciseTagProposal::create([
                    'exercise_id' => $exercise->id,
                    'provider' => 'ollama',
                    'model' => $model,
                    'status' => 'proposed',
                    'source_metadata' => $metadata,
                    'current_tag_payload' => $currentTagPayload,
                    'proposed_payload' => $result['payload'],
                    'confidence' => $result['confidence'],
                    'reasoning' => $result['reasoning'],
                    'raw_response' => $result['raw_response'],
                    'generated_by' => Auth::id(),
                    'generated_at' => now(),
                ]);
                $summary['created']++;
                $summary['proposal_ids'][] = $proposal->id;
            } catch (\Throwable $e) {
                $proposal = AiExerciseTagProposal::create([
                    'exercise_id' => $exercise->id,
                    'provider' => 'ollama',
                    'model' => $model,
                    'status' => 'failed',
                    'source_metadata' => $metadata,
                    'current_tag_payload' => $currentTagPayload,
                    'error_message' => $e->getMessage(),
                    'generated_by' => Auth::id(),
                    'generated_at' => now(),
                ]);
                $summary['failed']++;
                $summary['proposal_ids'][] = $proposal->id;
            }
        }

        return $summary;
    }

    public function applyProposal(AiExerciseTagProposal $proposal, bool $approve): ExerciseLibraryTag
    {
        if (! is_array($proposal->proposed_payload) || $proposal->status !== 'proposed') {
            throw new RuntimeException('Only proposed AI tags can be applied.');
        }

        $payload = $this->normalizePayload($proposal->proposed_payload);
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
        if (! in_array($proposal->status, ['proposed', 'failed'], true)) {
            throw new RuntimeException('Only open AI tag proposals can be rejected.');
        }

        $proposal->fill([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ])->save();
    }

    private function tagWithOllama(array $metadata, ?array $currentTagPayload, string $model): array
    {
        $baseUrl = rtrim((string) config('services.ollama.base_url', 'http://127.0.0.1:11434'), '/');
        $timeout = (int) config('services.ollama.timeout', 120);
        $response = Http::timeout($timeout)->post($baseUrl . '/api/generate', [
            'model' => $model,
            'stream' => false,
            'format' => 'json',
            'prompt' => $this->prompt($metadata, $currentTagPayload),
            'options' => [
                'temperature' => 0.1,
            ],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Ollama request failed: HTTP ' . $response->status());
        }

        $raw = (string) ($response->json('response') ?? '');
        $decoded = $this->decodeJsonResponse($raw);
        $payload = $this->normalizePayload($decoded['tag'] ?? $decoded);

        return [
            'payload' => $payload,
            'confidence' => isset($decoded['confidence']) ? max(0, min(1, (float) $decoded['confidence'])) : null,
            'reasoning' => isset($decoded['reasoning']) ? (string) $decoded['reasoning'] : null,
            'raw_response' => $raw,
        ];
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

        return 'You are tagging fitness exercise videos for a women fitness app. '
            . 'Use ONLY the allowed values from the schema. Return JSON only. '
            . 'Important safety rules: HIIT, jumps, high knees, burpees, sprinting, explosive drills are NOT warm-up cardio. '
            . 'Stretching must be real cooldown stretching, not warm-up mobility unless clearly a stretch. '
            . 'Gym main exercises should use gym/full_gym equipment; dumbbell routines use dumbbells; bodyweight uses no equipment. '
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

    private function normalizePayload(array $payload): array
    {
        $language = RoutineLibraryRules::normalizeLanguage($payload['language'] ?? 'en');
        $equipment = RoutineLibraryRules::normalizeEquipment($payload['equipment_category'] ?? 'bodyweight');
        $difficulty = RoutineLibraryRules::normalizeLevel($payload['difficulty'] ?? 'beginner');
        $impact = $this->allowedValue($payload['impact_level'] ?? 'low', RoutineLibraryRules::IMPACT_LEVELS, 'low');
        $intensity = $this->allowedValue($payload['intensity_level'] ?? 'moderate', RoutineLibraryRules::INTENSITY_LEVELS, 'moderate');
        $videoVariant = $this->allowedValue($payload['video_variant'] ?? 'explained', RoutineLibraryRules::VIDEO_VARIANTS, 'explained');
        $usageFlags = $this->usageFlags((array) ($payload['usage_flags'] ?? []));

        return [
            'language' => $language,
            'equipment_category' => $equipment,
            'equipment_tags' => $this->stringArray($payload['equipment_tags'] ?? []),
            'muscle_group' => strtolower((string) ($payload['muscle_group'] ?? '')),
            'secondary_muscle_groups' => $this->stringArray($payload['secondary_muscle_groups'] ?? []),
            'exercise_type' => strtolower((string) ($payload['exercise_type'] ?? 'main_workout')),
            'movement_patterns' => array_values(array_intersect($this->stringArray($payload['movement_patterns'] ?? []), RoutineLibraryRules::MOVEMENT_PATTERNS)),
            'training_styles' => $this->stringArray($payload['training_styles'] ?? []),
            'workout_sections' => array_values(array_intersect($this->stringArray($payload['workout_sections'] ?? []), array_keys(RoutineLibraryRules::WORKOUT_SECTION_LABELS))),
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
            'video_duration' => $exercise->video_duration,
            'rest_period' => $exercise->rest_period,
        ];
    }

    private function tagPayload(ExerciseLibraryTag $tag): array
    {
        return collect($tag->toArray())
            ->only((new ExerciseLibraryTag())->getFillable())
            ->except(['exercise_id'])
            ->all();
    }

    private function usageFlags(array $flags): array
    {
        $normalized = [];
        foreach (array_keys(RoutineLibraryRules::REQUIRED_AUDIT_USAGE) as $usage) {
            $normalized[$usage] = filter_var($flags[$usage] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        return $normalized;
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
