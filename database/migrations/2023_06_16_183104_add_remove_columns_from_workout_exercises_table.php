<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRemoveColumnsFromWorkoutExercisesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workout_exercises', function (Blueprint $table) {
            $table->dropColumn('target');
            $table->string('reps')->nullable()->after('sets');
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
            $table->dropColumn('reps');
            $table->string('target')->nullable()->after('sets');
        });
    }
}
