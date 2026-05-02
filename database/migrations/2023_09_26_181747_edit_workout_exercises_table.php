<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EditWorkoutExercisesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workout_exercises', function (Blueprint $table) {
            $table->dropColumn('rounds');
            $table->dropColumn('supersets');
            $table->integer('sets_rounds')->after('description')->nullable();
            $table->string('category',50)->after('sets_rounds')->default('simple');
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
            $table->dropColumn('sets_rounds');
            $table->dropColumn('category');
            $table->integer('rounds')->after('rest_period')->nullable();
            $table->integer('supersets')->after('rest_period')->nullable();
        });
    }
}
