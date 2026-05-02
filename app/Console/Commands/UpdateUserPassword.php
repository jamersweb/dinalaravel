<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Facades\Hash;

class UpdateUserPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:update-password {identifier} {password=Admin@Fitness2024}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update any user password by email or name';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $identifier = $this->argument('identifier');
        $password = $this->argument('password');

        // Try email first (exact match)
        $user = User::where('email', $identifier)->first();

        // If not found by email, try by name (users.name or user_details.name)
        if (!$user) {
            $user = User::where('name', 'like', "%{$identifier}%")->first();
        }
        if (!$user) {
            $userDetail = UserDetail::where('name', 'like', "%{$identifier}%")
                ->orWhere('Lastname', 'like', "%{$identifier}%")
                ->first();
            if ($userDetail) {
                $user = User::find($userDetail->user_id);
            }
        }

        if (!$user) {
            $this->error("User not found with email or name: '{$identifier}'");
            return 1;
        }

        $user->password = Hash::make($password);
        $user->save();

        $this->info("Password updated successfully for: {$user->email} ({$user->name})");
        $this->info("New password: {$password}");

        return 0;
    }
}
