$ErrorActionPreference='Stop'
$v4=(Invoke-RestMethod -Uri 'https://www.cloudflare.com/ips-v4' -Method Get).Trim() -split "`n"
$v6=(Invoke-RestMethod -Uri 'https://www.cloudflare.com/ips-v6' -Method Get).Trim() -split "`n"
$cidrs=@($v4+$v6|ForEach-Object{$_.Trim()}|Where-Object{$_})
if($cidrs.Count -lt 10){throw 'รายการ Cloudflare CIDR ไม่สมบูรณ์'}
Write-Output ('TRUSTED_PROXY_CIDRS=' + ($cidrs -join ','))
Write-Output 'คัดลอกค่าด้านบนไปยัง environment หรือ config/local.php และจำกัด Firewall ของ origin ให้รับเฉพาะ CIDR ชุดเดียวกัน'
