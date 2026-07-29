<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('workouts')) {
            return;
        }

        Schema::table('workouts', function (Blueprint $table) {
            if (! Schema::hasColumn('workouts', 'equipment_category')) {
                $table->string('equipment_category', 32)->nullable()->after('category');
            }
            if (! Schema::hasColumn('workouts', 'fitness_level')) {
                $table->string('fitness_level', 32)->nullable()->after('equipment_category');
            }
            if (! Schema::hasColumn('workouts', 'workout_type')) {
                $table->string('workout_type', 64)->nullable()->after('fitness_level');
            }
            if (! Schema::hasColumn('workouts', 'routine_source')) {
                $table->string('routine_source', 32)->nullable()->after('workout_type');
            }
            if (! Schema::hasColumn('workouts', 'routine_status')) {
                $table->string('routine_status', 32)->nullable()->after('routine_source');
            }
            if (! Schema::hasColumn('workouts', 'routine_generation_batch_id')) {
                $table->unsignedBigInteger('routine_generation_batch_id')->nullable()->after('routine_status');
            }
            if (! Schema::hasColumn('workouts', 'routine_sections')) {
                $table->json('routine_sections')->nullable()->after('daily_summary');
            }
            if (! Schema::hasColumn('workouts', 'routine_validation_errors')) {
                $table->json('routine_validation_errors')->nullable()->after('routine_sections');
            }
            if (! Schema::hasColumn('workouts', 'review_notes')) {
                $table->text('review_notes')->nullable()->after('routine_validation_errors');
            }
            if (! Schema::hasColumn('workouts', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('review_notes');
            }
            if (! Schema::hasColumn('workouts', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            }
            if (! Schema::hasColumn('workouts', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('workouts', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('reviewed_at');
            }
        });

        Schema::table('workouts', function (Blueprint $table) {
            try {
                $table->index(['routine_status', 'equipment_category', 'fitness_level'], 'workouts_routine_status_equipment_level_idx');
                $table->index(['language', 'routine_status'], 'workouts_language_routine_status_idx');
            } catch (Throwable $e) {
                // Indexes may already exist in restored databases.
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('workouts')) {
            return;
        }

        Schema::table('workouts', function (Blueprint $table) {
            try {
                $table->dropIndex('workouts_routine_status_equipment_level_idx');
                $table->dropIndex('workouts_language_routine_status_idx');
            } catch (Throwable $e) {
            }
        });

        Schema::table('workouts', function (Blueprint $table) {
            foreach ([
                'reviewed_by',
                'reviewed_at',
                'approved_by',
                'approved_at',
                'review_notes',
                'routine_validation_errors',
                'routine_sections',
                'routine_status',
                'routine_generation_batch_id',
                'routine_source',
                'workout_type',
                'fitness_level',
                'equipment_category',
            ] as $column) {
                if (Schema::hasColumn('workouts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
