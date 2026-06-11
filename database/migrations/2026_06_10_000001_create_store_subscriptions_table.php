<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoreSubscriptionsTable extends Migration
{
    public function up()
    {
        Schema::create('store_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('platform', 20);
            $table->string('product_id');
            $table->string('base_plan_id')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('original_transaction_id')->nullable();
            $table->string('purchase_token', 512)->nullable();
            $table->string('status', 32)->default('pending');
            $table->timestamp('purchased_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['platform', 'product_id']);
            $table->unique(['platform', 'purchase_token'], 'store_subs_platform_purchase_token_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('store_subscriptions');
    }
}
