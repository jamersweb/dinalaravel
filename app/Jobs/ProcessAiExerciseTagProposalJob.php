<?php

namespace App\Jobs;

use App\Models\AiExerciseTagProposal;
use App\Services\OllamaExerciseTaggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAiExerciseTagProposalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(protected int $proposalId)
    {
    }

    public function handle(OllamaExerciseTaggerService $service): void
    {
        $proposal = AiExerciseTagProposal::find($this->proposalId);
        if (! $proposal) {
            return;
        }

        $service->processQueuedProposal($proposal);
    }
}
