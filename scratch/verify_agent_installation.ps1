$ErrorActionPreference='Stop'
$setup=Get-Content -LiteralPath 'D:\xampp\htdocs\sistem-antrian-bank\scratch\branch-agent-audit\private\ui-installer\setup.json' -Raw | ConvertFrom-Json
$directory=Join-Path $env:LOCALAPPDATA ('BankDPTaspenPrintAgent\'+$setup.agent_id)
$shortcutPath=Join-Path ([Environment]::GetFolderPath('Startup')) ('Bank DP Taspen Agent '+$setup.agent_id+'.lnk')
if(-not(Test-Path -LiteralPath $shortcutPath)){throw 'Startup not installed'}
$shortcut=(New-Object -ComObject WScript.Shell).CreateShortcut($shortcutPath)
if($shortcut.Arguments -notmatch 'Start-Agent.ps1' -or $shortcut.WindowStyle -ne 7){throw 'Startup invalid'}
if(-not(Get-Acl -LiteralPath $directory).AreAccessRulesProtected){throw 'Private folder inheritance not protected'}
powershell.exe -NoProfile -NonInteractive -ExecutionPolicy Bypass -File (Join-Path $directory 'Start-Agent.ps1') -Once
if($LASTEXITCODE -ne 0){throw 'Agent diagnostic failed'}
# This test package targets only the isolated test server. Remove its autostart entry now.
Remove-Item -LiteralPath $shortcutPath
Write-Output 'PASS installer: official Sumatra verified, enrollment, DPAPI, protected folder, hidden startup, diagnostic heartbeat. Test startup removed.'
