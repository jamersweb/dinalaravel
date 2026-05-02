<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('exercises') && ! Schema::hasColumn('exercises', 'content_code')) {
            Schema::table('exercises', function (Blueprint $table) {
                $table->string('content_code', 64)->nullable()->after('id');
            });
            Schema::table('exercises', function (Blueprint $table) {
                $table->unique('content_code');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('exercises', 'content_code')) {
            Schema::table('exercises', function (Blueprint $table) {
                $table->dropUnique(['content_code']);
            });
            Schema::table('exercises', function (Blueprint $table) {
                $table->dropColumn('content_code');
            });
        }
    }
};
