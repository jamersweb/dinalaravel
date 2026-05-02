<?php
// Simple MySQL connection test
$host = '127.0.0.1';
$port = 3306;
$user = 'root';
$pass = '';

echo "Testing MySQL connection...\n";
echo "Host: $host:$port\n";
echo "User: $user\n\n";

try {
    $pdo = new PDO("mysql:host=$host;port=$port", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ MySQL connection successful!\n\n";
    
    // Check databases
    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Available databases:\n";
    foreach ($databases as $db) {
        echo "  - $db\n";
    }
    
    echo "\n";
    if (in_array('dina', $databases)) {
        echo "✓ Database 'dina' exists\n";
        
        // Try to connect to dina database
        $pdo2 = new PDO("mysql:host=$host;port=$port;dbname=dina", $user, $pass);
        $pdo2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "✓ Connected to 'dina' database\n";
        
        // Check users table
        $stmt = $pdo2->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            echo "✓ 'users' table exists\n";
            
            $stmt = $pdo2->query("SELECT COUNT(*) as count FROM users");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "✓ Found $count user(s) in users table\n";
            
            // Check admin user
            $stmt = $pdo2->prepare("SELECT id, name, email, role FROM users WHERE email = ? AND role = 2");
            $stmt->execute(['dina@gmail.com']);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                echo "✓ Admin user found:\n";
                echo "   ID: {$admin['id']}\n";
                echo "   Name: {$admin['name']}\n";
                echo "   Email: {$admin['email']}\n";
                echo "   Role: {$admin['role']}\n";
            } else {
                echo "⚠ Admin user (dina@gmail.com) not found\n";
            }
        } else {
            echo "⚠ 'users' table does not exist\n";
        }
    } else {
        echo "⚠ Database 'dina' does NOT exist\n";
        echo "\nTo create it, run:\n";
        echo "CREATE DATABASE dina;\n";
    }
    
} catch (PDOException $e) {
    echo "✗ MySQL connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Make sure XAMPP MySQL is running\n";
    echo "2. Check XAMPP Control Panel\n";
    echo "3. Try restarting MySQL service\n";
}
