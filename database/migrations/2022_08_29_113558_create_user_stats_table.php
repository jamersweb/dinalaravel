<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('date');
            $table->text('sleep')->nullable();
            $table->text('steps')->nullable();
            $table->text('body_weight')->nullable();
            $table->text('body_fat')->nullable();
            $table->text('caloric_intake')->nullable();
            $table->text('caloric_burn')->nullable();
            $table->text('resting_hr')->nullable();
            $table->text('blood_pressure')->nullable();
            $table->text('lean_body_mass')->nullable();
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
        Schema::dropIfExists('user_stats');
    }
}
