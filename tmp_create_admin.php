<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=dina', 'root', '0Charlemango23@');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$email = 'dina@gmail.com';
$plain = 'fwd123';
$name = 'Dina Admin';
$now = date('Y-m-d H:i:s');
$hash = password_hash($plain, PASSWORD_BCRYPT);

$st = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$st->execute([$email]);
$id = $st->fetchColumn();

if ($id) {
    $u = $pdo->prepare('UPDATE users SET name=?, password=?, role=2, status="active", email_verified_at=?, updated_at=? WHERE id=?');
    $u->execute([$name, $hash, $now, $now, $id]);
    echo "updated_user:$id\n";
} else {
    $i = $pdo->prepare('INSERT INTO users (name,email,email_verified_at,password,role,status,created_at,updated_at) VALUES (?,?,?,?,2,"active",?,?)');
    $i->execute([$name, $email, $now, $hash, $now, $now]);
    $id = $pdo->lastInsertId();
    echo "created_user:$id\n";
}

$st = $pdo->prepare('SELECT id FROM user_details WHERE user_id=? LIMIT 1');
$st->execute([$id]);
$detailId = $st->fetchColumn();
if ($detailId) {
    $u = $pdo->prepare('UPDATE user_details SET name=?, Lastname=?, phone=?, country=?, gender=?, DOB=?, subscription=0, updated_at=? WHERE user_id=?');
    $u->execute(['Dina','Admin','0000000000','PK','female','1990-01-01',$now,$id]);
    echo "updated_user_detail:$detailId\n";
} else {
    $i = $pdo->prepare('INSERT INTO user_details (user_id,name,phone,Lastname,country,gender,DOB,subscription,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $i->execute([$id,'Dina','0000000000','Admin','PK','female','1990-01-01',0,$now,$now]);
    echo "created_user_detail\n";
}

$st = $pdo->prepare('SELECT id FROM user_settings WHERE user_id=? LIMIT 1');
$st->execute([$id]);
$setId = $st->fetchColumn();
if (!$setId) {
    $i = $pdo->prepare('INSERT INTO user_settings (user_id, language, created_at, updated_at) VALUES (?,"en",?,?)');
    $i->execute([$id,$now,$now]);
    echo "created_user_settings\n";
}

echo "done\n";
