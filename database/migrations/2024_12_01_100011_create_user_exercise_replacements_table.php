<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserExerciseReplacementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_exercise_replacements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('workout_id')->nullable();
            $table->foreign('workout_id')->references('id')->on('workouts')->onDelete('cascade');
            $table->unsignedBigInteger('original_exercise_id');
            $table->foreign('original_exercise_id')->references('id')->on('exercises')->onDelete('cascade');
            $table->unsignedBigInteger('alternate_exercise_id');
            $table->foreign('alternate_exercise_id')->references('id')->on('exercises')->onDelete('cascade');
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
        Schema::dropIfExists('user_exercise_replacements');
    }
}

