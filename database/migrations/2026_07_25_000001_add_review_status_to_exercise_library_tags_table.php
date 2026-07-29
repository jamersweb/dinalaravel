<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exercise_library_tags') || Schema::hasColumn('exercise_library_tags', 'review_status')) {
            return;
        }

        Schema::table('exercise_library_tags', function (Blueprint $table) {
            $table->string('review_status', 32)->default('pending_review')->after('approved_for_generation');
            $table->index(['review_status', 'approved_for_generation'], 'exercise_tags_review_status_idx');
        });

        DB::table('exercise_library_tags')
            ->where('approved_for_generation', true)
            ->update(['review_status' => 'approved']);

        DB::table('exercise_library_tags')
            ->where('approved_for_generation', false)
            ->update(['review_status' => 'pending_review']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('exercise_library_tags') || ! Schema::hasColumn('exercise_library_tags', 'review_status')) {
            return;
        }

        Schema::table('exercise_library_tags', function (Blueprint $table) {
            $table->dropIndex('exercise_tags_review_status_idx');
            $table->dropColumn('review_status');
        });
    }
};
