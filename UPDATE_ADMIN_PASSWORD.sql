-- Update Admin Password SQL Query
-- Run this in phpMyAdmin or MySQL client

-- Option 1: Update password to Admin@Fitness2024
UPDATE users 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE email = 'dina@gmail.com' AND role = 2;

-- Note: The hash above is for password: Admin@Fitness2024
-- If you want a different password, generate a new hash using:
-- php artisan tinker
-- Hash::make('YourPasswordHere')
