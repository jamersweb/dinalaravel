<?php

namespace App\Console\Commands;

use App\Services\AiLaunchProgramBuilderService;
use App\Services\RoutineLibraryRules;
use Illuminate\Console\Command;

class BuildAiLaunchMatrixCommand extends Command
{
    protected $signature = 'ai-programs:build-launch-matrix
        {--language=* : Language(s) to build. Defaults to en and ar. Use multiple times for more.}
        {--weeks=12 : Program length, 1-16 weeks}
        {--replace : Replace existing AI launch programs with matching content codes}';

    protected $description = 'Build all launch matrix programs from approved generated routines.';

    public function handle(AiLaunchProgramBuilderService $builder): int
    {
        $languages = $this->option('language') ?: ['en', 'ar'];
        $languages = collect($languages)
            ->map(fn ($language) => RoutineLibraryRules::normalizeLanguage((string) $language))
            ->unique()
            ->values()
            ->all();

        $results = $builder->buildLaunchMatrix(
            $languages,
            (int) $this->option('weeks'),
            (bool) $this->option('replace')
        );

        $this->line(json_encode([
            'requested_languages' => $languages,
            'weeks' => max(1, min(16, (int) $this->option('weeks'))),
            'replace' => (bool) $this->option('replace'),
            'summary' => [
                'created' => collect($results)->where('status', 'created')->count(),
                'existing' => collect($results)->where('status', 'existing')->count(),
                'blocked' => collect($results)->where('status', 'blocked')->count(),
                'invalid' => collect($results)
                    ->filter(fn ($result) => array_key_exists('valid', $result) && $result['valid'] === false)
                    ->count(),
            ],
            'results' => $results,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $blocked = collect($results)->where('status', 'blocked')->isNotEmpty();
        $invalid = collect($results)
            ->filter(fn ($result) => array_key_exists('valid', $result) && $result['valid'] === false)
            ->isNotEmpty();

        return $blocked || $invalid ? self::FAILURE : self::SUCCESS;
    }
}
