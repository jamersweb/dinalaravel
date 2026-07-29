<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('routine_generation_batches')) {
            return;
        }

        Schema::create('routine_generation_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code', 80)->unique();
            $table->string('status', 32)->default('draft');
            $table->json('filters')->nullable();
            $table->json('missing_content_report')->nullable();
            $table->json('validation_report')->nullable();
            $table->unsignedInteger('requested_count')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routine_generation_batches');
    }
};
