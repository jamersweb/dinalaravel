<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '0Charlemango23@');
$pdo->exec('CREATE DATABASE IF NOT EXISTS fitnesswithdina_test');
echo "ok\n";
