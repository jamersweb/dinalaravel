<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('consultation_recommendations')) {
            return;
        }

        Schema::table('consultation_recommendations', function (Blueprint $table) {
            if (! Schema::hasColumn('consultation_recommendations', 'recommended_program_ids')) {
                $table->json('recommended_program_ids')->nullable()->after('recommended_routine_ids');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('consultation_recommendations')) {
            return;
        }

        Schema::table('consultation_recommendations', function (Blueprint $table) {
            if (Schema::hasColumn('consultation_recommendations', 'recommended_program_ids')) {
                $table->dropColumn('recommended_program_ids');
            }
        });
    }
};
