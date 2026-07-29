<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('consultation_recommendations')) {
            return;
        }

        Schema::create('consultation_recommendations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('consultation_form_id')->nullable();
            $table->decimal('bmr', 8, 2)->nullable();
            $table->decimal('tdee', 8, 2)->nullable();
            $table->integer('recommended_calories')->nullable();
            $table->string('training_level', 32)->nullable();
            $table->string('equipment_category', 32)->nullable();
            $table->unsignedTinyInteger('weekly_workout_frequency')->nullable();
            $table->json('injury_precautions')->nullable();
            $table->json('missing_fields')->nullable();
            $table->json('recommended_routine_ids')->nullable();
            $table->json('calculation_payload')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('consultation_form_id')->references('id')->on('consultation_forms')->onDelete('set null');
            $table->index(['user_id', 'created_at'], 'consultation_recommendations_user_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_recommendations');
    }
};
