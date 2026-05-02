<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToDiscountCodes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('discount_codes', function (Blueprint $table) {
            $table->string('availability',20)->default('specific')->after('off_by');
            $table->timestamp('valid_till')->nullable()->after('availability');
            $table->string('valid_products')->nullable()->after('valid_till');
        });

        Schema::table('d_code_usages', function(Blueprint $table) {
            $table->integer('user_id')->nullable()->after('user_email');
            $table->dropColumn('valid_products');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('discount_codes', function (Blueprint $table) {
            $table->dropColumn('valid_till');
            $table->dropColumn('availability');
            $table->dropColumn('valid_products');
        });
        Schema::table('d_code_usages', function (Blueprint $table) {
            $table->dropColumn('user_id');
            $table->string('valid_products')->nullable()->after('valid_till');
        });
    }
}
