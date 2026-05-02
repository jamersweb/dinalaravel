# Update Admin Password Instructions

## Current Situation
The admin password in the database is still the old one (`fwd123`), but the seeder has been updated to use `Admin@Fitness2024`.

## Solution Options

### Option 1: Using Artisan Command (Recommended)
1. Make sure XAMPP MySQL is running
2. Open terminal/command prompt in the project directory
3. Run:
   ```bash
   php artisan admin:update-password
   ```
   Or with custom password:
   ```bash
   php artisan admin:update-password dina@gmail.com YourNewPassword
   ```

### Option 2: Using Laravel Tinker
1. Make sure XAMPP MySQL is running
2. Open terminal and run:
   ```bash
   php artisan tinker
   ```
3. Then run:
   ```php
   $user = App\Models\User::where('email', 'dina@gmail.com')->where('role', 2)->first();
   $user->password = Hash::make('Admin@Fitness2024');
   $user->save();
   exit
   ```

### Option 3: Direct SQL Query (phpMyAdmin)
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Select your database
3. Go to SQL tab
4. Run this query:
   ```sql
   UPDATE users 
   SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
   WHERE email = 'dina@gmail.com' AND role = 2;
   ```
   (This hash is for password: `Admin@Fitness2024`)

### Option 4: Generate New Hash and Update
If you want a different password:
1. Run: `php artisan tinker`
2. Run: `Hash::make('YourDesiredPassword')`
3. Copy the hash
4. Update in database using SQL or tinker

## New Admin Credentials
- **Email:** dina@gmail.com
- **Password:** Admin@Fitness2024

## After Updating
Try logging in again at: http://127.0.0.1:8000/cms/login
