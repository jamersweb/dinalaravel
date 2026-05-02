<?php

namespace Database\Seeders;

use App\Models\ActivitiesCategory;
use Illuminate\Database\Seeder;

class ActivitiesCategories extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $cats = ['Workouts','Cardios','Habits','Goals Hit','Body Stats','Photos','Payments','Meals'];
        foreach ($cats as $cat) {
            ActivitiesCategory::firstorCreate([
                'name' => $cat
            ]);
        }
        
    }
}
