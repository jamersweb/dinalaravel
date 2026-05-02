<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdateAdminPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:update-password {email=dina@gmail.com} {password=Admin@Fitness2024}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update admin user password';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        $user = User::where('email', $email)->where('role', 2)->first();

        if (!$user) {
            $this->error("Admin user with email '{$email}' not found!");
            return 1;
        }

        $user->password = Hash::make($password);
        $user->save();

        $this->info("Password updated successfully for admin: {$email}");
        $this->info("New password: {$password}");
        
        return 0;
    }
}
