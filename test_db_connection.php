<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "Testing database connection...\n";
    echo "DB_HOST: " . env('DB_HOST') . "\n";
    echo "DB_DATABASE: " . env('DB_DATABASE') . "\n";
    echo "DB_USERNAME: " . env('DB_USERNAME') . "\n";
    echo "\n";
    
    DB::connection()->getPdo();
    echo "✓ Database connection successful!\n\n";
    
    // Try to query users table
    $userCount = DB::table('users')->count();
    echo "✓ Found {$userCount} user(s) in database\n";
    
    // Check if admin user exists
    $admin = DB::table('users')->where('email', 'dina@gmail.com')->where('role', 2)->first();
    if ($admin) {
        echo "✓ Admin user found: {$admin->email}\n";
    } else {
        echo "⚠ Admin user not found\n";
    }
    
} catch (Exception $e) {
    echo "✗ Database connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\n";
    echo "Please check:\n";
    echo "1. XAMPP MySQL is running\n";
    echo "2. Database 'dina' exists\n";
    echo "3. User 'root' has correct permissions\n";
    echo "4. .env file has correct database settings\n";
}
