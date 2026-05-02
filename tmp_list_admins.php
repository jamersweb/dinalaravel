<?php
$pass = '0Charlemango23@';
$db = 'dina';
$pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=$db", 'root', $pass);
$q = $pdo->query("SELECT id,name,email,role,status FROM users WHERE role IN (0,2) OR email LIKE '%dina%' ORDER BY id DESC LIMIT 20");
$rows = $q->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) echo json_encode($r), PHP_EOL;
if (!$rows) echo "NO_ROWS\n";
