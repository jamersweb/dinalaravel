<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Tag;
use App\Models\Program;
use App\Models\MealPlan;

class TestSeeder extends Seeder
{
    /**
     * Seed the application's database for testing.
     */
    public function run()
    {
        // Create test subscriptions
        Subscription::factory()->count(3)->create();
        
        // Create test tags
        Tag::factory()->injury()->count(3)->create();
        Tag::factory()->goal()->count(4)->create();
        Tag::factory()->medical()->count(3)->create();
        Tag::factory()->fitnessLevel()->count(3)->create();
        
        // Create test programs
        Program::factory()->beginner()->count(2)->create();
        Program::factory()->intermediate()->count(2)->create();
        Program::factory()->expert()->count(1)->create();
        
        // Create test meal plans
        MealPlan::factory()->count(3)->create();
        
        // Create test admin user
        User::factory()->admin()->withDetails()->create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
        ]);
    }
}

