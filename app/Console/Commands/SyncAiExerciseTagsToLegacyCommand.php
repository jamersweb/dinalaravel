<?php

namespace App\Console\Commands;

use App\Models\ExerciseLibraryTag;
use App\Services\OllamaExerciseTaggerService;
use Illuminate\Console\Command;

class SyncAiExerciseTagsToLegacyCommand extends Command
{
    protected $signature = 'ai-tags:sync-exercise-tags {--exercise_id= : Limit sync to one exercise ID}';

    protected $description = 'Copy accepted AI exercise library tags into the legacy exercises.tags field used by the CMS exercise screens.';

    public function handle(OllamaExerciseTaggerService $tagger): int
    {
        $query = ExerciseLibraryTag::query()
            ->when($this->option('exercise_id'), fn ($q, $id) => $q->where('exercise_id', (int) $id))
            ->orderBy('id');

        $synced = 0;
        $changed = 0;

        $query->chunkById(200, function ($libraryTags) use ($tagger, &$synced, &$changed) {
            foreach ($libraryTags as $libraryTag) {
                $added = $tagger->syncExerciseTagsFromPayload((int) $libraryTag->exercise_id, $libraryTag->toArray());
                $synced++;
                if ($added !== []) {
                    $changed++;
                }
            }
        });

        $this->info("Synced {$synced} AI library tag rows.");
        $this->info("Updated {$changed} exercises with missing CMS tag IDs.");

        return self::SUCCESS;
    }
}
