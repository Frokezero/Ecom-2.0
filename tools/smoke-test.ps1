param([int]$Port=8799)
$ErrorActionPreference='Stop'
$project=(Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$php=@('C:\xampp\php\php.exe','D:\xampp\php\php.exe')|Where-Object{Test-Path -LiteralPath $_}|Select-Object -First 1
if(-not $php){throw 'ไม่พบ php.exe'}
$arguments='-S 127.0.0.1:{0} -t "{1}"' -f $Port,$project
$process=Start-Process -FilePath $php -ArgumentList $arguments -WorkingDirectory $project -WindowStyle Hidden -PassThru
try{
 Start-Sleep -Milliseconds 800
 foreach($path in @('/index.php','/products.php','/login.php','/register.php')){$response=Invoke-WebRequest -Uri "http://127.0.0.1:$Port$path" -UseBasicParsing -MaximumRedirection 0;if($response.StatusCode-ne 200){throw "$path returned $($response.StatusCode)"};if($response.Content-notmatch '<!DOCTYPE html>'){throw "$path did not return HTML"};if($response.Headers['Content-Security-Policy']-notmatch "script-src 'self' 'nonce-"){throw "$path has no nonce CSP"};if($response.Content-match '<script(?![^>]*nonce=)'){throw "$path contains a script without nonce"};Write-Host "PASS $path"}
 $apiStatus=0;try{Invoke-WebRequest -Uri "http://127.0.0.1:$Port/api/wishlist.php" -Method Get -UseBasicParsing|Out-Null;$apiStatus=200}catch{$apiStatus=[int]$_.Exception.Response.StatusCode};if($apiStatus-ne 405){throw "wishlist method guard returned $apiStatus"};Write-Host 'PASS API method guard'
 $sitemap=Invoke-WebRequest -Uri "http://127.0.0.1:$Port/sitemap.php" -UseBasicParsing;if($sitemap.StatusCode-ne 200-or$sitemap.Content-notmatch '<urlset'){throw 'sitemap failed'};Write-Host 'PASS sitemap'
 $chatHomeResponse=Invoke-WebRequest -Uri "http://127.0.0.1:$Port/index.php" -UseBasicParsing -SessionVariable chatSession;if($chatHomeResponse.Content-notmatch 'id="chatbotLauncher"'){throw 'chatbot launcher missing'};$chat=Invoke-WebRequest -Uri "http://127.0.0.1:$Port/api/chatbot.php?action=history" -UseBasicParsing -WebSession $chatSession;$chatJson=$chat.Content|ConvertFrom-Json;if($chatJson.status-ne'success'){throw 'chatbot history failed'};Write-Host 'PASS chatbot widget and history API'
}finally{if($process -and -not $process.HasExited){Stop-Process -Id $process.Id -Force}}
