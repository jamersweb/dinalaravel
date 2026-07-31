<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->deleteMatchingPayloads('jobs');
        $this->deleteMatchingPayloads('failed_jobs');
    }

    public function down(): void
    {
        // Removed queued webhook jobs cannot be restored.
    }

    private function deleteMatchingPayloads(string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        DB::table($table)
            ->where('payload', 'like', '%ProcessRevenueCatWebhookJob%')
            ->delete();
    }
};
