<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('meals') || ! Schema::hasColumn('meals', 'locale_translations')) {
            return;
        }

        $meals = DB::table('meals')
            ->where('meal_by', 'admin')
            ->whereIn('language', ['en', 'ar'])
            ->orderBy('id')
            ->get();

        $groups = [];
        foreach ($meals as $meal) {
            $groups[$this->signature($meal)][] = $meal;
        }

        foreach ($groups as $rows) {
            $english = array_values(array_filter($rows, fn ($row) => $row->language === 'en'));
            $arabic = array_values(array_filter($rows, fn ($row) => $row->language === 'ar'));

            if (count($english) !== 1 || count($arabic) !== 1) {
                continue;
            }

            $baseMeal = $english[0];
            $translatedMeal = $arabic[0];

            $translationMap = $this->decodeTranslations($baseMeal->locale_translations);
            $translationMap['ar'] = array_filter([
                'name' => $translatedMeal->name,
                'ingredients' => $translatedMeal->ingredients,
                'directions' => $translatedMeal->directions,
            ], fn ($value) => is_string($value) && $value !== '');

            DB::table('meals')
                ->where('id', $baseMeal->id)
                ->update([
                    'locale_translations' => json_encode($translationMap, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);

            $this->repointMealReferences((int) $translatedMeal->id, (int) $baseMeal->id);

            DB::table('meals')->where('id', $translatedMeal->id)->delete();
        }
    }

    public function down(): void
    {
        // This merge is intentionally irreversible.
    }

    private function signature(object $meal): string
    {
        return implode('|', [
            $meal->user_id ?? '',
            $meal->meal_by ?? '',
            $meal->file ?? '',
            $meal->file_type ?? '',
            $meal->video_thumbnail ?? '',
            $meal->prep_time ?? '',
            $meal->cook_time ?? '',
            $meal->suitable_for ?? '',
            $meal->tags ?? '',
            $meal->contains ?? '',
            $meal->no_of_servings ?? '',
            $meal->calories_per_serving ?? '',
            $meal->protein_per_serving ?? '',
            $meal->carbs_per_serving ?? '',
            $meal->fat_per_serving ?? '',
            $meal->fiber_per_serving ?? '',
            $meal->nutrient ?? '',
            $meal->meal_type ?? '',
        ]);
    }

    private function decodeTranslations($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function repointMealReferences(int $fromMealId, int $toMealId): void
    {
        if ($fromMealId === $toMealId) {
            return;
        }

        if (Schema::hasTable('meal_days')) {
            foreach (['breakfast', 'lunch', 'dinner', 'snacks', 'drinks'] as $column) {
                if (Schema::hasColumn('meal_days', $column)) {
                    DB::table('meal_days')->where($column, $fromMealId)->update([$column => $toMealId]);
                }
            }
        }

        foreach ([
            'meal_comments' => 'meal_id',
            'nutrition_compilances' => 'meal_id',
            'ump_trackings' => 'meal_id',
        ] as $table => $column) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                DB::table($table)->where($column, $fromMealId)->update([$column => $toMealId]);
            }
        }

        if (Schema::hasTable('s_tasks') && Schema::hasColumn('s_tasks', 'target') && Schema::hasColumn('s_tasks', 'type')) {
            DB::table('s_tasks')
                ->where('type', 'meal')
                ->where('target', $fromMealId)
                ->update(['target' => $toMealId]);
        }

        if (Schema::hasTable('comments') && Schema::hasColumn('comments', 'target_id') && Schema::hasColumn('comments', 'type')) {
            DB::table('comments')
                ->where('type', 'meal')
                ->where('target_id', $fromMealId)
                ->update(['target_id' => $toMealId]);
        }
    }
};
