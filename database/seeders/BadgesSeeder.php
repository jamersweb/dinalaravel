<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

class BadgesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Badge::firstOrCreate([
            'id' => 1,
            'name' => 'Silver',
            'image' => '/assets/badges/silver.png',
        ]);
        Badge::firstOrCreate([
            'id' => 2,
            'name' => 'Gold',
            'image' => '/assets/badges/gold.png',
        ]);
        Badge::firstOrCreate([
            'id' => 3,
            'name' => 'Platinum',
            'image' => '/assets/badges/platinum.png',
        ]);
        
        if(is_null(Badge::pluck('name_ar')->first())){
            Badge::where('id',1)->update(['name_ar' => 'فضة']);
            Badge::where('id',2)->update(['name_ar' => 'ذهب']);
            Badge::where('id',3)->update(['name_ar' => 'البلاتين']);
        }
    }
}
