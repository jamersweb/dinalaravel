<?php

namespace App\Console\Commands;

use App\Services\RoutineGeneratorService;
use Illuminate\Console\Command;

class GenerateRoutineBatchCommand extends Command
{
    protected $signature = 'routine-library:generate
        {--language=en : en, ar, or no_audio}
        {--equipment=bodyweight : full_gym, gym, home_dumbbell, or bodyweight}
        {--level=beginner : beginner, intermediate, or advanced}
        {--minutes=30 : Target workout duration: 15, 20, 30, 45, or 60}
        {--type=* : Optional routine type codes}
        {--variations=1 : Variations per type, max 15}
        {--limit=10 : Maximum routines to create}';

    protected $description = 'Generate a draft routine batch from approved tagged exercises.';

    public function handle(RoutineGeneratorService $generator): int
    {
        $batch = $generator->generateBatch([
            'language' => $this->option('language'),
            'equipment_category' => $this->option('equipment'),
            'fitness_level' => $this->option('level'),
            'target_minutes' => (int) $this->option('minutes'),
            'workout_types' => $this->option('type') ?: null,
            'variations_per_type' => (int) $this->option('variations'),
            'limit' => (int) $this->option('limit'),
        ]);

        $this->line(json_encode($batch->fresh()->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $batch->status === 'blocked_missing_content' ? self::FAILURE : self::SUCCESS;
    }
}
