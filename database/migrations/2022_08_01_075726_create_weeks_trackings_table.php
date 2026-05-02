<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWeeksTrackingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('weeks_trackings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_track_id');
            $table->foreign('program_track_id')->references('id')->on('programs_trackings')->onDelete('cascade');
            $table->unsignedBigInteger('week_no');
            $table->integer('status');
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
        Schema::dropIfExists('weeks_trackings');
    }
}
