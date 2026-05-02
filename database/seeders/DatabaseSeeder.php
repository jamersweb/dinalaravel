<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(LanguageSeeder::class);
        $this->call(ActivitiesCategories::class);
        $this->call(adminSeeder::class);
        $this->call(TagsSeeder::class);
        $this->call(BadgesSeeder::class);
        $this->call(AutomatedMessagesSeeder::class);
        $this->call(ConsultationFormSeeder::class);
    }
}
