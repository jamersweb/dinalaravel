<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PdfColumnsInMealPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('meal_plans', function (Blueprint $table) {
            $table->string('attatchment2')->nullable()->after('attatchment_name');
            $table->string('attatchment2_name')->nullable()->after('attatchment2');
            $table->string('attatchment3')->nullable()->after('attatchment2_name');
            $table->string('attatchment3_name')->nullable()->after('attatchment3');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('meal_plans', function (Blueprint $table) {
            $table->dropColumn('attatchment2');
            $table->dropColumn('attatchment2_name');
            $table->dropColumn('attatchment3');
            $table->dropColumn('attatchment3_name');
        });
    }
}
