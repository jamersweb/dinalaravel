<?php

namespace App\Console\Commands;

use App\Services\RoutineExerciseAutoTaggerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class AutoTagRoutineExercisesCommand extends Command
{
    protected $signature = 'routine-library:auto-tag-exercises
        {--approve : Mark auto-tagged exercises approved for generation}
        {--replace : Reclassify exercises that already have routine-library tags}
        {--preserve-review-status : Keep existing approval and review status when replacing tags}
        {--only-missing-master-fields : Reclassify only existing tags missing the prompt-strict master taxonomy fields}
        {--include-no-audio : Include language=no exercises}
        {--dry-run : Report changes without writing}';

    protected $description = 'Map existing CMS exercise tags into routine-library audit tags.';

    public function handle(RoutineExerciseAutoTaggerService $tagger): int
    {
        if ($this->option('only-missing-master-fields')) {
            $missingColumns = array_values(array_filter([
                'primary_category',
                'training_adaptation',
                'program_role',
                'exercise_family',
                'movement_direction',
                'stability_demand',
                'variation_type',
            ], fn ($column) => ! Schema::hasColumn('exercise_library_tags', $column)));

            if ($missingColumns !== []) {
                $this->error('exercise_library_tags is missing prompt-strict columns: ' . implode(', ', $missingColumns));
                $this->line('Run php artisan migrate before backfilling master exercise tags.');

                return self::FAILURE;
            }
        }

        $summary = $tagger->tag([
            'approve' => (bool) $this->option('approve'),
            'replace' => (bool) $this->option('replace'),
            'preserve_review_status' => (bool) $this->option('preserve-review-status'),
            'only_missing_master_fields' => (bool) $this->option('only-missing-master-fields'),
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
