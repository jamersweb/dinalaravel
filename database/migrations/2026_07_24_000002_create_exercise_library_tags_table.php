<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('exercise_library_tags')) {
            return;
        }

        Schema::create('exercise_library_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exercise_id');
            $table->string('language', 10)->default('en');
            $table->string('equipment_category', 32);
            $table->string('muscle_group', 64)->nullable();
            $table->string('exercise_type', 64);
            $table->string('difficulty', 32)->default('beginner');
            $table->json('injury_cautions')->nullable();
            $table->json('goal_fit')->nullable();
            $table->json('usage_flags')->nullable();
            $table->boolean('approved_for_generation')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('exercise_id')->references('id')->on('exercises')->onDelete('cascade');
            $table->unique('exercise_id');
            $table->index(['language', 'equipment_category', 'difficulty'], 'exercise_tags_lang_equipment_difficulty_idx');
            $table->index(['exercise_type', 'approved_for_generation'], 'exercise_tags_type_approved_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_library_tags');
    }
};
