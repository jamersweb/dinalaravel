<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrivacySettingToPosturePictures extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('posture_pictures', function (Blueprint $table) {
            $table->enum('privacy_setting', ['confidential', 'approved_for_social'])->default('confidential')->after('side_picture');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('posture_pictures', function (Blueprint $table) {
            $table->dropColumn('privacy_setting');
        });
    }
}

