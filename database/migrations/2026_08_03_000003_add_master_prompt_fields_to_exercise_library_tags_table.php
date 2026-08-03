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
            if (! Schema::hasColumn('exercise_library_tags', 'exercise_family')) {
                $table->string('exercise_family', 128)->nullable()->after('exercise_type');
            }
            if (! Schema::hasColumn('exercise_library_tags', 'movement_direction')) {
                $table->string('movement_direction', 64)->nullable()->after('exercise_family');
            }
            if (! Schema::hasColumn('exercise_library_tags', 'stability_demand')) {
                $table->string('stability_demand', 32)->nullable()->after('movement_direction');
            }
            if (! Schema::hasColumn('exercise_library_tags', 'variation_type')) {
                $table->string('variation_type', 64)->nullable()->after('stability_demand');
            }
            if (! Schema::hasColumn('exercise_library_tags', 'compatibility_flags')) {
                $table->json('compatibility_flags')->nullable()->after('goal_fit');
            }
            if (! Schema::hasColumn('exercise_library_tags', 'regression_exercise_id')) {
                $table->unsignedBigInteger('regression_exercise_id')->nullable()->after('compatibility_flags');
            }
            if (! Schema::hasColumn('exercise_library_tags', 'progression_exercise_id')) {
                $table->unsignedBigInteger('progression_exercise_id')->nullable()->after('regression_exercise_id');
            }
            if (! Schema::hasColumn('exercise_library_tags', 'alternative_exercise_ids')) {
                $table->json('alternative_exercise_ids')->nullable()->after('progression_exercise_id');
            }
            if (! Schema::hasColumn('exercise_library_tags', 'confidence_bucket')) {
                $table->string('confidence_bucket', 16)->nullable()->after('approved_for_generation');
            }
            if (! Schema::hasColumn('exercise_library_tags', 'review_blockers')) {
                $table->json('review_blockers')->nullable()->after('review_status');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('exercise_library_tags')) {
            return;
        }

        Schema::table('exercise_library_tags', function (Blueprint $table) {
            foreach ([
                'review_blockers',
                'confidence_bucket',
                'alternative_exercise_ids',
                'progression_exercise_id',
                'regression_exercise_id',
                'compatibility_flags',
                'variation_type',
                'stability_demand',
                'movement_direction',
                'exercise_family',
            ] as $column) {
                if (Schema::hasColumn('exercise_library_tags', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
