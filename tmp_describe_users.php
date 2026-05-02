<?php
$pdo=new PDO('mysql:host=127.0.0.1;port=3306;dbname=dina','root','0Charlemango23@');
foreach($pdo->query('DESCRIBE users') as $r){echo implode(' | ',[$r['Field'],$r['Type'],$r['Null'],$r['Default']??'NULL']),PHP_EOL;}
