<?php

namespace App\Console\Commands;

use App\Services\ConsultationRecommendationService;
use Illuminate\Console\Command;

class RecommendConsultationCommand extends Command
{
    protected $signature = 'consultation:recommend {user_id : User ID to recommend for}';

    protected $description = 'Calculate calories and approved routine recommendations from consultation answers.';

    public function handle(ConsultationRecommendationService $service): int
    {
        $recommendation = $service->recommendForUser((int) $this->argument('user_id'));
        $this->line(json_encode($recommendation->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
