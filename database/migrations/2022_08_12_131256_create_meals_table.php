<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMealsTable extends Migration
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
            $table->string('name');
            $table->string('prep_time');
            $table->string('cook_time');
            $table->string('suitable_for');
            $table->string('tags')->nullable();
            $table->string('contains')->nullable();
            $table->string('file');
            $table->string('file_type')->default('video');
            $table->string('video_thumbnail')->nullable();
            $table->integer('no_of_servings');
            $table->integer('calories_per_serving');
            $table->integer('protein_per_serving');
            $table->integer('carbs_per_serving');
            $table->integer('fat_per_serving');
            $table->longText('ingredients');
            $table->longText('directions')->nullable();
            $table->text('nutrient')->nullable();
            $table->string('meal_type')->default('auto');
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
        Schema::dropIfExists('meals');
    }
}
