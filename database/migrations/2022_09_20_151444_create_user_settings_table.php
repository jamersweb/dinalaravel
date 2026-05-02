<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('language')->default('en');
            $table->boolean('group_chat_noti')->default(1);
            $table->boolean('private_chat_noti')->default(1);
            $table->boolean('comments_noti')->default(1);
            $table->boolean('payments_noti')->default(1);
            $table->boolean('activities_noti')->default(1);
            $table->string('weight_unit')->default('kg');
            $table->string('distance_unit')->default('km');
            $table->string('body_measures')->default('cm');
            $table->string('video_quality')->default('alwaysHD');
            $table->text('stats_sequence')->nullable();
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
        Schema::dropIfExists('user_settings');
    }
}
