<?php

namespace App\Console\Commands;

use App\Models\Program;
use App\Services\WorkoutProgramCodeAllocator;
use Illuminate\Console\Command;

class AssignProgramContentCodesCommand extends Command
{
    protected $signature = 'programs:assign-content-codes
                            {--dry-run : Show planned codes without saving}
                            {--force : Overwrite existing content_code (use with care)}';

    protected $description = 'Assign program content_code values using programs block in config/content_code_scheme.php (PR-###).';

    public function handle(WorkoutProgramCodeAllocator $allocator): int
    {
        $dry = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $query = Program::query()->orderBy('id');
        if (! $force) {
            $query->where(function ($q) {
                $q->whereNull('content_code')->orWhere('content_code', '');
            });
        }

        $rows = $query->get();
        if ($rows->isEmpty()) {
            $this->info('No programs to process.');

            return 0;
        }

        $reserved = [];
        $ok = 0;
        $failed = 0;

        foreach ($rows as $prog) {
            $extra = $reserved['programs'] ?? [];
            $code = $allocator->allocateNext('programs', Program::class, 'content_code', $extra);
            if ($code === null) {
                $this->error("Program {$prog->id}: could not allocate code (check config programs range)");
                $failed++;

                continue;
            }
            if (preg_match('/-(\d+)$/', $code, $m)) {
                $reserved['programs'][] = (int) $m[1];
            }

            $collision = Program::query()->where('content_code', $code)->where('id', '!=', $prog->id)->exists();
            if ($collision) {
                $this->error("Program {$prog->id}: code {$code} already used by another row");
                $failed++;

                continue;
            }

            $this->line(($dry ? '[dry-run] ' : '')."Program {$prog->id} → {$code}");
            if (! $dry) {
                $prog->content_code = $code;
                $prog->save();
            }
            $ok++;
        }

        $this->newLine();
        $this->info(sprintf('Done. assigned: %d, failed: %d%s', $ok, $failed, $dry ? ' (dry-run)' : ''));

        return $failed > 0 ? 1 : 0;
    }
}
