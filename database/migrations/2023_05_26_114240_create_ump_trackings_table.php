<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUmpTrackingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ump_trackings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ump_id');
            $table->foreign('ump_id')->references('id')->on('user_meal_plans')->cascadeOnDelete();
            $table->unsignedBigInteger('mw_id');
            $table->foreign('mw_id')->references('id')->on('meal_weeks')->cascadeOnDelete();
            $table->unsignedBigInteger('md_id');
            $table->foreign('md_id')->references('id')->on('meal_days')->cascadeOnDelete();
            $table->integer('meal_id');
            $table->string('meal_type');
            $table->boolean('status')->default(0);
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
        Schema::dropIfExists('ump_trackings');
    }
}
