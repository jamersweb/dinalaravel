<?php

namespace App\Console\Commands;

use App\Services\AiLaunchProgramBuilderService;
use Illuminate\Console\Command;

class BuildAiLaunchProgramCommand extends Command
{
    protected $signature = 'ai-programs:build-launch
        {number : Launch matrix program number}
        {--language=en : en, ar, or no_audio}
        {--weeks=12 : Program length, 1-16 weeks}
        {--replace : Replace an existing AI launch program with the same content code}';

    protected $description = 'Build a multi-week AI launch program from approved generated routines.';

    public function handle(AiLaunchProgramBuilderService $builder): int
    {
        try {
            $result = $builder->buildLaunchProgram(
                (int) $this->argument('number'),
                (string) $this->option('language'),
                (int) $this->option('weeks'),
                (bool) $this->option('replace')
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $program = $result['program'];
        $validation = $result['validation'];
        $this->line(json_encode([
            'program_id' => $program->id,
            'content_code' => $program->content_code,
            'title' => $program->title,
            'status' => $result['status'],
            'weeks' => $program->aiWeekDays->pluck('week_no')->unique()->count(),
            'days' => $program->aiWeekDays->count(),
            'validation' => $validation,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $validation['valid'] ? self::SUCCESS : self::FAILURE;
    }
}
