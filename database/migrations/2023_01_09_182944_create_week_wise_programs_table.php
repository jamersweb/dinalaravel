<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWeekWiseProgramsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('week_wise_programs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_sub_id');
            $table->foreign('program_sub_id')->references('id')->on('program_subs')->cascadeOnDelete();
            $table->tinyInteger('week_no');
            $table->boolean('status')->default(0);
            $table->timestamp('done_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('week_wise_programs');
    }
}
