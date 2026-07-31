<?php

namespace App\Console\Commands;

use App\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MergeDuplicateTagsCommand extends Command
{
    protected $signature = 'tags:merge-duplicates {--apply : Update references and delete duplicate tag rows}';

    protected $description = 'Audit and optionally merge duplicate tags by normalized name, type, and category.';

    private array $tagColumns = [
        ['users', 'tags'],
        ['exercises', 'tags'],
        ['workouts', 'tags'],
        ['programs', 'tags'],
        ['meals', 'tags'],
        ['meal_days', 'tags'],
        ['meal_weeks', 'tags'],
        ['meal_plans', 'tags'],
        ['foods', 'tags'],
        ['general_meals', 'tags'],
    ];

    public function handle(): int
    {
        $groups = Tag::query()
            ->selectRaw('LOWER(TRIM(name)) as normalized_name')
            ->selectRaw('LOWER(TRIM(COALESCE(type, ""))) as normalized_type')
            ->selectRaw('LOWER(TRIM(COALESCE(category, ""))) as normalized_category')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('GROUP_CONCAT(id ORDER BY id) as ids')
            ->groupBy('normalized_name', 'normalized_type', 'normalized_category')
            ->having('total', '>', 1)
            ->orderByDesc('total')
            ->get();

        if ($groups->isEmpty()) {
            $this->info('No duplicate tags found.');
            return self::SUCCESS;
        }

        $this->warn('Duplicate tag groups found: '.$groups->count());
        foreach ($groups as $group) {
            $this->line(sprintf(
                '- %s / %s / %s => %s',
                $group->normalized_category ?: '-',
                $group->normalized_type ?: '-',
                $group->normalized_name ?: '-',
                $group->ids
            ));
        }

        if (! $this->option('apply')) {
            $this->comment('Dry run only. Re-run with --apply to merge duplicate IDs into the lowest ID.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($groups) {
            foreach ($groups as $group) {
                $ids = array_values(array_filter(array_map('intval', explode(',', (string) $group->ids))));
                $keeperId = array_shift($ids);
                if (! $keeperId || $ids === []) {
                    continue;
                }

                $this->replaceTagReferences($keeperId, $ids);
                Tag::whereIn('id', $ids)->delete();
                $this->info('Merged duplicate tag IDs '.implode(',', $ids).' into '.$keeperId.'.');
            }
        });

        return self::SUCCESS;
    }

    private function replaceTagReferences(int $keeperId, array $duplicateIds): void
    {
        if (Schema::hasTable('user_tags')) {
            DB::table('user_tags')->whereIn('tag_id', $duplicateIds)->orderBy('id')->chunkById(200, function ($rows) use ($keeperId) {
                foreach ($rows as $row) {
                    $exists = DB::table('user_tags')
                        ->where('user_id', $row->user_id)
                        ->where('tag_id', $keeperId)
                        ->exists();

                    if ($exists) {
                        DB::table('user_tags')->where('id', $row->id)->delete();
                    } else {
                        DB::table('user_tags')->where('id', $row->id)->update(['tag_id' => $keeperId]);
                    }
                }
            });
        }

        foreach ($this->tagColumns as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            DB::table($table)
                ->whereNotNull($column)
                ->select('id', $column)
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($table, $column, $keeperId, $duplicateIds) {
                    foreach ($rows as $row) {
                        $tagIds = $this->parseTagIds($row->{$column});
                        if ($tagIds === [] || count(array_intersect($tagIds, $duplicateIds)) === 0) {
                            continue;
                        }

                        $tagIds = array_values(array_unique(array_map(
                            fn (int $id) => in_array($id, $duplicateIds, true) ? $keeperId : $id,
                            $tagIds
                        )));

                        DB::table($table)->where('id', $row->id)->update([$column => json_encode($tagIds)]);
                    }
                });
        }
    }

    private function parseTagIds($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $decoded = is_string($value) ? json_decode($value, true) : $value;
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('intval', $decoded)));
        }

        return array_values(array_filter(array_map('intval', explode(',', trim((string) $value, '[] ')))));
    }
}
