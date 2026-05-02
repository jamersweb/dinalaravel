<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomThumbnailToExercisesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('exercises') && ! Schema::hasColumn('exercises', 'custom_thumbnail')) {
            Schema::table('exercises', function (Blueprint $table) {
                $table->string('custom_thumbnail')->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('exercises', 'custom_thumbnail')) {
            Schema::table('exercises', function (Blueprint $table) {
                $table->dropColumn('custom_thumbnail');
            });
        }
    }
}
