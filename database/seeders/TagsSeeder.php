<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $category = 'exercise';
        $types = ['Main muscle','Equipment','Movement','Mechanics','Level'];
        $type1tags = ['Abductors','Abs','Adductors','Back (lower)','Back (middle)','Bicep','Calves','Chest (inner)',
        'Chest (mid)','Chest (upper)','Forearms','Glutes','Hamstrings','Lats','Neck','Obliques','Quads','Shoulder (front)',
        'Shoulder (rear)','Shoulder (side)','Traps','Triceps'];
        $type2tags = ['Bands (handles)','Bands (loops)','Barbell','Battle ropes','Bench','Body weight','BOSU','Box','Cable',
        'D-ring','Dumbbell','EZ bar','Foam roller','Jump rope','Kettlebell','Lacrosse ball','Landmine','Machine','Mat','Medicine ball',
        'Mini band','Plate','Pull up bar','Sandbag','Slam ball','Sled','Sliders','Smith machine','Suspension','Swiss ball'];
        $type3tags = ['Alternating','Anti-extension core','Anti-rotation core','Bilateral','Contralateral','General conditioning',
        'Hip dominant','Horizontal pull','Horizontal push','IpsiLateral','Knee dominant','Mobility','Plyometrics','Static stretches',
        'Unilateral','Vertical pull','Vertical push'];
        $type4tags = ['Compound','Isolation'];
        $type5tags = ['Advanced','Beginner','Intermediate'];

        foreach ($type1tags as $tag) {
            Tag::firstorCreate([
                'name' => $tag,
                'type' => $types[0],
                'category' => $category
            ]);
        }
        foreach ($type2tags as $tag) {
            Tag::firstorCreate([
                'name' => $tag,
                'type' => $types[1],
                'category' => $category
            ]);
        }
        foreach ($type3tags as $tag) {
            Tag::firstorCreate([
                'name' => $tag,
                'type' => $types[2],
                'category' => $category
            ]);
        }
        foreach ($type4tags as $tag) {
            Tag::firstorCreate([
                'name' => $tag,
                'type' => $types[3],
                'category' => $category
            ]);
        }
        foreach ($type5tags as $tag) {
            Tag::firstorCreate([
                'name' => $tag,
                'type' => $types[4],
                'category' => $category
            ]);
        }
        $category = 'meal';
        $mealTags = ['Palio','High fiber','One pot','Slow cooker','Salad','Soup','Smoothie'];
        foreach ($mealTags as $tag) {
            Tag::firstorCreate([
                'name' => $tag,
                'type' => 'basic',
                'category' => $category
            ]);
        }
    }
}

