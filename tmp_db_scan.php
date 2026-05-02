<?php
$pass='0Charlemango23@';
$pdo=new PDO('mysql:host=127.0.0.1;port=3306','root',$pass);
$dbs=$pdo->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN);
foreach($dbs as $db){
  if(in_array($db,['information_schema','mysql','performance_schema','phpmyadmin','test'])) continue;
  try{
    $p=new PDO("mysql:host=127.0.0.1;port=3306;dbname=$db",'root',$pass);
    $has=$p->query("SHOW TABLES LIKE 'users'")->fetch();
    if(!$has) continue;
    $cnt=$p->query('SELECT COUNT(*) FROM users')->fetchColumn();
    echo "$db users=$cnt\n";
  }catch(Throwable $e){echo "$db ERR\n";}
}
