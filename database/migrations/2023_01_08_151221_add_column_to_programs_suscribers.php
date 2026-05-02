<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToProgramsSuscribers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('program_subscribers', function (Blueprint $table) {
            $table->string('complete_date')->nullable()->after('resume_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('program_subscribers', function (Blueprint $table) {
            $table->dropColumn('complete_date');
        });
    }
}
