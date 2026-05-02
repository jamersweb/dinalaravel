<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBodyMeasurementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('body_measurements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->integer('neck')->nullable();
            $table->integer('shoulders')->nullable();
            $table->integer('chest')->nullable();
            $table->integer('left_bicep')->nullable();
            $table->integer('right_bicep')->nullable();
            $table->integer('left_forearm')->nullable();
            $table->integer('right_forearm')->nullable();
            $table->integer('waist')->nullable();
            $table->integer('hips')->nullable();
            $table->integer('left_thigh')->nullable();
            $table->integer('right_thigh')->nullable();
            $table->integer('left_calf')->nullable();
            $table->integer('right_calf')->nullable();
            $table->string('unit');
            $table->timestamp('date');
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
        Schema::dropIfExists('body_measurements');
    }
}
