<?php

namespace App\Console\Commands;

use App\Models\Exercise;
use Illuminate\Console\Command;

class BackfillExerciseContentCodes extends Command
{
    protected $signature = 'exercises:backfill-content-codes
                            {--strategy=id : "id" sets PREFIX-id (unique). "seq" fills only empty codes with PREFIX-001, 002… in id order}
                            {--prefix=EX : Prefix before the number}
                            {--width=3 : Zero-pad width for seq strategy}
                            {--dry-run : Print changes without saving}
                            {--force : With strategy=id only: set PREFIX-id on every exercise (overwrites existing codes)}';

    protected $description = 'Bulk-assign exercise content_code values for rows that are missing them (or all rows with --force + id).';

    public function handle(): int
    {
        $strategy = strtolower(trim((string) $this->option('strategy')));
        $prefix = trim((string) $this->option('prefix'));
        $width = max(1, (int) $this->option('width'));
        $dry = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if ($prefix === '') {
            $this->error('prefix cannot be empty.');

            return 1;
        }

        if (! in_array($strategy, ['id', 'seq'], true)) {
            $this->error('strategy must be "id" or "seq".');

            return 1;
        }

        if ($force && $strategy !== 'id') {
            $this->error('--force is only supported with --strategy=id (overwrites every row with PREFIX-id).');

            return 1;
        }

        if ($strategy === 'id') {
            return $this->runIdStrategy($prefix, $dry, $force);
        }

        return $this->runSeqStrategy($prefix, $width, $dry);
    }

    private function runIdStrategy(string $prefix, bool $dry, bool $force): int
    {
        $query = Exercise::query()->orderBy('id');
        if (! $force) {
            $query->where(function ($q) {
                $q->whereNull('content_code')->orWhere('content_code', '');
            });
        }

        $rows = $query->get();
        if ($rows->isEmpty()) {
            $this->info('No exercises to update.');

            return 0;
        }

        foreach ($rows as $ex) {
            $code = $prefix.'-'.$ex->id;
            $taken = Exercise::query()
                ->where('content_code', $code)
                ->where('id', '!=', $ex->id)
                ->exists();
            if ($taken) {
                $this->warn("Skip exercise {$ex->id}: {$code} already used by another row.");

                continue;
            }
            $this->line(($dry ? '[dry-run] ' : '')."Exercise {$ex->id} → {$code}");
            if (! $dry) {
                $ex->content_code = $code;
                $ex->save();
            }
        }

        return 0;
    }

    private function runSeqStrategy(string $prefix, int $width, bool $dry): int
    {
        $rows = Exercise::query()
            ->where(function ($q) {
                $q->whereNull('content_code')->orWhere('content_code', '');
            })
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No exercises missing content_code.');

            return 0;
        }

        $pattern = '/^'.preg_quote($prefix, '/').'-(\d+)$/';
        $next = 1;
        $maxFromDb = Exercise::query()
            ->whereNotNull('content_code')
            ->pluck('content_code')
            ->filter()
            ->map(function ($c) use ($pattern) {
                if (preg_match($pattern, trim((string) $c), $m)) {
                    return (int) $m[1];
                }

                return null;
            })
            ->filter()
            ->max();
        if ($maxFromDb !== null) {
            $next = $maxFromDb + 1;
        }

        foreach ($rows as $ex) {
            $code = $prefix.'-'.str_pad((string) $next, $width, '0', STR_PAD_LEFT);
            while (
                Exercise::query()
                    ->where('content_code', $code)
                    ->where('id', '!=', $ex->id)
                    ->exists()
            ) {
                $next++;
                $code = $prefix.'-'.str_pad((string) $next, $width, '0', STR_PAD_LEFT);
            }
            $this->line(($dry ? '[dry-run] ' : '')."Exercise {$ex->id} → {$code}");
            if (! $dry) {
                $ex->content_code = $code;
                $ex->save();
            }
            $next++;
        }

        return 0;
    }
}
