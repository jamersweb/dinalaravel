<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exercise_library_tags')) {
            return;
        }

        Schema::table('exercise_library_tags', function (Blueprint $table) {
            if (! Schema::hasColumn('exercise_library_tags', 'equipment_tags')) {
                $table->json('equipment_tags')->nullable()->after('equipment_category');
            }
            if (! Schema::hasColumn('exercise_library_tags', 'secondary_muscle_groups')) {
                $table->json('secondary_muscle_groups')->nullable()->after('muscle_group');
            }
            if (! Schema::hasColumn('exercise_library_tags', 'movement_patterns')) {
                $table->json('movement_patterns')->nullable()->after('exercise_type');
            }
            if (! Schema::hasColumn('exercise_library_tags', 'training_styles')) {
                $table->json('training_styles')->nullable()->after('movement_patterns');
            }
            if (! Schema::hasColumn('exercise_library_tags', 'workout_sections')) {
                $table->json('workout_sections')->nullable()->after('training_styles');
            }
            if (! Schema::hasColumn('exercise_library_tags', 'impact_level')) {
                $table->string('impact_level', 32)->nullable()->after('workout_sections');
            }
            if (! Schema::hasColumn('exercise_library_tags', 'intensity_level')) {
                $table->string('intensity_level', 32)->nullable()->after('impact_level');
            }
            if (! Schema::hasColumn('exercise_library_tags', 'video_variant')) {
                $table->string('video_variant', 32)->default('explained')->after('intensity_level');
            }
            if (! Schema::hasColumn('exercise_library_tags', 'recommended_duration_seconds')) {
                $table->unsignedSmallInteger('recommended_duration_seconds')->nullable()->after('video_variant');
            }
            if (! Schema::hasColumn('exercise_library_tags', 'recommended_repetitions')) {
                $table->string('recommended_repetitions', 64)->nullable()->after('recommended_duration_seconds');
            }
            if (! Schema::hasColumn('exercise_library_tags', 'recommended_sets')) {
                $table->string('recommended_sets', 64)->nullable()->after('recommended_repetitions');
            }
            if (! Schema::hasColumn('exercise_library_tags', 'recommended_rest_seconds')) {
                $table->unsignedSmallInteger('recommended_rest_seconds')->nullable()->after('recommended_sets');
            }
            if (! Schema::hasColumn('exercise_library_tags', 'safety_notes')) {
                $table->json('safety_notes')->nullable()->after('recommended_rest_seconds');
            }
            if (! Schema::hasColumn('exercise_library_tags', 'contraindications')) {
                $table->json('contraindications')->nullable()->after('safety_notes');
            }
        });

        Schema::table('exercise_library_tags', function (Blueprint $table) {
            try {
                $table->index(['difficulty', 'impact_level', 'intensity_level'], 'exercise_tags_level_impact_intensity_idx');
                $table->index(['video_variant', 'language'], 'exercise_tags_video_variant_language_idx');
            } catch (Throwable $e) {
                // Indexes may already exist in restored databases.
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('exercise_library_tags')) {
            return;
        }

        Schema::table('exercise_library_tags', function (Blueprint $table) {
            try {
                $table->dropIndex('exercise_tags_level_impact_intensity_idx');
                $table->dropIndex('exercise_tags_video_variant_language_idx');
            } catch (Throwable $e) {
            }
        });

        Schema::table('exercise_library_tags', function (Blueprint $table) {
            foreach ([
                'contraindications',
                'safety_notes',
                'recommended_rest_seconds',
                'recommended_sets',
                'recommended_repetitions',
                'recommended_duration_seconds',
                'video_variant',
                'intensity_level',
                'impact_level',
                'workout_sections',
                'training_styles',
                'movement_patterns',
                'secondary_muscle_groups',
                'equipment_tags',
            ] as $column) {
                if (Schema::hasColumn('exercise_library_tags', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
