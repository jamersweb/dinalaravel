<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExerciseCompilationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exercise_compilations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('program_sub_id');
            $table->unsignedBigInteger('week_id');
            $table->unsignedBigInteger('weekly_workout_id');
            $table->unsignedInteger('workout_exercise_id');
            $table->unsignedInteger('exercise_id');
            $table->integer('sets')->nullable();
            $table->integer('rounds')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('exercise_compilations');
    }
}
