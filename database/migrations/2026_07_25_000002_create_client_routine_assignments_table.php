<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('client_routine_assignments')) {
            return;
        }

        Schema::create('client_routine_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('consultation_recommendation_id')->nullable();
            $table->unsignedBigInteger('workout_id');
            $table->string('status', 32)->default('assigned');
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('consultation_recommendation_id', 'client_routine_assignments_recommendation_fk')
                ->references('id')->on('consultation_recommendations')->onDelete('set null');
            $table->foreign('workout_id')->references('id')->on('workouts')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['user_id', 'workout_id'], 'client_routine_assignment_unique');
            $table->index(['user_id', 'status'], 'client_routine_assignment_user_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_routine_assignments');
    }
};
