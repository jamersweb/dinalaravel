<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserHabitCompletionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_habit_completions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('habit_list_item_id');
            $table->foreign('habit_list_item_id')->references('id')->on('habit_list_items')->onDelete('cascade');
            $table->date('completed_date');
            $table->timestamps();
            
            $table->unique(['user_id', 'habit_list_item_id', 'completed_date'], 'uhc_user_habit_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_habit_completions');
    }
}

