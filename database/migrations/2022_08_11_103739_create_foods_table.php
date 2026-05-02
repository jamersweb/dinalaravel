<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFoodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('foods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('serving_size');
            $table->Integer('calories');
            $table->Integer('protein');
            $table->Integer('carbs');
            $table->Integer('fat');
            $table->Integer('saturated_fat')->nullable();
            $table->Integer('trans_fat')->nullable();
            $table->Integer('polyunsaturated_fat')->nullable();
            $table->Integer('monounsaturated_fat')->nullable();
            $table->Integer('cholestrol')->nullable();
            $table->Integer('sodium')->nullable();
            $table->Integer('dietary_fiber')->nullable();
            $table->Integer('total_sugars')->nullable();
            $table->Integer('vitamin_a')->nullable();
            $table->Integer('vitamin_c')->nullable();
            $table->Integer('vitamin_d')->nullable();
            $table->Integer('vitamin_e')->nullable();
            $table->Integer('thiamin')->nullable();
            $table->Integer('riboflavin')->nullable();
            $table->Integer('niacin')->nullable();
            $table->Integer('vitamin_b6')->nullable();
            $table->Integer('vitamin_b12')->nullable();
            $table->Integer('pantothenic_acid')->nullable();
            $table->Integer('calcium')->nullable();
            $table->Integer('iron')->nullable();
            $table->Integer('potassium')->nullable();
            $table->Integer('phosphorus')->nullable();
            $table->Integer('magnesium')->nullable();
            $table->Integer('zinc')->nullable();
            $table->Integer('selenium')->nullable();
            $table->Integer('copper')->nullable();
            $table->Integer('menganese')->nullable();
            $table->Integer('alchohal')->nullable();
            $table->Integer('caffeine')->nullable();
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
        Schema::dropIfExists('foods');
    }
}
