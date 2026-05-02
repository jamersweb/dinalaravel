<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMealWeeksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('meal_weeks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->string('tags')->nullable();
            $table->unsignedBigInteger('meal_day1')->nullable();
            $table->unsignedBigInteger('meal_day2')->nullable();
            $table->unsignedBigInteger('meal_day3')->nullable();
            $table->unsignedBigInteger('meal_day4')->nullable();
            $table->unsignedBigInteger('meal_day5')->nullable();
            $table->unsignedBigInteger('meal_day6')->nullable();
            $table->unsignedBigInteger('meal_day7')->nullable();
            $table->boolean('status')->default(1);
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
        Schema::dropIfExists('meal_weeks');
    }
}
