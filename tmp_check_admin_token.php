<?php
$pdo=new PDO('mysql:host=127.0.0.1;port=3306;dbname=dina','root','0Charlemango23@');
$r=$pdo->query("SELECT id,email,api_token FROM users WHERE email='dina@gmail.com'")->fetch(PDO::FETCH_ASSOC);
var_export($r);
