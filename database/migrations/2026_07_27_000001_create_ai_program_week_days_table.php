<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_program_week_days')) {
            return;
        }

        Schema::create('ai_program_week_days', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('program_phase_id')->nullable();
            $table->unsignedBigInteger('week_id')->nullable();
            $table->unsignedBigInteger('workout_id')->nullable();
            $table->unsignedTinyInteger('week_no');
            $table->unsignedTinyInteger('day_no');
            $table->string('day_type', 32)->default('workout');
            $table->string('display_name')->nullable();
            $table->unsignedSmallInteger('estimated_minutes')->nullable();
            $table->string('training_style', 64)->nullable();
            $table->json('muscle_groups')->nullable();
            $table->json('progression_notes')->nullable();
            $table->json('recovery_guidance')->nullable();
            $table->json('validation_errors')->nullable();
            $table->timestamps();

            $table->foreign('program_id')->references('id')->on('programs')->onDelete('cascade');
            $table->foreign('program_phase_id')->references('id')->on('program_phases')->nullOnDelete();
            $table->foreign('week_id')->references('id')->on('week_wise_programs')->nullOnDelete();
            $table->foreign('workout_id')->references('id')->on('workouts')->nullOnDelete();
            $table->unique(['program_id', 'week_no', 'day_no'], 'ai_program_days_program_week_day_unique');
            $table->index(['program_id', 'week_no'], 'ai_program_days_program_week_idx');
            $table->index(['day_type', 'estimated_minutes'], 'ai_program_days_type_minutes_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_program_week_days');
    }
};
