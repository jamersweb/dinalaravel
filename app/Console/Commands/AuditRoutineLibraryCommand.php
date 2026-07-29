<?php

namespace App\Console\Commands;

use App\Services\RoutineContentAuditService;
use Illuminate\Console\Command;

class AuditRoutineLibraryCommand extends Command
{
    protected $signature = 'routine-library:audit
        {--language= : en, ar, or no_audio}
        {--equipment= : full_gym, gym, home_dumbbell, or bodyweight}';

    protected $description = 'Audit approved exercise coverage before routine generation.';

    public function handle(RoutineContentAuditService $auditService): int
    {
        $filters = array_filter([
            'language' => $this->option('language'),
            'equipment_category' => $this->option('equipment'),
        ]);

        $audit = $auditService->audit($filters);
        $this->line(json_encode($audit, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $audit['status'] === 'ready' ? self::SUCCESS : self::FAILURE;
    }
}
