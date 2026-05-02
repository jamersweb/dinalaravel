<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('meals') && ! Schema::hasColumn('meals', 'locale_translations')) {
            Schema::table('meals', function (Blueprint $table) {
                $table->json('locale_translations')->nullable()->after('language');
            });
        }
        if (Schema::hasTable('exercises') && ! Schema::hasColumn('exercises', 'locale_translations')) {
            Schema::table('exercises', function (Blueprint $table) {
                $table->json('locale_translations')->nullable()->after('language');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('meals', 'locale_translations')) {
            Schema::table('meals', function (Blueprint $table) {
                $table->dropColumn('locale_translations');
            });
        }
        if (Schema::hasColumn('exercises', 'locale_translations')) {
            Schema::table('exercises', function (Blueprint $table) {
                $table->dropColumn('locale_translations');
            });
        }
    }
};
