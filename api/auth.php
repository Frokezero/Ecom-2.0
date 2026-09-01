<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/security_monitor.php';
require_once __DIR__ . '/../includes/behavior_analytics.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse('error','อนุญาตเฉพาะ POST',[],405);
requireCsrf();
$action=$_POST['action'] ?? '';

if ($action === 'logout') {
    $_SESSION=[];
    if (ini_get('session.use_cookies')) {
        $p=session_get_cookie_params(); setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']);
    }
    session_destroy();
    jsonResponse('success','ออกจากระบบแล้ว',['redirect'=>BASE_URL.'index.php']);
}

$db=(new Database())->getConnection();
if (!$db) jsonResponse('error','ไม่สามารถเชื่อมต่อฐานข้อมูลได้',[],503);
enforceSecurityBlock($db,isLoggedIn()?(int)$_SESSION['user_id']:null,true);
enforceRequestRate($db,'api.auth',60,60,isLoggedIn()?(int)$_SESSION['user_id']:null);
if ($action === 'resend_verification') {
    $email=strtolower(trim($_POST['email'] ?? ''));
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) jsonResponse('error','กรุณากรอกอีเมลให้ถูกต้อง',['field'=>'email'],422);
    $stmt=$db->prepare('SELECT id,email,full_name,email_verified_at,email_verification_sent_at FROM users WHERE email=? LIMIT 1');$stmt->execute([$email]);$user=$stmt->fetch();
    if($user && !$user['email_verified_at']){
        if($user['email_verification_sent_at'] && time()-strtotime($user['email_verification_sent_at'])<60)jsonResponse('error','เพิ่งส่งอีเมลไป กรุณารอประมาณ 1 นาทีก่อนส่งซ้ำ',['retry_after'=>60-(time()-strtotime($user['email_verification_sent_at']))],429);
        try{sendVerificationEmail($db,(int)$user['id'],$user['email'],$user['full_name']);}
        catch(Throwable $e){$db->prepare('UPDATE users SET email_verification_sent_at=NULL WHERE id=?')->execute([(int)$user['id']]);jsonResponse('error','ระบบส่งอีเมลยังไม่พร้อม กรุณาตรวจการตั้งค่า SMTP แล้วลองใหม่',[],503);}
    }
    jsonResponse('success','หากอีเมลนี้รอยืนยัน ระบบได้ส่งลิงก์ฉบับใหม่แล้ว');
}
if($action==='verify_2fa'){
    $token=(string)($_SESSION['two_factor_challenge']??'');$code=preg_replace('/\D/','',(string)($_POST['code']??''));if($token===''||strlen($code)!==6)jsonResponse('error','กรุณากรอกรหัส 6 หลัก',[],422);
    try{$db->beginTransaction();$stmt=$db->prepare('SELECT c.*,u.username,u.email,u.full_name,u.role FROM auth_challenges c JOIN users u ON u.id=c.user_id WHERE c.token_hash=? AND c.used_at IS NULL AND c.expires_at>NOW() LIMIT 1 FOR UPDATE');$stmt->execute([hash('sha256',$token)]);$c=$stmt->fetch();if(!$c||$c['attempts']>=5||!password_verify($code,$c['code_hash'])){if($c)$db->prepare('UPDATE auth_challenges SET attempts=attempts+1 WHERE id=?')->execute([(int)$c['id']]);$db->commit();jsonResponse('error','รหัสไม่ถูกต้องหรือหมดอายุ',[],401);}$db->prepare('UPDATE auth_challenges SET used_at=NOW() WHERE id=?')->execute([(int)$c['id']]);$db->commit();unset($_SESSION['two_factor_challenge']);session_regenerate_id(true);$_SESSION['user_id']=(int)$c['user_id'];$_SESSION['username']=$c['username'];$_SESSION['full_name']=$c['full_name'];$_SESSION['email']=$c['email'];$_SESSION['user_role']=$c['role'];auditLog($db,'security.2fa.verify','user',(int)$c['user_id']);jsonResponse('success','ยืนยันตัวตนสำเร็จ',['redirect'=>BASE_URL.($c['role']==='admin'?'admin/index.php':'index.php')]);}catch(Throwable $e){if($db->inTransaction())$db->rollBack();jsonResponse('error','ไม่สามารถยืนยันตัวตนได้',[],500);}
}
if ($action === 'register') {
    $username=trim($_POST['username'] ?? ''); $email=strtolower(trim($_POST['email'] ?? '')); $password=$_POST['password'] ?? '';
    $passwordConfirm=$_POST['password_confirm'] ?? '';
    $fullName=trim($_POST['full_name'] ?? ''); $phone=trim($_POST['phone'] ?? '');
    $reservedUsernames=['admin','administrator','root','system','support','staff','moderator','kitchenmart','official','null','undefined'];
    if (!preg_match('/^[A-Za-z][A-Za-z0-9]{2,29}$/',$username)) jsonResponse('error','ชื่อผู้ใช้ต้องมี 3–30 ตัว เริ่มด้วยตัวอักษรอังกฤษ และใช้ได้เฉพาะ A–Z กับตัวเลขเท่านั้น ห้ามเว้นวรรคหรือใช้อักขระพิเศษ',['field'=>'username'],422);
    if (in_array(strtolower($username),$reservedUsernames,true)) jsonResponse('error','ชื่อผู้ใช้นี้เป็นชื่อสงวนของระบบ กรุณาเลือกชื่ออื่น',['field'=>'username'],422);
    if (preg_match('/[\x00-\x1F\x7F\r\n]/',$email) || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>100) jsonResponse('error','กรุณากรอกอีเมลจริงให้ถูกต้อง และห้ามใส่ช่องว่างหรืออักขระควบคุม',['field'=>'email'],422);
    $fullName=preg_replace('/\s+/u',' ',$fullName) ?? $fullName;
    if (mb_strlen($fullName)<2 || mb_strlen($fullName)>100 || !preg_match("/^[\\p{L}\\p{M} .'-]+$/u",$fullName)) jsonResponse('error','ชื่อ-นามสกุลใช้ได้เฉพาะตัวอักษร ช่องว่าง จุด ขีดกลาง และเครื่องหมายอัญประกาศเท่านั้น',['field'=>'full_name'],422);
    if ($phone!=='' && !preg_match('/^(?:0[0-9]{8,9}|\+66[0-9]{8,9})$/',$phone)) jsonResponse('error','เบอร์โทรใช้ได้เฉพาะตัวเลข 9–10 หลัก หรือรูปแบบ +66 โดยห้ามมีช่องว่างและขีดกลาง',['field'=>'phone'],422);
    $emailLocal=explode('@',$email,2)[0]??'';$commonPasswords=['password123','1234567890','qwerty12345','admin12345'];
    if (strlen($password)<10 || strlen($password)>72 || !preg_match('/[A-Z]/',$password) || !preg_match('/[a-z]/',$password) || !preg_match('/\d/',$password) || preg_match('/\s|[\x00-\x1F\x7F]/',$password)) jsonResponse('error','รหัสผ่านต้องมี 10–72 ตัว และมีตัวพิมพ์ใหญ่ ตัวพิมพ์เล็ก และตัวเลข โดยห้ามมีช่องว่าง',['field'=>'password'],422);
    if (in_array(strtolower($password),$commonPasswords,true) || str_contains(strtolower($password),strtolower($username)) || ($emailLocal!==''&&str_contains(strtolower($password),strtolower($emailLocal)))) jsonResponse('error','รหัสผ่านคาดเดาง่ายเกินไป และต้องไม่มีชื่อผู้ใช้หรือชื่ออีเมลอยู่ภายใน',['field'=>'password'],422);
    if (!hash_equals($password,$passwordConfirm)) jsonResponse('error','รหัสผ่านทั้งสองช่องไม่ตรงกัน',['field'=>'password_confirm'],422);
    if (($_POST['accept_terms'] ?? '')!=='1') jsonResponse('error','กรุณายอมรับเงื่อนไขการใช้งาน',['field'=>'accept_terms'],422);
    $stmt=$db->prepare('SELECT id FROM users WHERE username=? LIMIT 1');$stmt->execute([$username]);if($stmt->fetch())jsonResponse('error','ชื่อผู้ใช้นี้ถูกใช้งานแล้ว กรุณาเลือกชื่อผู้ใช้อื่น',['field'=>'username'],409);
    $stmt=$db->prepare('SELECT id FROM users WHERE email=? LIMIT 1');$stmt->execute([$email]);if($stmt->fetch())jsonResponse('error','อีเมลนี้ถูกใช้สมัครสมาชิกแล้ว กรุณาเข้าสู่ระบบหรือใช้อีเมลอื่น',['field'=>'email'],409);
    try{$stmt=$db->prepare("INSERT INTO users (username,email,password_hash,full_name,phone,address,role,email_verified_at) VALUES (?,?,?,?,?,'','customer',NULL)");$stmt->execute([$username,$email,password_hash($password,PASSWORD_DEFAULT),$fullName,$phone]);}
    catch(PDOException $e){if((int)($e->errorInfo[1]??0)===1062)jsonResponse('error','ชื่อผู้ใช้หรืออีเมลนี้ถูกใช้แล้ว กรุณาเข้าสู่ระบบหรือเลือกข้อมูลอื่น',['field'=>'username'],409);throw $e;}
    $userId=(int)$db->lastInsertId();$delivery='sent';
    try{sendVerificationEmail($db,$userId,$email,$fullName);}
    catch(Throwable $e){$delivery='failed';$db->prepare('UPDATE users SET email_verification_sent_at=NULL WHERE id=?')->execute([$userId]);}
    $redirect=BASE_URL.'check-email.php?email='.rawurlencode($email).($delivery==='failed'?'&delivery=failed':'');
    jsonResponse('success',$delivery==='sent'?'สมัครสมาชิกแล้ว กรุณาตรวจอีเมลเพื่อยืนยันบัญชี':'สร้างบัญชีแล้ว แต่ยังส่งอีเมลไม่ได้ กรุณาตั้งค่า SMTP และกดส่งซ้ำ',['redirect'=>$redirect,'delivery'=>$delivery]);
}
if ($action === 'login') {
    $identity=trim($_POST['username_email'] ?? ''); $password=$_POST['password'] ?? '';
    $rateKey=appConfig('APP_KEY','kitchenmart-local-rate-key');
    $identityHash=hash_hmac('sha256',mb_strtolower($identity),$rateKey);
    $ip=(string)($_SERVER['HTTP_CF_CONNECTING_IP']??$_SERVER['REMOTE_ADDR']??'unknown');
    $ipHash=hash_hmac('sha256',$ip,$rateKey);
    $identityRule=securityRule($db,'login_fail_identity',['threshold_count'=>10,'window_seconds'=>900,'risk_points'=>10,'block_seconds'=>900]);
    // Never allow a stale database rule to bypass the ten-attempt alert threshold.
    $identityRule['threshold_count']=max(10,(int)$identityRule['threshold_count']);
    $ipRule=securityRule($db,'login_fail_ip',['threshold_count'=>20,'window_seconds'=>900,'risk_points'=>10,'block_seconds'=>3600]);$window=max($identityRule['window_seconds'],$ipRule['window_seconds']);
    $limit=$db->prepare("SELECT SUM(identifier_hash=? AND was_successful=0) identity_failures,SUM(ip_hash=? AND was_successful=0) ip_failures FROM login_attempts WHERE attempted_at>DATE_SUB(NOW(),INTERVAL ? SECOND)");
    $limit->execute([$identityHash,$ipHash,$window]);$rate=$limit->fetch();
    $identityFailures=(int)($rate['identity_failures']??0);$ipFailures=(int)($rate['ip_failures']??0);
    $notifyIdentity=function(string $stage,int $failures)use($db,$identity,$identityHash,$ipFailures,$window):void{try{$eventType=$stage==='warning'?'login.warning':'login.threshold';$marker='%"identity_hash":"'.$identityHash.'"%';$seen=$db->prepare("SELECT id FROM security_events WHERE event_type=? AND created_at>DATE_SUB(NOW(),INTERVAL ? SECOND) AND metadata_json LIKE ? LIMIT 1");$seen->execute([$eventType,$window,$marker]);if($seen->fetchColumn())return;$account=$db->prepare('SELECT id FROM users WHERE username=? OR email=? LIMIT 1');$account->execute([$identity,$identity]);$accountId=(int)($account->fetchColumn()?:0);$isWarning=$stage==='warning';$title=$isWarning?'แจ้งเตือนความปลอดภัย: ใส่รหัสผิด 5 ครั้ง':'บัญชีถูกพักชั่วคราว: ใส่รหัสผิด 10 ครั้ง';$body=$isWarning?'มีการพยายามเข้าสู่บัญชีของคุณผิด 5 ครั้งภายใน 15 นาที หากไม่ใช่คุณ แนะนำให้เปลี่ยนรหัสผ่านทันที ระบบยังไม่ได้บล็อกบัญชี':'มีการพยายามเข้าสู่บัญชีของคุณผิดครบ 10 ครั้งภายใน 15 นาที ระบบจึงพักการเข้าสู่ชื่อผู้ใช้นี้ 15 นาที หากไม่ใช่คุณ กรุณาเปลี่ยนรหัสผ่าน';recordSecurityEvent($db,$eventType,0,$accountId?:null,['identity_hash'=>$identityHash,'identity_failures'=>$failures,'ip_failures'=>$ipFailures],$isWarning?'email_warning':'identity_blocked');if($accountId)createNotification($db,$accountId,'security',$title,$body,BASE_URL.'forgot-password.php',true);createRoleNotification($db,'admin','security',$title,$body,BASE_URL.'admin/security-center.php',true);}catch(Throwable $ignored){}};
    if($ipFailures>=$ipRule['threshold_count']){$blockSeconds=$ipRule['block_seconds'];recordSecurityEvent($db,'request.rate_limit',$ipRule['risk_points'],null,['endpoint'=>'login','identity_failures'=>$identityFailures,'ip_failures'=>$ipFailures],'ip_blocked');securityBlock($db,'ip',$ipHash,null,'เข้าสู่ระบบผิดหลายบัญชีเกินกำหนด',70,$blockSeconds);jsonResponse('error','เครือข่ายนี้พยายามเข้าสู่ระบบผิดหลายบัญชี ระบบจึงบล็อกชั่วคราว',['retry_after'=>$blockSeconds,'failed_attempts'=>$ipFailures],429);}
    if($identityFailures>=$identityRule['threshold_count']){$notifyIdentity('blocked',$identityFailures);jsonResponse('error','ชื่อผู้ใช้นี้กรอกรหัสผิดครบ 10 ครั้ง กรุณารอ 15 นาทีหรือใช้เมนูลืมรหัสผ่าน บัญชีอื่นยังเข้าสู่ระบบได้ตามปกติ',['retry_after'=>$identityRule['block_seconds'],'failed_attempts'=>$identityFailures,'block_scope'=>'identity'],429);}
    $attemptMap=$_SESSION['login_attempts']??[];if(isset($attemptMap['count']))$attemptMap=[];$attempts=$attemptMap[$identityHash]??['count'=>0,'last'=>0];
    if(time()-(int)$attempts['last']>=60)$attempts=['count'=>0,'last'=>0];
    if ($identity==='' || $password==='') jsonResponse('error','กรุณากรอกชื่อผู้ใช้หรืออีเมล และรหัสผ่านให้ครบ',['field'=>$identity===''?'username_email':'password'],422);
    $stmt=$db->prepare('SELECT * FROM users WHERE username=? OR email=? LIMIT 1'); $stmt->execute([$identity,$identity]); $user=$stmt->fetch();
    if (!$user || !password_verify($password,$user['password_hash'])) {$db->prepare('INSERT INTO login_attempts(identifier_hash,ip_hash,was_successful) VALUES(?,?,0)')->execute([$identityHash,$ipHash]);$attemptMap[$identityHash]=['count'=>(int)$attempts['count']+1,'last'=>time()];$_SESSION['login_attempts']=$attemptMap;$newFailures=$identityFailures+1;recordSecurityEvent($db,'login.failed',10,$user?(int)$user['id']:null,['identifier_hash'=>$identityHash,'attempt'=>$newFailures],'monitored');$activity=behaviorLog($db,['user_id'=>$user?(int)$user['id']:null,'action'=>'login.failed','status'=>401,'login_success'=>0]);behaviorEvaluateRuntime($db,$activity,$user?(int)$user['id']:null,date('Y-m-d H:i:s'));if($newFailures===5){$notifyIdentity('warning',$newFailures);jsonResponse('error','ชื่อผู้ใช้ อีเมล หรือรหัสผ่านไม่ถูกต้อง ระบบส่งอีเมลเตือนความปลอดภัยแล้วหากมีบัญชีนี้อยู่',['failed_attempts'=>$newFailures,'warning_sent'=>true],401);}if($newFailures>=$identityRule['threshold_count']){$notifyIdentity('blocked',$newFailures);jsonResponse('error','ชื่อผู้ใช้นี้กรอกรหัสผิดครบ 10 ครั้ง ระบบพักการเข้าสู่ชื่อนี้ 15 นาทีและส่งอีเมลแจ้งแล้ว',['retry_after'=>$identityRule['block_seconds'],'failed_attempts'=>$newFailures,'block_scope'=>'identity'],429);}jsonResponse('error','ชื่อผู้ใช้ อีเมล หรือรหัสผ่านไม่ถูกต้อง',[],401);}
    $requestHost=$_SERVER['HTTP_HOST'] ?? '';
    $isLocalRequest=(bool)preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/i',$requestHost);
    if($user['role']==='admin' && !$isLocalRequest && appConfig('ALLOW_PUBLIC_ADMIN','0')!=='1')jsonResponse('error','ปิดการเข้าสู่ระบบผู้ดูแลผ่านลิงก์สาธารณะเพื่อความปลอดภัย กรุณาเข้าสู่ระบบจากเครื่องเซิร์ฟเวอร์',[],403);
    if (!$user['email_verified_at']) jsonResponse('error','กรุณายืนยันอีเมลก่อนเข้าสู่ระบบ',['field'=>'username_email','email'=>$user['email'],'verification_required'=>true],403);
    if(!empty($user['two_factor_enabled'])){$code=(string)random_int(100000,999999);$token=bin2hex(random_bytes(32));$db->prepare('UPDATE auth_challenges SET used_at=NOW() WHERE user_id=? AND used_at IS NULL')->execute([(int)$user['id']]);$db->prepare('INSERT INTO auth_challenges(user_id,token_hash,code_hash,expires_at) VALUES(?,?,?,?)')->execute([(int)$user['id'],hash('sha256',$token),password_hash($code,PASSWORD_DEFAULT),date('Y-m-d H:i:s',time()+600)]);try{sendTwoFactorCode($user['email'],$user['full_name'],$code);}catch(Throwable $e){jsonResponse('error','ไม่สามารถส่งรหัสยืนยันได้ กรุณาติดต่อผู้ดูแลระบบ',[],503);}$_SESSION['two_factor_challenge']=$token;jsonResponse('success','ส่งรหัสยืนยันไปยังอีเมลแล้ว',['redirect'=>BASE_URL.'verify-two-factor.php','two_factor_required'=>true]);}
    unset($attemptMap[$identityHash]);$_SESSION['login_attempts']=$attemptMap;
    $db->prepare('INSERT INTO login_attempts(identifier_hash,ip_hash,was_successful) VALUES(?,?,1)')->execute([$identityHash,$ipHash]);
    $known=$db->prepare('SELECT COUNT(*) FROM user_login_locations WHERE user_id=?');$known->execute([(int)$user['id']]);$hasHistory=(int)$known->fetchColumn()>0;$location=$db->prepare('SELECT id FROM user_login_locations WHERE user_id=? AND ip_hash=?');$location->execute([(int)$user['id'],$ipHash]);$knownHere=(bool)$location->fetchColumn();if($hasHistory&&!$knownHere){recordSecurityEvent($db,'login.new_ip',20,(int)$user['id'],['country_code'=>$_SERVER['HTTP_CF_IPCOUNTRY']??null],'verification_recommended');createNotification($db,(int)$user['id'],'security','พบการเข้าสู่ระบบจาก IP ใหม่','บัญชีของคุณเข้าสู่ระบบจากเครือข่ายใหม่ หากไม่ใช่คุณกรุณาเปลี่ยนรหัสผ่านทันที',BASE_URL.'profile.php');}$country=substr(strtoupper((string)($_SERVER['HTTP_CF_IPCOUNTRY']??'')),0,2)?:null;$agentHash=hash('sha256',(string)($_SERVER['HTTP_USER_AGENT']??''));$remember=$db->prepare('INSERT INTO user_login_locations(user_id,ip_hash,country_code,user_agent_hash) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE login_count=login_count+1,last_seen_at=NOW(),country_code=VALUES(country_code),user_agent_hash=VALUES(user_agent_hash)');$remember->execute([(int)$user['id'],$ipHash,$country,$agentHash]);
    recordSecurityEvent($db,'login.success',0,(int)$user['id'],['role'=>$user['role']],'allowed');
    $activity=behaviorLog($db,['user_id'=>(int)$user['id'],'action'=>'login.success','status'=>200,'login_success'=>1]);behaviorEvaluateRuntime($db,$activity,(int)$user['id'],date('Y-m-d H:i:s'));
    $db->prepare('DELETE FROM login_attempts WHERE attempted_at<DATE_SUB(NOW(),INTERVAL 30 DAY)')->execute();
    session_regenerate_id(true); $_SESSION['user_id']=(int)$user['id']; $_SESSION['username']=$user['username'];
    $_SESSION['full_name']=$user['full_name']; $_SESSION['email']=$user['email']; $_SESSION['user_role']=$user['role'];
    $redirect=BASE_URL.($user['role']==='admin'?'admin/index.php':'index.php');
    $requested=$_SESSION['redirect_url'] ?? '';unset($_SESSION['redirect_url']);
    if(is_string($requested)&&str_starts_with($requested,'/')&&!str_starts_with($requested,'//')&&($user['role']==='admin'||!str_contains($requested,'/admin/')))$redirect=$requested;
    jsonResponse('success','เข้าสู่ระบบสำเร็จ',['redirect'=>$redirect]);
}
jsonResponse('error','คำสั่งไม่ถูกต้อง',[],400);
