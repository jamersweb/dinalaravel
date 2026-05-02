<?php
$pass = '0Charlemango23@';
$email = 'dina@gmail.com';
$dbs = ['dina', 'fitnesswithdina_test'];
foreach ($dbs as $db) {
  try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=$db", 'root', $pass);
    $stmt = $pdo->prepare("SELECT id,email,role,status FROM users WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $db, ': ', $row ? json_encode($row) : 'NOT_FOUND', PHP_EOL;
  } catch (Throwable $e) {
    echo $db, ': ERROR ', $e->getMessage(), PHP_EOL;
  }
}
