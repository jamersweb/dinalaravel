<?php

namespace App\Console\Commands;

use App\Models\Exercise;
use App\Models\Tag;
use App\Services\ExerciseContentCodeAllocator;
use Illuminate\Console\Command;

class AssignExerciseCodesFromSchemeCommand extends Command
{
    protected $signature = 'exercises:assign-scheme-codes
                            {--dry-run : Show planned codes without saving}
                            {--force : Overwrite existing content_code (use with care)}';

    protected $description = 'Assign exercise content_code values using config/content_code_scheme.php (Dina bands: EX/CO/FU/… + tag/type mapping).';

    public function handle(ExerciseContentCodeAllocator $allocator): int
    {
        $dry = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $query = Exercise::query()->orderBy('id');
        if (! $force) {
            $query->where(function ($q) {
                $q->whereNull('content_code')->orWhere('content_code', '');
            });
        }

        $rows = $query->get();
        if ($rows->isEmpty()) {
            $this->info('No exercises to process.');

            return 0;
        }

        /** @var array<string, list<int>> $reserved Numbers already taken this run per segment */
        $reserved = [];
        $ok = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($rows as $ex) {
            $segment = $this->resolveSegment($ex);
            if ($segment === null) {
                $this->warn("Exercise {$ex->id} ({$ex->title}): no segment — add a matching tag in tag_to_segment or type_to_segment in config/content_code_scheme.php");
                $skipped++;

                continue;
            }

            $extra = $reserved[$segment] ?? [];
            $code = $allocator->allocateNext($segment, $extra);
            if ($code === null) {
                $this->error("Exercise {$ex->id}: range full for segment [{$segment}]");
                $failed++;

                continue;
            }

            if (preg_match('/-(\d+)$/', $code, $m)) {
                $reserved[$segment][] = (int) $m[1];
            }

            $collision = Exercise::query()->where('content_code', $code)->where('id', '!=', $ex->id)->exists();
            if ($collision) {
                $this->error("Exercise {$ex->id}: code {$code} already used by another row");
                $failed++;

                continue;
            }

            $this->line(($dry ? '[dry-run] ' : '')."Exercise {$ex->id} → {$code} (segment: {$segment})");
            if (! $dry) {
                $ex->content_code = $code;
                $ex->save();
            }
            $ok++;
        }

        $this->newLine();
        $this->info(sprintf(
            'Done. assigned: %d, skipped (no segment): %d, failed: %d%s',
            $ok,
            $skipped,
            $failed,
            $dry ? ' (dry-run)' : ''
        ));

        return $failed > 0 ? 1 : 0;
    }

    private function resolveSegment(Exercise $ex): ?string
    {
        $scheme = config('content_code_scheme', []);
        $tagMap = $scheme['tag_to_segment'] ?? [];

        $ids = $ex->tags;
        if (is_string($ids)) {
            $decoded = json_decode($ids, true);
            $ids = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($ids)) {
            $ids = [];
        }

        $names = Tag::query()
            ->where('category', 'exercise')
            ->whereIn('id', $ids)
            ->pluck('name')
            ->map(fn ($n) => strtolower(trim((string) $n)))
            ->all();

        foreach ($names as $name) {
            if ($name !== '' && isset($tagMap[$name])) {
                return $tagMap[$name];
            }
        }

        $typeMap = $scheme['type_to_segment'] ?? [];
        $type = (string) $ex->type;
        if ($type !== '' && array_key_exists($type, $typeMap)) {
            $seg = $typeMap[$type];
            if ($seg !== null && $seg !== '') {
                return $seg;
            }
        }

        return null;
    }
}
