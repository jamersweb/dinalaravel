<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMealPlanDaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('meal_plan_days', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('meal_plan_id');
            $table->foreign('meal_plan_id')->references('id')->on('meal_plans')->cascadeOnDelete();
            $table->string('day',20);
            $table->unsignedBigInteger('breakfast')->nullable();
            $table->unsignedBigInteger('lunch')->nullable();
            $table->unsignedBigInteger('dinner')->nullable();
            $table->unsignedBigInteger('snacks')->nullable();
            $table->unsignedBigInteger('drink')->nullable();
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
        Schema::dropIfExists('meal_plan_days');
    }
}
