<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meal;
use App\Models\Tag;
use App\Services\RecipeScraperService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RecipeImportController extends Controller
{
    private const MEAL_CACHE_VERSION_KEY = 'meals:cache:version';
    private const FALLBACK_MEAL_IMAGE = '/images/mealscard.png';

    public function __construct(private RecipeScraperService $scraper)
    {
    }

    public function preview(Request $request)
    {
        $validate = $this->validateRequest($request, false);
        if ($validate->fails()) {
            return $this->validationResponse($validate);
        }

        return response()->json([
            'status' => true,
            'data' => $this->scrapeUrls($this->urlsFromRequest($request)),
        ]);
    }

    public function import(Request $request)
    {
        $validate = $this->validateRequest($request, true);
        if ($validate->fails()) {
            return $this->validationResponse($validate);
        }

        $urls = $this->urlsFromRequest($request);
        $scraped = $this->scrapeUrls($urls);
        $imported = [];
        $failed = [];
        $skipped = [];

        foreach ($scraped as $item) {
            if (! ($item['status'] ?? false)) {
                $failed[] = $item;
                continue;
            }

            $existingMeal = Meal::where('meal_by', 'admin')->where('name', $item['name'])->first();
            if ($existingMeal) {
                $imageUpdated = $this->updateMealImageIfMissing($existingMeal, $item, $request);
                $skipped[] = [
                    'url' => $item['url'],
                    'name' => $item['name'],
                    'message' => $imageUpdated ? 'Meal already exists; missing image was updated.' : 'Meal already exists.',
                ];
                continue;
            }

            $meal = $this->createMealFromRecipe($item, $request);
            $imported[] = [
                'id' => $meal->id,
                'url' => $item['url'],
                'name' => $meal->name,
            ];
        }

        if ($imported !== []) {
            $this->bumpMealCacheVersion();
        }

        return response()->json([
            'status' => true,
            'message' => count($imported).' recipe(s) imported.',
            'data' => [
                'imported' => $imported,
                'skipped' => $skipped,
                'failed' => $failed,
            ],
        ]);
    }

    private function validateRequest(Request $request, bool $import)
    {
        $rules = [
            'urls' => 'required',
            'default_suitable_for' => 'required|array|min:1',
            'default_suitable_for.*' => 'in:breakfast,lunch,dinner,snacks,drinks',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:tags,id',
            'create_missing_tags' => 'nullable|boolean',
            'import_images' => 'nullable|boolean',
        ];

        if (! $import) {
            unset($rules['tag_ids.*']);
        }

        return Validator::make($request->all(), $rules);
    }

    private function validationResponse($validate)
    {
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->all()[0],
        ], 422);
    }

    private function urlsFromRequest(Request $request): array
    {
        $urls = $request->input('urls');
        if (is_string($urls)) {
            $urls = preg_split('/\r\n|\r|\n|,/', $urls);
        }

        if (! is_array($urls)) {
            return [];
        }

        $urls = array_map('trim', $urls);
        $urls = array_filter($urls);

        return array_values(array_unique($urls));
    }

    private function scrapeUrls(array $urls): array
    {
        return array_map(fn (string $url) => $this->scraper->scrape($url), $urls);
    }

    private function createMealFromRecipe(array $recipe, Request $request): Meal
    {
        $meal = new Meal();
        $meal->user_id = Auth::id();
        $meal->meal_type = 'manual';
        $meal->meal_by = 'admin';
        $meal->name = Str::limit($recipe['name'], 250, '');
        $meal->language = 'en';
        $meal->prep_time = $recipe['prep_time'] ?? null;
        $meal->cook_time = $this->cookTime($recipe);
        $meal->suitable_for = json_encode(array_values($request->input('default_suitable_for')));
        $meal->tags = json_encode($this->tagIds($recipe, $request));
        $meal->contains = implode(', ', array_slice($recipe['ingredients'] ?? [], 0, 8));
        $meal->file = url(self::FALLBACK_MEAL_IMAGE);
        $meal->file_type = 'image';
        $meal->video_thumbnail = null;
        $meal->serving_size = null;
        $meal->no_of_servings = $recipe['no_of_servings'] ?? 1;
        $meal->calories_per_serving = $recipe['calories_per_serving'] ?? 0;
        $meal->protein_per_serving = $recipe['protein_per_serving'] ?? 0;
        $meal->carbs_per_serving = $recipe['carbs_per_serving'] ?? 0;
        $meal->fat_per_serving = $recipe['fat_per_serving'] ?? 0;
        $meal->fiber_per_serving = $recipe['fiber_per_serving'] ?? 0;
        $meal->ingredients = json_encode($recipe['ingredients'] ?? []);
        $meal->directions = json_encode($recipe['directions'] ?? []);
        $meal->nutrient = json_encode([
            'source_url' => $recipe['url'],
            'source_host' => $recipe['source_host'] ?? null,
            'description' => $recipe['description'] ?? null,
            'raw_nutrition' => $recipe['raw_nutrition'] ?? [],
            'imported_by' => 'recipe_scraper',
        ]);
        $meal->locale_translations = null;
        $meal->save();

        if ($request->boolean('import_images') && $this->validImageUrl($recipe['image_url'] ?? null)) {
            $filename = $this->scraper->downloadImage($recipe['image_url'], $meal->id);
            if ($filename) {
                $meal->file = $filename;
                $meal->save();
            }
        }

        return $meal;
    }

    private function updateMealImageIfMissing(Meal $meal, array $recipe, Request $request): bool
    {
        if (! $this->mealImageMissing($meal)) {
            return false;
        }

        $meal->file = url(self::FALLBACK_MEAL_IMAGE);
        $meal->file_type = 'image';
        $meal->video_thumbnail = null;

        if ($request->boolean('import_images') && $this->validImageUrl($recipe['image_url'] ?? null)) {
            $filename = $this->scraper->downloadImage($recipe['image_url'], $meal->id);
            if ($filename) {
                $meal->file = $filename;
            }
        }

        $meal->save();
        $this->bumpMealCacheVersion();

        return true;
    }

    private function mealImageMissing(Meal $meal): bool
    {
        $file = $meal->getRawOriginal('file');
        if (empty($file)) {
            return true;
        }

        if (filter_var($file, FILTER_VALIDATE_URL)) {
            $fallbackPath = parse_url(url(self::FALLBACK_MEAL_IMAGE), PHP_URL_PATH);
            $filePath = parse_url($file, PHP_URL_PATH);

            return $filePath === $fallbackPath || ! str_contains($file, parse_url(url('/'), PHP_URL_HOST) ?: '');
        }

        return ! Storage::disk('fwd_media')->exists('meals/'.basename($file));
    }

    private function validImageUrl(?string $url): bool
    {
        return is_string($url)
            && filter_var($url, FILTER_VALIDATE_URL)
            && ! str_starts_with($url, 'blob:');
    }

    private function cookTime(array $recipe): ?int
    {
        if (! empty($recipe['cook_time'])) {
            return $recipe['cook_time'];
        }

        if (! empty($recipe['total_time']) && ! empty($recipe['prep_time'])) {
            return max(0, (int) $recipe['total_time'] - (int) $recipe['prep_time']);
        }

        return $recipe['total_time'] ?? null;
    }

    private function tagIds(array $recipe, Request $request): array
    {
        $ids = array_map('intval', $request->input('tag_ids', []));
        if (! $request->boolean('create_missing_tags')) {
            return array_values(array_unique($ids));
        }

        foreach ($recipe['tag_suggestions'] ?? [] as $name) {
            $name = trim($name);
            if ($name === '') {
                continue;
            }

            $tag = Tag::firstOrCreate(
                ['name' => $name, 'category' => 'meal'],
                ['type' => 'Imported']
            );
            $ids[] = (int) $tag->id;
        }

        return array_values(array_unique($ids));
    }

    private function bumpMealCacheVersion(): void
    {
        $current = (int) Cache::get(self::MEAL_CACHE_VERSION_KEY, 1);
        Cache::forever(self::MEAL_CACHE_VERSION_KEY, $current + 1);
    }
}
