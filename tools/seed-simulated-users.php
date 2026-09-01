<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../config/database.php';
$db=(new Database())->getConnection();if(!$db){fwrite(STDERR,"Database unavailable\n");exit(1);}
$domain='simulation.kitchenmart.test';
if(in_array('--cleanup',$argv,true)){
    $stmt=$db->prepare('DELETE FROM users WHERE email LIKE ?');$stmt->execute(['%@'.$domain]);
    echo 'Removed simulated users: '.$stmt->rowCount()."\n";exit;
}
$firstNames=['กิตติ','ณัฐพล','พิมพ์ชนก','วรัญญา','ธนกฤต','ศิริพร','ภาคภูมิ','ชลธิชา','ปกรณ์','นภัสสร'];
$lastNames=['ใจดี','มั่นคง','สุขสันต์','ศรีทอง','แสงแก้ว','พูนทรัพย์','วงศ์คำ','บุญมี','จันทร์งาม','คำภู'];
$subdistricts=['ธาตุเชิงชุม','พังขว้าง','ขมิ้น','เชียงเครือ','ฮางโฮง'];
$districts=['เมืองสกลนคร','พรรณานิคม','วาริชภูมิ','กุสุมาลย์','สว่างแดนดิน'];
$postcodes=['47000','47130','47150','47210','47110'];
$created=0;$existing=0;$db->beginTransaction();
try{
    $find=$db->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
    $insert=$db->prepare("INSERT INTO users(username,email,password_hash,full_name,phone,address,preferred_payment_method,role,email_verified_at) VALUES(?,?,?,?,?,? ,?,'customer',NOW())");
    $address=$db->prepare('INSERT INTO user_addresses(user_id,label,recipient_name,phone,address_line,subdistrict,district,province,postal_code,is_default) VALUES(?,?,?,?,?,?,?,?,?,1)');
    for($i=1;$i<=100;$i++){
        $serial=str_pad((string)$i,3,'0',STR_PAD_LEFT);$username='simuser'.$serial;$email=$username.'@'.$domain;
        $find->execute([$email]);if($find->fetchColumn()){$existing++;continue;}
        $first=$firstNames[($i-1)%count($firstNames)];$last=$lastNames[(int)(($i-1)/count($firstNames))%count($lastNames)];$name=$first.' '.$last;
        $phone='08'.str_pad((string)(12000000+$i),8,'0',STR_PAD_LEFT);$area=($i-1)%count($districts);$line=(string)(10+$i).' หมู่ '.(1+($i%12));
        $insert->execute([$username,$email,password_hash('SimUser!2026-'.$serial,PASSWORD_DEFAULT),$name,$phone,$line.', '.$subdistricts[$area].', '.$districts[$area].', สกลนคร',$i%2?'promptpay':'cod']);
        $userId=(int)$db->lastInsertId();$address->execute([$userId,'บ้าน',$name,$phone,$line,$subdistricts[$area],$districts[$area],'สกลนคร',$postcodes[$area]]);$created++;
    }
    $db->commit();
}catch(Throwable $e){if($db->inTransaction())$db->rollBack();fwrite(STDERR,"Seed failed: ".$e->getMessage()."\n");exit(1);}
$count=$db->prepare('SELECT COUNT(*) FROM users WHERE email LIKE ?');$count->execute(['%@'.$domain]);
echo "Simulated users ready: ".$count->fetchColumn()." (created=$created existing=$existing)\n";
echo "Sample login: simuser001 / SimUser!2026-001\n";
