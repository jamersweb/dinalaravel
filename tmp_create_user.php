<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=dina', 'root', '0Charlemango23@');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$email='user1@gmail.com'; $pass='fwd123'; $name='Test User'; $now=date('Y-m-d H:i:s');
$hash=password_hash($pass,PASSWORD_BCRYPT);
$st=$pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1'); $st->execute([$email]); $id=$st->fetchColumn();
if($id){$u=$pdo->prepare('UPDATE users SET name=?,password=?,role=1,status="active",email_verified_at=?,updated_at=? WHERE id=?');$u->execute([$name,$hash,$now,$now,$id]);}
else{$i=$pdo->prepare('INSERT INTO users (name,email,email_verified_at,password,role,status,created_at,updated_at) VALUES (?,?,?,?,1,"active",?,?)');$i->execute([$name,$email,$now,$hash,$now,$now]);$id=$pdo->lastInsertId();}
echo $id,PHP_EOL;
