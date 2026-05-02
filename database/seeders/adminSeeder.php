<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserSetting;
use Illuminate\Support\Facades\Hash;

class adminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
		$admin = User::where('email', 'dina@gmail.com')->first();
		if (!$admin) {
			$admin = User::create([
				'name' => 'Dina',
				'email' => 'dina@gmail.com',
				'email_verified_at'  => now(),
				'email_verification_code' => 1111,
				'password' => Hash::make('Admin@Fitness2024'),
				'role' => 2
			]);
		} else {
			$admin->password = Hash::make('Admin@Fitness2024');
			$admin->role = 2;
			$admin->save();
		}
		$userId = $admin->id;
		UserDetail::firstOrCreate(
			['user_id' => $userId],
			['name' => 'Dina', 'Lastname' => 'Taji', 'phone' => '62239456', 'DOB' => '27-3-1997', 'country' => 'Dubai', 'gender' => 'female', 'subscription' => 0]
		);
		UserSetting::firstOrCreate(['user_id' => $userId], []);
    }
}
