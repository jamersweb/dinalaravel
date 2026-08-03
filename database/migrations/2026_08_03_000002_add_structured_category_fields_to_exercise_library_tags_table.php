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
            if (! Schema::hasColumn('exercise_library_tags', 'secondary_categories')) {
                $table->json('secondary_categories')->nullable()->after('primary_category');
            }
            if (! Schema::hasColumn('exercise_library_tags', 'body_regions')) {
                $table->json('body_regions')->nullable()->after('secondary_muscle_groups');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('exercise_library_tags')) {
            return;
        }

        Schema::table('exercise_library_tags', function (Blueprint $table) {
            foreach (['body_regions', 'secondary_categories'] as $column) {
                if (Schema::hasColumn('exercise_library_tags', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
