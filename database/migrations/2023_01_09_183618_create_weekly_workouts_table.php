<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWeeklyWorkoutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('weekly_workouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('week_id');
            $table->foreign('week_id')->references('id')->on('week_wise_programs')->cascadeOnDelete();
            $table->unsignedBigInteger('workout_id');
            $table->foreign('workout_id')->references('id')->on('workouts')->cascadeOnDelete();
            $table->string('display_name')->nullable();
            $table->boolean('status')->default(0);
            $table->timestamp('done_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('week_wise_workouts');
    }
}
