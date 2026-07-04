<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weekly_workouts', function (Blueprint $table) {
            $table->string('section_tag', 64)->nullable()->after('display_name');
            $table->unsignedInteger('sort_order')->default(0)->after('section_tag');
        });
    }

    public function down(): void
    {
        Schema::table('weekly_workouts', function (Blueprint $table) {
            $table->dropColumn(['section_tag', 'sort_order']);
        });
    }
};
