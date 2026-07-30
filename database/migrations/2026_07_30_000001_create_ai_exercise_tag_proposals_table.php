<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_exercise_tag_proposals')) {
            return;
        }

        Schema::create('ai_exercise_tag_proposals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exercise_id');
            $table->string('provider', 32)->default('ollama');
            $table->string('model', 128);
            $table->string('status', 32)->default('proposed');
            $table->json('source_metadata')->nullable();
            $table->json('current_tag_payload')->nullable();
            $table->json('proposed_payload')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->text('reasoning')->nullable();
            $table->longText('raw_response')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('exercise_id')->references('id')->on('exercises')->onDelete('cascade');
            $table->index(['status', 'created_at'], 'ai_tag_proposals_status_created_idx');
            $table->index(['exercise_id', 'status'], 'ai_tag_proposals_exercise_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_exercise_tag_proposals');
    }
};
