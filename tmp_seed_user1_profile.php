<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=dina', 'root', '0Charlemango23@');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$id = 2; $now=date('Y-m-d H:i:s');
$st=$pdo->prepare('SELECT id FROM user_details WHERE user_id=?'); $st->execute([$id]);
if(!$st->fetchColumn()){
  $i=$pdo->prepare('INSERT INTO user_details (user_id,name,phone,Lastname,country,gender,DOB,subscription,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)');
  $i->execute([$id,'User','0000000000','One','PK','male','1995-01-01',0,$now,$now]);
}
$st=$pdo->prepare('SELECT id FROM user_settings WHERE user_id=?'); $st->execute([$id]);
if(!$st->fetchColumn()){
  $i=$pdo->prepare('INSERT INTO user_settings (user_id,language,created_at,updated_at) VALUES (?,?,?,?)');
  $i->execute([$id,'en',$now,$now]);
}
echo "ok\n";
