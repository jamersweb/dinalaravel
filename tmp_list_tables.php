<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=fitnesswithdina_test', 'root', '0Charlemango23@');
$rows = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM);
foreach ($rows as $r) echo $r[0], PHP_EOL;
