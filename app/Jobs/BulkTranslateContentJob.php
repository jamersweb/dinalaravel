<?php

namespace App\Jobs;

use App\Services\ContentBulkTranslationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class BulkTranslateContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 7200;

    /**
     * @param  array<int, string>  $targetLocales
     * @param  array<int, string>  $scopes
     */
    public function __construct(
        protected string $sourceLocale,
        protected array $targetLocales,
        protected array $scopes
    ) {
        $this->sourceLocale = strtolower($this->sourceLocale);
        $this->targetLocales = array_values(array_unique(array_map('strtolower', $this->targetLocales)));
    }

    public function handle(ContentBulkTranslationService $service): void
    {
        $summary = [];

        foreach ($this->targetLocales as $target) {
            if ($target === $this->sourceLocale) {
                continue;
            }
            try {
                $counts = $service->translateForTarget($this->sourceLocale, $target, $this->scopes);
                $summary[$target] = $counts;
                Log::info('BulkTranslateContentJob: finished locale', [
                    'source' => $this->sourceLocale,
                    'target' => $target,
                    'meals_updated' => $counts['meals_updated'],
                    'exercises_updated' => $counts['exercises_updated'],
                    'programs_updated' => $counts['programs_updated'],
                    'workouts_updated' => $counts['workouts_updated'],
                ]);
            } catch (Throwable $e) {
                Log::error('BulkTranslateContentJob: locale failed', [
                    'source' => $this->sourceLocale,
                    'target' => $target,
                    'error' => $e->getMessage(),
                ]);
                $summary[$target] = ['error' => $e->getMessage()];
            }
        }

        Log::info('BulkTranslateContentJob: complete', [
            'source' => $this->sourceLocale,
            'targets' => $this->targetLocales,
            'summary' => $summary,
        ]);
    }
}
