<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workouts') && ! Schema::hasColumn('workouts', 'content_code')) {
            Schema::table('workouts', function (Blueprint $table) {
                $table->string('content_code', 64)->nullable()->after('id');
            });
            Schema::table('workouts', function (Blueprint $table) {
                $table->unique('content_code');
            });
        }
        if (Schema::hasTable('programs') && ! Schema::hasColumn('programs', 'content_code')) {
            Schema::table('programs', function (Blueprint $table) {
                $table->string('content_code', 64)->nullable()->after('id');
            });
            Schema::table('programs', function (Blueprint $table) {
                $table->unique('content_code');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('workouts', 'content_code')) {
            Schema::table('workouts', function (Blueprint $table) {
                $table->dropUnique(['content_code']);
            });
            Schema::table('workouts', function (Blueprint $table) {
                $table->dropColumn('content_code');
            });
        }
        if (Schema::hasColumn('programs', 'content_code')) {
            Schema::table('programs', function (Blueprint $table) {
                $table->dropUnique(['content_code']);
            });
            Schema::table('programs', function (Blueprint $table) {
                $table->dropColumn('content_code');
            });
        }
    }
};
