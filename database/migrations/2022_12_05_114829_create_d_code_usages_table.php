<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDCodeUsagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('d_code_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('code_id');
            $table->foreign('code_id')->references('id')->on('discount_codes')->cascadeOnDelete();
            $table->string('user_email');
            $table->string('valid_products');
            $table->boolean('status');
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
        Schema::dropIfExists('d_code_usages');
    }
}
