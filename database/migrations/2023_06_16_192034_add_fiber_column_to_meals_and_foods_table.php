<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFiberColumnToMealsAndFoodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('foods', function (Blueprint $table) {
            $table->integer('fiber')->after('fat')->default(0);
        });
        Schema::table('meals', function (Blueprint $table) {
            $table->integer('fiber_per_serving')->after('fat_per_serving')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('foods', function (Blueprint $table) {
            $table->dropColumn('fiber');
        });
        Schema::table('meals', function (Blueprint $table) {
            $table->dropColumn('fiber_per_serving');
        });
    }
}
