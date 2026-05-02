<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserSubsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_subs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sub_id');
            $table->foreign('sub_id')->references('id')->on('subscriptions')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->foreign('payment_id')->references('id')->on('payments')->cascadeOnDelete();
            $table->unsignedBigInteger('discount_code')->nullable();
            $table->foreign('discount_code')->references('id')->on('discount_codes')->cascadeOnDelete();
            $table->string('discount_code_status',20)->nullable();
            $table->string('status',20)->default('awaiting_payment');
            $table->timestamp('sub_start_date')->nullable();
            $table->timestamp('sub_expire_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_subs');
    }
}
