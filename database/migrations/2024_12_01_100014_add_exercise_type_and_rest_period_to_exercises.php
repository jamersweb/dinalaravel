<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExerciseTypeAndRestPeriodToExercises extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('exercises', function (Blueprint $table) {
            if (!Schema::hasColumn('exercises', 'exercise_type')) {
                $table->enum('exercise_type', ['repetitions', 'time'])->default('repetitions')->after('type');
            }
            if (!Schema::hasColumn('exercises', 'rest_period')) {
                $table->integer('rest_period')->nullable()->comment('Rest period in seconds')->after('exercise_type');
            }
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
            if (Schema::hasColumn('exercises', 'exercise_type')) {
                $table->dropColumn('exercise_type');
            }
            if (Schema::hasColumn('exercises', 'rest_period')) {
                $table->dropColumn('rest_period');
            }
        });
    }
}

