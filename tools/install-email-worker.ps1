param([int]$BatchSize=50)
$ErrorActionPreference='Stop'
$project=(Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$phpCandidates=@('C:\xampp\php\php.exe','D:\xampp\php\php.exe')
$php=$phpCandidates|Where-Object{Test-Path -LiteralPath $_}|Select-Object -First 1
if(-not $php){throw 'ไม่พบ php.exe กรุณาติดตั้ง XAMPP หรือแก้รายการ path ในสคริปต์'}
$worker=Join-Path $project 'tools\process-email-queue.php'
$action=New-ScheduledTaskAction -Execute $php -Argument ('"{0}" {1}' -f $worker,$BatchSize) -WorkingDirectory $project
$trigger=New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(1) -RepetitionInterval (New-TimeSpan -Minutes 1)
$settings=New-ScheduledTaskSettingsSet -ExecutionTimeLimit (New-TimeSpan -Minutes 5) -MultipleInstances IgnoreNew -StartWhenAvailable
Register-ScheduledTask -TaskName 'KitchenMart Email Worker' -Action $action -Trigger $trigger -Settings $settings -Description 'ส่งอีเมลธุรกรรม KitchenMart ทุกหนึ่งนาที' -Force | Out-Null
$cleanup=Join-Path $project 'tools\cleanup-orphan-uploads.php'
$cleanupAction=New-ScheduledTaskAction -Execute $php -Argument ('"{0}"' -f $cleanup) -WorkingDirectory $project
$cleanupTrigger=New-ScheduledTaskTrigger -Daily -At '03:30'
Register-ScheduledTask -TaskName 'KitchenMart Upload Cleanup' -Action $cleanupAction -Trigger $cleanupTrigger -Settings $settings -Description 'ล้างรูปอัปโหลดที่ไม่มีข้อมูลอ้างอิงและเก่ากว่า 24 ชั่วโมง' -Force | Out-Null
Write-Host 'ติดตั้ง Email Worker และ Upload Cleanup เรียบร้อยแล้ว'
