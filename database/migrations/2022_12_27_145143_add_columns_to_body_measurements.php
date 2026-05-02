<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToBodyMeasurements extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('body_measurements', function (Blueprint $table) {
            $table->integer('belly_button')->nullable()->after('right_calf');
            $table->integer('under_belly')->nullable()->after('belly_button');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('body_measurements', function (Blueprint $table) {
            $table->dropColumn('under_belly');
            $table->dropColumn('belly_button');
        });
    }
}
