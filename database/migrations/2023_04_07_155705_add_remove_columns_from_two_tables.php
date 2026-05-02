<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRemoveColumnsFromTwoTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('group_messages', function (Blueprint $table) {
            $table->longText('content_ar')->nullable()->after('content');
        });
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('last_message');
            $table->dropColumn('last_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('two_tables', function (Blueprint $table) {
            $table->dropColumn('content_ar');
            $table->longText('last_message')->nullable()->after('image');
            $table->longText('last_type')->nullable()->after('last_message');
        });
    }
}
