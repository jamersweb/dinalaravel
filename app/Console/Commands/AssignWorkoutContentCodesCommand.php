<?php

namespace App\Console\Commands;

use App\Models\Workout;
use App\Services\WorkoutProgramCodeAllocator;
use Illuminate\Console\Command;

class AssignWorkoutContentCodesCommand extends Command
{
    protected $signature = 'workouts:assign-content-codes
                            {--dry-run : Show planned codes without saving}
                            {--force : Overwrite existing content_code (use with care)}';

    protected $description = 'Assign workout content_code values using workout_routines in config/content_code_scheme.php (WR-###).';

    public function handle(WorkoutProgramCodeAllocator $allocator): int
    {
        $dry = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $query = Workout::query()->orderBy('id');
        if (! $force) {
            $query->where(function ($q) {
                $q->whereNull('content_code')->orWhere('content_code', '');
            });
        }

        $rows = $query->get();
        if ($rows->isEmpty()) {
            $this->info('No workouts to process.');

            return 0;
        }

        $reserved = [];
        $ok = 0;
        $failed = 0;

        foreach ($rows as $wrk) {
            $extra = $reserved['workout_routines'] ?? [];
            $code = $allocator->allocateNext('workout_routines', Workout::class, 'content_code', $extra);
            if ($code === null) {
                $this->error("Workout {$wrk->id}: could not allocate code (check config workout_routines range)");
                $failed++;

                continue;
            }
            if (preg_match('/-(\d+)$/', $code, $m)) {
                $reserved['workout_routines'][] = (int) $m[1];
            }

            $collision = Workout::query()->where('content_code', $code)->where('id', '!=', $wrk->id)->exists();
            if ($collision) {
                $this->error("Workout {$wrk->id}: code {$code} already used by another row");
                $failed++;

                continue;
            }

            $this->line(($dry ? '[dry-run] ' : '')."Workout {$wrk->id} → {$code}");
            if (! $dry) {
                $wrk->content_code = $code;
                $wrk->save();
            }
            $ok++;
        }

        $this->newLine();
        $this->info(sprintf('Done. assigned: %d, failed: %d%s', $ok, $failed, $dry ? ' (dry-run)' : ''));

        return $failed > 0 ? 1 : 0;
    }
}
