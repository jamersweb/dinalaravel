<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MakeUserDetailsCountryNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->modifyCountryNullable(true);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('user_details')->whereNull('country')->update(['country' => '']);

        $this->modifyCountryNullable(false);
    }

    private function modifyCountryNullable(bool $nullable)
    {
        if (!Schema::hasTable('user_details')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `user_details` MODIFY `country` VARCHAR(255) ' . ($nullable ? 'NULL' : 'NOT NULL'));
        }
    }
}
