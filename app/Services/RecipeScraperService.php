<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RecipeScraperService
{
    private Client $client;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?: new Client([
            'timeout' => 20,
            'connect_timeout' => 10,
            'allow_redirects' => true,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ],
        ]);
    }

    public function scrape(string $url): array
    {
        $url = trim($url);
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->failed($url, 'Invalid URL.');
        }

        try {
            $response = $this->client->get($url);
            $html = (string) $response->getBody();
        } catch (\Throwable $e) {
            return $this->failed($url, 'Unable to fetch URL: '.$e->getMessage());
        }

        $recipe = $this->findRecipeJsonLd($html);
        if (! $recipe) {
            return $this->failed($url, 'No schema.org Recipe JSON-LD found.');
        }

        $title = $this->stringValue($recipe['name'] ?? null) ?: $this->metaContent($html, 'og:title');
        if (! $title) {
            return $this->failed($url, 'Recipe title was not found.');
        }

        $nutrition = is_array($recipe['nutrition'] ?? null) ? $recipe['nutrition'] : [];
        $ingredients = $this->stringList($recipe['recipeIngredient'] ?? []);
        $directions = $this->directions($recipe['recipeInstructions'] ?? []);

        return [
            'status' => true,
            'url' => $url,
            'source_host' => parse_url($url, PHP_URL_HOST),
            'name' => $title,
            'description' => $this->stringValue($recipe['description'] ?? null),
            'image_url' => $this->imageUrl($recipe['image'] ?? null, $html),
            'prep_time' => $this->minutes($recipe['prepTime'] ?? null),
            'cook_time' => $this->minutes($recipe['cookTime'] ?? null),
            'total_time' => $this->minutes($recipe['totalTime'] ?? null),
            'no_of_servings' => $this->servings($recipe['recipeYield'] ?? null),
            'calories_per_serving' => $this->nutritionNumber($nutrition, 'calories'),
            'protein_per_serving' => $this->nutritionNumber($nutrition, 'proteinContent'),
            'carbs_per_serving' => $this->nutritionNumber($nutrition, 'carbohydrateContent'),
            'fat_per_serving' => $this->nutritionNumber($nutrition, 'fatContent'),
            'fiber_per_serving' => $this->nutritionNumber($nutrition, 'fiberContent'),
            'ingredients' => $ingredients,
            'directions' => $directions,
            'tag_suggestions' => $this->tagSuggestions($recipe),
            'raw_nutrition' => $nutrition,
            'warnings' => $this->warnings($ingredients, $directions),
        ];
    }

    public function downloadImage(string $url, int $mealId): ?string
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        try {
            $response = $this->client->get($url, [
                'headers' => [
                    'Accept' => 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                    'Referer' => parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST).'/',
                ],
            ]);
            $contentType = strtolower((string) $response->getHeaderLine('Content-Type'));
            if (! str_contains($contentType, 'image/')) {
                return null;
            }

            $extension = match (true) {
                str_contains($contentType, 'png') => 'png',
                str_contains($contentType, 'webp') => 'webp',
                default => 'jpg',
            };
            $filename = $mealId.'_meal_scraped_'.time().'_'.Str::random(8).'.'.$extension;
            Storage::disk('fwd_media')->put('meals/'.$filename, (string) $response->getBody());

            return $filename;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function failed(string $url, string $message): array
    {
        return [
            'status' => false,
            'url' => $url,
            'message' => $message,
        ];
    }

    private function findRecipeJsonLd(string $html): ?array
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $scripts = $xpath->query('//script[@type="application/ld+json"]');

        foreach ($scripts as $script) {
            $decoded = json_decode(trim($script->textContent), true);
            if (! is_array($decoded)) {
                continue;
            }

            $recipe = $this->searchRecipeNode($decoded);
            if ($recipe) {
                return $recipe;
            }
        }

        return null;
    }

    private function searchRecipeNode(array $node): ?array
    {
        if ($this->isRecipeNode($node)) {
            return $node;
        }

        foreach (['@graph', 'itemListElement'] as $key) {
            if (isset($node[$key]) && is_array($node[$key])) {
                foreach ($node[$key] as $child) {
                    if (is_array($child)) {
                        $recipe = $this->searchRecipeNode($child);
                        if ($recipe) {
                            return $recipe;
                        }
                    }
                }
            }
        }

        foreach ($node as $child) {
            if (is_array($child)) {
                $recipe = $this->searchRecipeNode($child);
                if ($recipe) {
                    return $recipe;
                }
            }
        }

        return null;
    }

    private function isRecipeNode(array $node): bool
    {
        $type = $node['@type'] ?? null;
        if (is_string($type)) {
            return strtolower($type) === 'recipe';
        }
        if (is_array($type)) {
            return in_array('recipe', array_map('strtolower', $type), true);
        }

        return false;
    }

    private function stringValue($value): ?string
    {
        if (is_string($value)) {
            return trim(html_entity_decode($value));
        }
        if (is_array($value)) {
            return $this->stringValue(Arr::first($value));
        }

        return null;
    }

    private function stringList($value): array
    {
        if (is_string($value)) {
            return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $value))));
        }
        if (! is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $item) {
            $text = $this->stringValue($item);
            if ($text) {
                $items[] = $text;
            }
        }

        return $items;
    }

    private function directions($value): array
    {
        if (is_string($value)) {
            return $this->stringList($value);
        }
        if (! is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $step) {
            if (is_string($step)) {
                $items[] = trim($step);
                continue;
            }
            if (! is_array($step)) {
                continue;
            }
            if (($step['@type'] ?? null) === 'HowToSection' && is_array($step['itemListElement'] ?? null)) {
                foreach ($this->directions($step['itemListElement']) as $sectionStep) {
                    $items[] = $sectionStep;
                }
                continue;
            }
            $text = $this->stringValue($step['text'] ?? $step['name'] ?? null);
            if ($text) {
                $items[] = $text;
            }
        }

        return $items;
    }

    private function imageUrl($value, string $html): ?string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_array($value)) {
            if (isset($value['url'])) {
                return $this->stringValue($value['url']);
            }
            $first = Arr::first($value);
            return $this->imageUrl($first, $html);
        }

        return $this->metaContent($html, 'og:image');
    }

    private function metaContent(string $html, string $property): ?string
    {
        if (! preg_match('/<meta[^>]+(?:property|name)=["\']'.preg_quote($property, '/').'["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)) {
            return null;
        }

        return html_entity_decode($matches[1]);
    }

    private function minutes($value): ?int
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        if (preg_match('/P(?:T)?(?:(\d+)H)?(?:(\d+)M)?/i', $value, $matches)) {
            $hours = isset($matches[1]) && $matches[1] !== '' ? (int) $matches[1] : 0;
            $minutes = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : 0;
            $total = ($hours * 60) + $minutes;
            return $total > 0 ? $total : null;
        }

        if (preg_match('/(\d+)/', $value, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function servings($value): int
    {
        $text = $this->stringValue($value);
        if ($text && preg_match('/\d+/', $text, $matches)) {
            return max(1, (int) $matches[0]);
        }

        return 1;
    }

    private function nutritionNumber(array $nutrition, string $key): int
    {
        $value = $nutrition[$key] ?? null;
        if (! is_string($value) && ! is_numeric($value)) {
            return 0;
        }
        preg_match('/[\d.]+/', (string) $value, $matches);

        return isset($matches[0]) ? (int) round((float) $matches[0]) : 0;
    }

    private function tagSuggestions(array $recipe): array
    {
        $values = [];
        foreach (['recipeCuisine', 'recipeCategory', 'keywords'] as $key) {
            $value = $recipe[$key] ?? null;
            if (is_string($value)) {
                $values = array_merge($values, preg_split('/,|;|\|/', $value));
            } elseif (is_array($value)) {
                $values = array_merge($values, $this->stringList($value));
            }
        }

        $values = array_map(fn ($item) => Str::title(trim($item)), $values);

        return array_values(array_unique(array_filter($values)));
    }

    private function warnings(array $ingredients, array $directions): array
    {
        $warnings = [];
        if ($ingredients === []) {
            $warnings[] = 'No ingredients found.';
        }
        if ($directions === []) {
            $warnings[] = 'No directions found.';
        }

        return $warnings;
    }
}
