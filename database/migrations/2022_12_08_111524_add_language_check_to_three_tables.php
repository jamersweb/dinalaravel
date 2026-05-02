<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLanguageCheckToThreeTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('exercises', function (Blueprint $table) {
            //
            $table->string('language',10)->default('en')->after('instructions');
        });
        Schema::table('workouts', function (Blueprint $table) {
            //
            $table->string('language',10)->default('en')->after('image');
        });
        Schema::table('programs', function (Blueprint $table) {
            //
            $table->string('language',10)->default('en')->after('image');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropColumn('language');
        });
        Schema::table('workouts', function (Blueprint $table) {
            $table->dropColumn('language');
        });
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('language');
        });
    }
}
