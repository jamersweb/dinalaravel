<?php

namespace App\Console\Commands;

use App\Services\RoutineExerciseAutoTaggerService;
use Illuminate\Console\Command;

class AutoTagRoutineExercisesCommand extends Command
{
    protected $signature = 'routine-library:auto-tag-exercises
        {--approve : Mark auto-tagged exercises approved for generation}
        {--replace : Reclassify exercises that already have routine-library tags}
        {--include-no-audio : Include language=no exercises}
        {--dry-run : Report changes without writing}';

    protected $description = 'Map existing CMS exercise tags into routine-library audit tags.';

    public function handle(RoutineExerciseAutoTaggerService $tagger): int
    {
        $summary = $tagger->tag([
            'approve' => (bool) $this->option('approve'),
            'replace' => (bool) $this->option('replace'),
            'include_no_audio' => (bool) $this->option('include-no-audio'),
            'dry_run' => (bool) $this->option('dry-run'),
        ]);

        $this->line(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if (! $this->option('dry-run')) {
            $this->line(json_encode($tagger->report(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        return self::SUCCESS;
    }
}
