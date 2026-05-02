<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgramHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('program_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('old_program_id')->nullable();
            $table->foreign('old_program_id')->references('id')->on('programs')->onDelete('set null');
            $table->unsignedBigInteger('new_program_id');
            $table->foreign('new_program_id')->references('id')->on('programs')->onDelete('cascade');
            $table->text('progress_snapshot')->nullable(); // JSON data
            $table->timestamp('switched_at');
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
        Schema::dropIfExists('program_history');
    }
}

