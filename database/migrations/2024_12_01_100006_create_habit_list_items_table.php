<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHabitListItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('habit_list_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('habit_list_id');
            $table->foreign('habit_list_id')->references('id')->on('habit_lists')->onDelete('cascade');
            $table->string('habit_name');
            $table->integer('order')->default(0);
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
        Schema::dropIfExists('habit_list_items');
    }
}

