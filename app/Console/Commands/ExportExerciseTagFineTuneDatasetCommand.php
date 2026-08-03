<?php

namespace App\Console\Commands;

use App\Models\ExerciseLibraryTag;
use App\Services\RoutineLibraryRules;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ExportExerciseTagFineTuneDatasetCommand extends Command
{
    protected $signature = 'ai-tags:export-finetune-dataset
        {--output=storage/app/ai-training/exercise-tags : Output path prefix; split suffixes are added automatically}
        {--language=* : Limit to language(s), for example --language=en --language=ar}
        {--validation=10 : Validation split percentage}
        {--test=10 : Test split percentage}
        {--limit= : Maximum accepted examples to export}
        {--include-medium : Include approved medium-confidence rows}
        {--include-low : Include approved low-confidence rows}
        {--include-image-url : Include resolved thumbnail URL in the training input}
        {--dry-run : Count and validate rows without writing files}';

    protected $description = 'Export human-approved exercise library tags as JSONL chat fine-tuning examples.';

    private const JSON_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    private const TAG_FIELDS = [
        'language',
        'equipment_category',
        'equipment_tags',
        'primary_category',
        'secondary_categories',
        'training_adaptation',
        'program_role',
        'muscle_group',
        'secondary_muscle_groups',
        'body_regions',
        'exercise_type',
        'exercise_family',
        'movement_direction',
        'stability_demand',
        'variation_type',
        'movement_patterns',
        'training_styles',
        'workout_sections',
        'impact_level',
        'intensity_level',
        'video_variant',
        'recommended_duration_seconds',
        'recommended_repetitions',
        'recommended_sets',
        'recommended_rest_seconds',
        'safety_notes',
        'contraindications',
        'difficulty',
        'injury_cautions',
        'goal_fit',
        'compatibility_flags',
        'regression_exercise_id',
        'progression_exercise_id',
        'alternative_exercise_ids',
        'usage_flags',
        'safety_flags',
        'confidence_bucket',
    ];

    private const ARRAY_FIELDS = [
        'equipment_tags',
        'secondary_categories',
        'secondary_muscle_groups',
        'body_regions',
        'movement_patterns',
        'training_styles',
        'workout_sections',
        'safety_notes',
        'contraindications',
        'injury_cautions',
        'goal_fit',
        'alternative_exercise_ids',
    ];

    private const REQUIRED_FIELDS = [
        'language',
        'equipment_category',
        'primary_category',
        'training_adaptation',
        'program_role',
        'muscle_group',
        'exercise_type',
        'difficulty',
    ];

    public function handle(): int
    {
        $validationPercent = $this->boundedPercent((int) $this->option('validation'), 'validation');
        $testPercent = $this->boundedPercent((int) $this->option('test'), 'test');
        if (($validationPercent + $testPercent) >= 100) {
            $this->error('Validation plus test percentages must be less than 100.');

            return self::FAILURE;
        }

        $languages = $this->languages();
        if ($languages === null) {
            return self::FAILURE;
        }

        $missingColumns = $this->missingTagColumns();
        if ($missingColumns !== []) {
            $this->error('exercise_library_tags is missing prompt-strict columns: ' . implode(', ', $missingColumns));
            $this->line('Run php artisan migrate before exporting the fine-tuning dataset.');

            return self::FAILURE;
        }

        $limit = $this->option('limit') !== null
            ? max(1, (int) $this->option('limit'))
            : null;

        $paths = $this->outputPaths((string) $this->option('output'));
        $handles = [];
        if (! $this->option('dry-run')) {
            File::ensureDirectoryExists(dirname($paths['train']));
            foreach (['train', 'validation', 'test'] as $split) {
                $handles[$split] = fopen($paths[$split], 'wb');
                if ($handles[$split] === false) {
                    $this->error("Unable to open {$paths[$split]} for writing.");
                    $this->closeHandles($handles);

                    return self::FAILURE;
                }
            }
        }

        $counts = ['train' => 0, 'validation' => 0, 'test' => 0];
        $skipped = [];
        $accepted = 0;

        $query = ExerciseLibraryTag::query()
            ->with(['exercise' => function ($query) {
                $query->select([
                    'id',
                    'content_code',
                    'title',
                    'type',
                    'tags',
                    'language',
                    'video_type',
                    'video_duration',
                    'video_url',
                    'image',
                    'custom_thumbnail',
                    'exercise_type',
                    'rest_period',
                    'locale_translations',
                ]);
            }])
            ->where('approved_for_generation', true)
            ->where('review_status', 'approved')
            ->whereIn('language', $languages)
            ->orderBy('exercise_id');

        foreach ($query->cursor() as $tag) {
            if ($limit !== null && $accepted >= $limit) {
                break;
            }

            $skipReason = $this->skipReason($tag);
            if ($skipReason !== null) {
                $skipped[$skipReason] = ($skipped[$skipReason] ?? 0) + 1;
                continue;
            }

            $example = $this->example($tag);
            $split = $this->splitFor($tag, $validationPercent, $testPercent);
            $counts[$split]++;
            $accepted++;

            if (! $this->option('dry-run')) {
                fwrite($handles[$split], json_encode($example, self::JSON_FLAGS) . PHP_EOL);
            }
        }

        $this->closeHandles($handles);

        $manifest = [
            'generated_at' => now()->toIso8601String(),
            'format' => 'chat_jsonl',
            'source' => 'exercise_library_tags approved_for_generation=true review_status=approved',
            'languages' => $languages,
            'splits' => $counts,
            'accepted' => $accepted,
            'skipped' => $skipped,
            'options' => [
                'validation_percent' => $validationPercent,
                'test_percent' => $testPercent,
                'include_medium' => (bool) $this->option('include-medium'),
                'include_low' => (bool) $this->option('include-low'),
                'include_image_url' => (bool) $this->option('include-image-url'),
                'limit' => $limit,
            ],
        ];

        if (! $this->option('dry-run')) {
            File::put($paths['manifest'], json_encode($manifest, JSON_PRETTY_PRINT | self::JSON_FLAGS) . PHP_EOL);
        }

        $this->line(json_encode($manifest, JSON_PRETTY_PRINT | self::JSON_FLAGS));
        if (! $this->option('dry-run')) {
            $this->info('Wrote:');
            foreach ($paths as $path) {
                $this->line($path);
            }
        }

        return $accepted > 0 ? self::SUCCESS : self::FAILURE;
    }

    private function boundedPercent(int $value, string $name): int
    {
        if ($value < 0 || $value > 99) {
            $this->warn("{$name} split was outside 0-99 and has been clamped.");
        }

        return max(0, min(99, $value));
    }

    private function languages(): ?array
    {
        $languages = array_values(array_filter((array) $this->option('language')));
        if ($languages === []) {
            $languages = RoutineLibraryRules::CONTENT_LANGUAGES;
        }

        $invalid = array_values(array_diff($languages, RoutineLibraryRules::LANGUAGES));
        if ($invalid !== []) {
            $this->error('Unsupported language(s): ' . implode(', ', $invalid));

            return null;
        }

        return $languages;
    }

    private function outputPaths(string $prefix): array
    {
        $prefix = preg_replace('/\.jsonl$/', '', $prefix) ?: 'storage/app/ai-training/exercise-tags';
        $prefix = $this->absolutePath($prefix);

        return [
            'train' => "{$prefix}-train.jsonl",
            'validation' => "{$prefix}-validation.jsonl",
            'test' => "{$prefix}-test.jsonl",
            'manifest' => "{$prefix}-manifest.json",
        ];
    }

    private function missingTagColumns(): array
    {
        return array_values(array_filter(self::TAG_FIELDS, function ($column) {
            return ! Schema::hasColumn('exercise_library_tags', $column);
        }));
    }

    private function absolutePath(string $path): string
    {
        if (preg_match('/^[A-Za-z]:[\/\\\\]/', $path) || str_starts_with($path, '/') || str_starts_with($path, '\\')) {
            return $path;
        }

        return base_path($path);
    }

    private function skipReason(ExerciseLibraryTag $tag): ?string
    {
        if (! $tag->exercise) {
            return 'missing_exercise';
        }

        $blockers = is_array($tag->review_blockers) ? array_filter($tag->review_blockers) : [];
        if ($blockers !== []) {
            return 'review_blockers';
        }

        $bucket = $tag->confidence_bucket ?: 'high';
        if ($bucket === 'low' && ! $this->option('include-low')) {
            return 'low_confidence';
        }
        if ($bucket === 'medium' && ! $this->option('include-medium')) {
            return 'medium_confidence';
        }

        foreach (self::REQUIRED_FIELDS as $field) {
            if ($tag->{$field} === null || $tag->{$field} === '') {
                return "missing_{$field}";
            }
        }

        $taxonomyError = $this->taxonomyError($tag);
        if ($taxonomyError !== null) {
            return $taxonomyError;
        }

        if ($this->tagPayload($tag) === []) {
            return 'empty_tag_payload';
        }

        return null;
    }

    private function taxonomyError(ExerciseLibraryTag $tag): ?string
    {
        $scalarChecks = [
            'language' => RoutineLibraryRules::LANGUAGES,
            'equipment_category' => RoutineLibraryRules::EQUIPMENT_CATEGORIES,
            'primary_category' => RoutineLibraryRules::PRIMARY_CATEGORIES,
            'training_adaptation' => RoutineLibraryRules::TRAINING_ADAPTATIONS,
            'program_role' => RoutineLibraryRules::PROGRAM_ROLES,
            'exercise_type' => RoutineLibraryRules::EXERCISE_TYPES,
            'difficulty' => RoutineLibraryRules::LEVELS,
            'movement_direction' => RoutineLibraryRules::MOVEMENT_DIRECTIONS,
            'stability_demand' => RoutineLibraryRules::STABILITY_DEMANDS,
            'variation_type' => RoutineLibraryRules::VARIATION_TYPES,
            'impact_level' => RoutineLibraryRules::IMPACT_LEVELS,
            'intensity_level' => RoutineLibraryRules::INTENSITY_LEVELS,
            'video_variant' => RoutineLibraryRules::VIDEO_VARIANTS,
            'confidence_bucket' => RoutineLibraryRules::CONFIDENCE_BUCKETS,
        ];

        foreach ($scalarChecks as $field => $allowed) {
            $value = $tag->{$field};
            if ($value !== null && $value !== '' && ! in_array($value, $allowed, true)) {
                return "unsupported_{$field}";
            }
        }

        $arrayChecks = [
            'equipment_tags' => RoutineLibraryRules::EQUIPMENT_TAGS,
            'secondary_categories' => RoutineLibraryRules::PRIMARY_CATEGORIES,
            'body_regions' => RoutineLibraryRules::BODY_REGIONS,
            'movement_patterns' => RoutineLibraryRules::MOVEMENT_PATTERNS,
            'training_styles' => RoutineLibraryRules::TRAINING_STYLES,
            'workout_sections' => array_keys(RoutineLibraryRules::WORKOUT_SECTION_LABELS),
        ];

        foreach ($arrayChecks as $field => $allowed) {
            foreach ($this->arrayValue($tag->{$field}) as $value) {
                if (! in_array($value, $allowed, true)) {
                    return "unsupported_{$field}";
                }
            }
        }

        return null;
    }

    private function example(ExerciseLibraryTag $tag): array
    {
        return [
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->systemPrompt(),
                ],
                [
                    'role' => 'user',
                    'content' => "Classify this exercise for the Dina Fitness master exercise library.\n\nExercise metadata:\n"
                        . json_encode($this->exerciseMetadata($tag), JSON_PRETTY_PRINT | self::JSON_FLAGS)
                        . "\n\nReturn JSON only.",
                ],
                [
                    'role' => 'assistant',
                    'content' => json_encode([
                        'tag' => $this->tagPayload($tag),
                        'confidence' => $this->confidenceValue($tag->confidence_bucket),
                        'reasoning' => 'Human-approved exercise library tag.',
                    ], self::JSON_FLAGS),
                ],
            ],
        ];
    }

    private function systemPrompt(): string
    {
        return 'You are a fitness-library tagging expert for an automated exercise tagging and workout generation system. Return exactly one JSON object with keys tag, confidence, and reasoning. Use only approved taxonomy values. Do not invent categories, sections, equipment, movement directions, stability demands, variation types, usage flags, or safety flags. Keep warm-up, main workout, cardio, activation, lower-back/core preparation, and stretching roles separate.';
    }

    private function exerciseMetadata(ExerciseLibraryTag $tag): array
    {
        $exercise = $tag->exercise;
        $metadata = [
            'id' => $exercise->id,
            'content_code' => $exercise->content_code,
            'title' => $exercise->title,
            'type' => $exercise->type,
            'legacy_tags' => $exercise->tags,
            'language' => $exercise->language,
            'video_type' => $exercise->video_type,
            'video_duration_seconds' => $exercise->video_duration,
            'exercise_type_hint' => $exercise->exercise_type,
            'rest_period_seconds' => $exercise->rest_period,
            'thumbnail_available' => $exercise->getRawOriginal('custom_thumbnail') !== null
                || $exercise->getRawOriginal('image') !== null,
            'locale_translations' => $exercise->locale_translations,
        ];

        if ($this->option('include-image-url')) {
            $metadata['image_url'] = $exercise->image;
        }

        return $this->stripEmpty($metadata);
    }

    private function tagPayload(ExerciseLibraryTag $tag): array
    {
        $payload = [];
        foreach (self::TAG_FIELDS as $field) {
            $value = $tag->{$field};
            if (in_array($field, self::ARRAY_FIELDS, true)) {
                $value = $this->arrayValue($value);
            }
            if (in_array($field, ['compatibility_flags', 'usage_flags', 'safety_flags'], true)) {
                $value = is_array($value) ? $value : [];
            }
            if ($value !== null && $value !== '') {
                $payload[$field] = $value;
            }
        }

        return $payload;
    }

    private function confidenceValue(?string $bucket): float
    {
        return match ($bucket) {
            'medium' => 0.75,
            'low' => 0.45,
            default => 0.95,
        };
    }

    private function splitFor(ExerciseLibraryTag $tag, int $validationPercent, int $testPercent): string
    {
        $key = (string) ($tag->exercise->content_code ?: $tag->exercise_id);
        $bucket = hexdec(substr(md5($key), 0, 8)) % 100;
        if ($bucket < $testPercent) {
            return 'test';
        }
        if ($bucket < ($testPercent + $validationPercent)) {
            return 'validation';
        }

        return 'train';
    }

    private function arrayValue($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_array($value)) {
            return array_values(array_filter($value, fn ($item) => $item !== null && $item !== ''));
        }

        return [$value];
    }

    private function stripEmpty(array $values): array
    {
        return array_filter($values, function ($value) {
            return $value !== null && $value !== '' && $value !== [];
        });
    }

    private function closeHandles(array $handles): void
    {
        foreach ($handles as $handle) {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }
    }
}
