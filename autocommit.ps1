$projectPath = "c:\Users\N\Desktop\new_app"
$debounceSeconds = 5
$periodicMinutes = 5

$shared = [hashtable]::Synchronized(@{
    LastChange = $null
    LastCommit = Get-Date
})

function Invoke-AutoCommit {
    param([string]$reason)
    Push-Location $projectPath
    $status = git status --porcelain 2>&1
    if ($status) {
        git add .
        $msg = "auto: $(Get-Date -Format 'yyyy-MM-dd HH:mm') [$reason]"
        git commit -m $msg
        $shared.LastCommit = Get-Date
        Write-Host "$(Get-Date -Format 'HH:mm:ss') OK  $msg" -ForegroundColor Green
    }
    Pop-Location
}

$watcher = New-Object System.IO.FileSystemWatcher $projectPath
$watcher.IncludeSubdirectories = $true
$watcher.Filter = "*"
$watcher.EnableRaisingEvents = $true

$onChange = {
    $p = $Event.SourceEventArgs.FullPath
    if ($p -notmatch '\\.git\\' -and $p -notmatch '\\vendor\\' -and $p -notmatch '\\node_modules\\') {
        $Event.MessageData.LastChange = Get-Date
    }
}

Register-ObjectEvent $watcher Changed -Action $onChange -MessageData $shared | Out-Null
Register-ObjectEvent $watcher Created -Action $onChange -MessageData $shared | Out-Null
Register-ObjectEvent $watcher Deleted -Action $onChange -MessageData $shared | Out-Null
Register-ObjectEvent $watcher Renamed -Action $onChange -MessageData $shared | Out-Null

Write-Host "Auto-commit: $projectPath" -ForegroundColor Cyan
Write-Host "Debounce: ${debounceSeconds}s после сохранения | Fallback: каждые ${periodicMinutes} мин" -ForegroundColor Cyan
Write-Host "Ctrl+C для остановки" -ForegroundColor Gray

while ($true) {
    Start-Sleep -Seconds 2

    $now = Get-Date
    $sinceChange  = if ($shared.LastChange)  { ($now - $shared.LastChange).TotalSeconds  } else { 999 }
    $sinceCommit  = ($now - $shared.LastCommit).TotalMinutes

    if ($shared.LastChange -and $sinceChange -ge $debounceSeconds) {
        $shared.LastChange = $null
        Invoke-AutoCommit "save"
    } elseif ($sinceCommit -ge $periodicMinutes) {
        Invoke-AutoCommit "periodic"
    }
}
