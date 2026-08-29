<?php
require_once __DIR__ . '/../config/config.php';

function mailAppUrl(): string {
    return rtrim(appConfig('APP_URL', BASE_URL), '/') . '/';
}

function smtpRead($socket): string {
    $response = '';
    while (($line = fgets($socket, 1024)) !== false) {
        $response .= $line;
        if (strlen($line) < 4 || $line[3] === ' ') break;
    }
    return $response;
}

function smtpCommand($socket, string $command, array $validCodes): string {
    if ($command !== '') fwrite($socket, $command . "\r\n");
    $response = smtpRead($socket);
    $code = (int)substr($response, 0, 3);
    if (!in_array($code, $validCodes, true)) throw new RuntimeException('SMTP error: ' . trim($response));
    return $response;
}

function sendAppMail(string $to, string $subject, string $html): void {
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Invalid recipient email');
    if (strtolower(appConfig('MAIL_TRANSPORT', 'smtp')) === 'log') {
        $logDir = __DIR__ . '/../.runtime-sessions';
        if (!is_dir($logDir)) mkdir($logDir, 0775, true);
        file_put_contents($logDir . '/mail.log', '[' . date('c') . "] TO: {$to}\nSUBJECT: {$subject}\n{$html}\n\n", FILE_APPEND | LOCK_EX);
        return;
    }

    $host = appConfig('SMTP_HOST', '');
    $port = (int)appConfig('SMTP_PORT', '587');
    $user = appConfig('SMTP_USER', '');
    // Gmail displays App Passwords in groups; whitespace is never part of the credential.
    $pass = preg_replace('/\s+/', '', appConfig('SMTP_PASS', ''));
    $encryption = strtolower(appConfig('SMTP_ENCRYPTION', 'tls'));
    $fromEmail = appConfig('SMTP_FROM_EMAIL', $user);
    $fromName = appConfig('SMTP_FROM_NAME', APP_NAME);
    if ($host === '' || $user === '' || $pass === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('ยังไม่ได้ตั้งค่า SMTP สำหรับส่งอีเมล');
    }

    $target = ($encryption === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $socket = @stream_socket_client($target, $errorNo, $errorText, 15, STREAM_CLIENT_CONNECT);
    if (!$socket) throw new RuntimeException('เชื่อมต่อ SMTP ไม่สำเร็จ: ' . $errorText);
    stream_set_timeout($socket, 15);
    try {
        smtpCommand($socket, '', [220]);
        smtpCommand($socket, 'EHLO kitchenmart.local', [250]);
        if ($encryption === 'tls') {
            smtpCommand($socket, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new RuntimeException('เปิดการเข้ารหัส TLS ไม่สำเร็จ');
            smtpCommand($socket, 'EHLO kitchenmart.local', [250]);
        }
        smtpCommand($socket, 'AUTH LOGIN', [334]);
        smtpCommand($socket, base64_encode($user), [334]);
        smtpCommand($socket, base64_encode($pass), [235]);
        smtpCommand($socket, 'MAIL FROM:<' . str_replace(["\r", "\n"], '', $fromEmail) . '>', [250]);
        smtpCommand($socket, 'RCPT TO:<' . str_replace(["\r", "\n"], '', $to) . '>', [250, 251]);
        smtpCommand($socket, 'DATA', [354]);
        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'From: =?UTF-8?B?' . base64_encode($fromName) . '?= <' . $fromEmail . '>',
            'To: <' . $to . '>',
            'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@kitchenmart.local>',
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit'
        ];
        $payload = implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n.", "\n..", $html) . "\r\n.";
        smtpCommand($socket, $payload, [250]);
        smtpCommand($socket, 'QUIT', [221]);
    } finally {
        fclose($socket);
    }
}

function verificationEmailHtml(string $safeName, string $safeUrl): string {
    return '<!doctype html><html lang="th"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>'
        . '<body style="margin:0;padding:0;background:#f4f1e9;font-family:Arial,Tahoma,sans-serif;color:#23332e">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f4f1e9"><tr><td align="center" style="padding:32px 14px">'
        . '<table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border:1px solid #e3dfd2">'
        . '<tr><td style="padding:26px 32px;background:#173f32;color:#ffffff"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"><tr><td style="font-size:24px;font-weight:700;letter-spacing:-.4px">KitchenMart</td><td align="right" style="font-size:11px;letter-spacing:1.2px;color:#c3d5cc">ACCOUNT SECURITY</td></tr></table></td></tr>'
        . '<tr><td style="padding:38px 32px 30px"><div style="width:48px;height:48px;line-height:48px;text-align:center;border-radius:24px;background:#eaf2ed;color:#173f32;font-size:24px;margin-bottom:18px">✉</div>'
        . '<p style="margin:0 0 9px;color:#b45c28;font-size:11px;font-weight:700;letter-spacing:1.4px">ONE LAST STEP</p>'
        . '<h1 style="margin:0 0 15px;font-size:28px;line-height:1.25;color:#173f32;font-family:Georgia,\'Times New Roman\',serif">ยืนยันอีเมลของคุณ</h1>'
        . '<p style="margin:0 0 12px;font-size:15px;line-height:1.75;color:#53635d">สวัสดี ' . $safeName . '</p>'
        . '<p style="margin:0 0 25px;font-size:15px;line-height:1.75;color:#53635d">กดปุ่มด้านล่างเพื่อยืนยันอีเมลและเปิดใช้งานบัญชี KitchenMart ของคุณ ลิงก์นี้ใช้ได้ภายใน <strong style="color:#173f32">30 นาที</strong></p>'
        . '<table role="presentation" cellspacing="0" cellpadding="0" border="0"><tr><td style="border-radius:4px;background:#173f32"><a href="' . $safeUrl . '" style="display:inline-block;padding:14px 23px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700">ยืนยันอีเมลของฉัน&nbsp; →</a></td></tr></table>'
        . '<div style="margin-top:31px;padding:16px 18px;background:#f8f7f2;border-left:3px solid #d88b4d"><p style="margin:0;font-size:12px;line-height:1.65;color:#68746f">หากคุณไม่ได้สมัครสมาชิก KitchenMart คุณไม่ต้องดำเนินการใด ๆ และสามารถละเว้นอีเมลฉบับนี้ได้</p></div>'
        . '<p style="margin:27px 0 0;font-size:11px;line-height:1.65;color:#7b857f">ปุ่มไม่ทำงาน? คัดลอกลิงก์นี้ไปวางในเบราว์เซอร์:<br><a href="' . $safeUrl . '" style="color:#28614d;word-break:break-all">' . $safeUrl . '</a></p>'
        . '</td></tr><tr><td style="padding:21px 32px;background:#173f32;text-align:center;color:#b9cec3;font-size:11px;line-height:1.6">KitchenMart · ของดีสำหรับทุกครัว<br>อีเมลนี้ถูกส่งเพื่อยืนยันความเป็นเจ้าของบัญชีของคุณ</td></tr>'
        . '</table></td></tr></table></body></html>';
}

function sendVerificationEmail(PDO $db, int $userId, string $email, string $fullName): void {
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', time() + 1800);
    $stmt = $db->prepare('UPDATE users SET email_verification_token_hash=?,email_verification_expires_at=?,email_verification_sent_at=NOW() WHERE id=? AND email_verified_at IS NULL');
    $stmt->execute([$tokenHash, $expiresAt, $userId]);
    $safeName = htmlspecialchars($fullName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeUrl = htmlspecialchars(mailAppUrl() . 'verify-email.php?token=' . rawurlencode($token), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    sendAppMail($email, 'ยืนยันอีเมลสำหรับบัญชี KitchenMart', verificationEmailHtml($safeName, $safeUrl));
}

function sendPasswordResetEmail(string $email, string $fullName, string $token): void {
    $safeName=htmlspecialchars($fullName,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    $url=mailAppUrl().'reset-password.php?token='.rawurlencode($token);
    $safeUrl=htmlspecialchars($url,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    $html='<!doctype html><html lang="th"><meta charset="UTF-8"><body style="margin:0;background:#f4f1e9;font-family:Arial,Tahoma,sans-serif;color:#23332e"><table width="100%" role="presentation"><tr><td align="center" style="padding:32px 14px"><table width="600" role="presentation" style="max-width:600px;background:#fff;border:1px solid #e3dfd2"><tr><td style="padding:26px 32px;background:#173f32;color:#fff;font-size:24px;font-weight:700">KitchenMart</td></tr><tr><td style="padding:38px 32px"><p style="color:#b45c28;font-size:12px;font-weight:700">ACCOUNT SECURITY</p><h1 style="font-family:Georgia,serif;color:#173f32">ตั้งรหัสผ่านใหม่</h1><p>สวัสดี '.$safeName.' มีคำขอตั้งรหัสผ่านใหม่สำหรับบัญชีของคุณ ลิงก์นี้ใช้ได้ 30 นาทีและใช้ได้เพียงครั้งเดียว</p><p style="margin:28px 0"><a href="'.$safeUrl.'" style="background:#173f32;color:#fff;padding:14px 22px;text-decoration:none;font-weight:700">ตั้งรหัสผ่านใหม่ →</a></p><p style="font-size:12px;color:#68746f">หากคุณไม่ได้เป็นผู้ร้องขอ ให้ละเว้นอีเมลนี้และรหัสผ่านเดิมจะยังใช้งานได้</p></td></tr></table></td></tr></table></body></html>';
    sendAppMail($email,'ตั้งรหัสผ่านใหม่สำหรับบัญชี KitchenMart',$html);
}
function sendTwoFactorCode(string $email,string $fullName,string $code):void{
    $safeName=htmlspecialchars($fullName,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');$safeCode=htmlspecialchars($code,ENT_QUOTES,'UTF-8');
    sendAppMail($email,'รหัสยืนยันการเข้าสู่ระบบ KitchenMart','<!doctype html><html lang="th"><meta charset="UTF-8"><body style="background:#f4f1e9;font-family:Arial,Tahoma,sans-serif;padding:30px"><div style="max-width:560px;margin:auto;background:#fff;padding:32px;border-top:8px solid #173f32"><h1 style="color:#173f32">ยืนยันการเข้าสู่ระบบ</h1><p>สวัสดี '.$safeName.' รหัสยืนยันของคุณคือ</p><div style="font-size:34px;font-weight:800;letter-spacing:9px;padding:20px;background:#f4f1e9;text-align:center">'.$safeCode.'</div><p>รหัสใช้ได้ 10 นาที หากคุณไม่ได้เข้าสู่ระบบ กรุณาเปลี่ยนรหัสผ่านทันที</p></div></body></html>');
}
