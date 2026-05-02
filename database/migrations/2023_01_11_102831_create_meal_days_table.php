<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMealDaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('meal_days', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->string('tags')->nullable();
            $table->unsignedBigInteger('breakfast')->nullable();
            $table->unsignedBigInteger('lunch')->nullable();
            $table->unsignedBigInteger('dinner')->nullable();
            $table->unsignedBigInteger('snacks')->nullable();
            $table->unsignedBigInteger('drinks')->nullable();
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
        Schema::dropIfExists('meal_days');
    }
}
