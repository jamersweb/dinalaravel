<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_ui_string_translations', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 16);
            $table->string('message_key', 512);
            $table->text('value');
            $table->timestamps();

            $table->unique(['locale', 'message_key']);
            $table->index('locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_ui_string_translations');
    }
};
