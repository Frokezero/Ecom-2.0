param(
    [string]$BaseUrl = 'http://localhost:8080',
    [int]$Requests = 100,
    [int]$Concurrency = 10,
    [int]$P95TargetMs = 500,
    [double]$MaxErrorPercent = 1
)
$ErrorActionPreference = 'Stop'
$target = $BaseUrl.TrimEnd('/') + '/index.php'
$samples = [System.Collections.Generic.List[double]]::new()
$errors = 0
$wall = [Diagnostics.Stopwatch]::StartNew()
for ($offset = 0; $offset -lt $Requests; $offset += $Concurrency) {
    $count = [Math]::Min($Concurrency, $Requests - $offset)
    $jobs = 1..$count | ForEach-Object { Start-Job -ScriptBlock { param($url) $s=[Diagnostics.Stopwatch]::StartNew(); try{$r=Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 15;$s.Stop();[pscustomobject]@{Ms=$s.Elapsed.TotalMilliseconds;Ok=($r.StatusCode -eq 200)}}catch{$s.Stop();[pscustomobject]@{Ms=$s.Elapsed.TotalMilliseconds;Ok=$false}} } -ArgumentList $target }
    $results = $jobs | Wait-Job | Receive-Job
    $jobs | Remove-Job
    foreach ($result in $results) { $samples.Add([double]$result.Ms); if (!$result.Ok) { $errors++ } }
}
$wall.Stop(); $sorted = $samples | Sort-Object
$p95 = $sorted[[Math]::Min($sorted.Count-1,[Math]::Ceiling($sorted.Count*.95)-1)]
$average = ($samples | Measure-Object -Average).Average
$errorPercent = if($Requests){$errors*100/$Requests}else{100}
$rps = if($wall.Elapsed.TotalSeconds){$Requests/$wall.Elapsed.TotalSeconds}else{0}
$passed = $p95 -le $P95TargetMs -and $errorPercent -le $MaxErrorPercent
[pscustomobject]@{Url=$target;Requests=$Requests;Concurrency=$Concurrency;AverageMs=[Math]::Round($average,2);P95Ms=[Math]::Round($p95,2);RequestsPerSecond=[Math]::Round($rps,2);Errors=$errors;ErrorPercent=[Math]::Round($errorPercent,2);Passed=$passed} | Format-List
if (!$passed) { exit 1 }
