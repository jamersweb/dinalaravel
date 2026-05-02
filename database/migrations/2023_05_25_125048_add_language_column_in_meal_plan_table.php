<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLanguageColumnInMealPlanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('meal_days', function (Blueprint $table) {
            $table->string('language',10)->default('en')->after('tags');
        });
        Schema::table('meal_weeks', function (Blueprint $table) {
            $table->string('language',10)->default('en')->after('tags');
        });
        Schema::table('meal_plans', function (Blueprint $table) {
            $table->string('language',10)->default('en')->after('tags');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('meal_days', function (Blueprint $table) {
            $table->dropColumn('language');
        });
        Schema::table('meal_weeks', function (Blueprint $table) {
            $table->dropColumn('language');
        });
        Schema::table('meal_plans', function (Blueprint $table) {
            $table->dropColumn('language');
        });
    }
}
