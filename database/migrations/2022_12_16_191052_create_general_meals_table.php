<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeneralMealsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('meals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('meal_type')->default('auto');
            $table->string('meal_by')->default('admin');
            $table->string('name');
            $table->string('prep_time')->nullable();
            $table->string('cook_time')->nullable();
            $table->string('suitable_for')->nullable();
            $table->string('tags')->nullable();
            $table->string('contains')->nullable();
            $table->string('file')->nullable();
            $table->string('file_type')->nullable();
            $table->string('video_thumbnail')->nullable();
            $table->integer('serving_size')->nullable();
            $table->integer('no_of_servings')->nullable();
            $table->integer('calories_per_serving')->nullable();
            $table->integer('protein_per_serving')->nullable();
            $table->integer('carbs_per_serving')->nullable();
            $table->integer('fat_per_serving')->nullable();
            $table->longText('ingredients')->nullable();
            $table->longText('directions')->nullable();
            $table->text('nutrient')->nullable();
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
        Schema::dropIfExists('general_meals');
    }
}
