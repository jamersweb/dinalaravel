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
            if (! Schema::hasColumn('exercise_library_tags', 'primary_category')) {
                $table->string('primary_category', 64)->nullable()->after('equipment_tags');
            }
            if (! Schema::hasColumn('exercise_library_tags', 'training_adaptation')) {
                $table->string('training_adaptation', 64)->nullable()->after('primary_category');
            }
            if (! Schema::hasColumn('exercise_library_tags', 'program_role')) {
                $table->string('program_role', 64)->nullable()->after('training_adaptation');
            }
            if (! Schema::hasColumn('exercise_library_tags', 'safety_flags')) {
                $table->json('safety_flags')->nullable()->after('usage_flags');
            }
        });

        Schema::table('exercise_library_tags', function (Blueprint $table) {
            try {
                $table->index(['primary_category', 'program_role'], 'exercise_tags_taxonomy_role_idx');
                $table->index(['training_adaptation', 'difficulty'], 'exercise_tags_adaptation_level_idx');
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
                $table->dropIndex('exercise_tags_taxonomy_role_idx');
                $table->dropIndex('exercise_tags_adaptation_level_idx');
            } catch (Throwable $e) {
            }
        });

        Schema::table('exercise_library_tags', function (Blueprint $table) {
            foreach ([
                'safety_flags',
                'program_role',
                'training_adaptation',
                'primary_category',
            ] as $column) {
                if (Schema::hasColumn('exercise_library_tags', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
