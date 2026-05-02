<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConsultationFormsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('consultation_forms')) {
            return;
        }
        Schema::create('consultation_forms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->text('health_background')->nullable();
            $table->text('injuries')->nullable();
            $table->text('goals')->nullable();
            $table->text('lifestyle_habits')->nullable();
            $table->text('preferred_training_style')->nullable();
            $table->string('fitness_level')->nullable();
            $table->text('medical_concerns')->nullable();
            $table->text('training_experience')->nullable();
            $table->timestamp('completed_at')->nullable();
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
        Schema::dropIfExists('consultation_forms');
    }
}

