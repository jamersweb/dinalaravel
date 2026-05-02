<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLocaleTranslationsToProgramsAndWorkouts extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('programs') && ! Schema::hasColumn('programs', 'locale_translations')) {
            Schema::table('programs', function (Blueprint $table) {
                $table->json('locale_translations')->nullable()->after('language');
            });
        }
        if (Schema::hasTable('workouts') && ! Schema::hasColumn('workouts', 'locale_translations')) {
            Schema::table('workouts', function (Blueprint $table) {
                $table->json('locale_translations')->nullable()->after('language');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('programs', 'locale_translations')) {
            Schema::table('programs', function (Blueprint $table) {
                $table->dropColumn('locale_translations');
            });
        }
        if (Schema::hasColumn('workouts', 'locale_translations')) {
            Schema::table('workouts', function (Blueprint $table) {
                $table->dropColumn('locale_translations');
            });
        }
    }
}
