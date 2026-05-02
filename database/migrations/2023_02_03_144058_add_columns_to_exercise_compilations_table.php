<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToExerciseCompilationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('exercise_compilations', function (Blueprint $table) {
            $table->integer('weight')->nullable()->after('rounds');
            $table->string('weight_unit',20)->nullable()->after('weight');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('exercise_compilations', function (Blueprint $table) {
            $table->dropColumn('weight_unit');
            $table->dropColumn('weight');
        });
    }
}
