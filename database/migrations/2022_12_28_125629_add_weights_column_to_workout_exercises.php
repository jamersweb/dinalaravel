<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWeightsColumnToWorkoutExercises extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workout_exercises', function (Blueprint $table) {
            $table->text('description')->nullable()->after('supersets');
        });
        Schema::table('exercises', function (Blueprint $table) {
            $table->text('weights')->nullable()->after('instructions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('workout_exercises', function (Blueprint $table) {
            $table->dropColumn('description');
        });
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropColumn('weights');
        });
    }
}
