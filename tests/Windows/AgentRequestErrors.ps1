# Run with Windows PowerShell 5.1. Uses a fake HTTP client; no network or printing.
$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot '..\..\agent\Agent.Common.ps1')

$config = [pscustomobject]@{ server_url='http://127.0.0.1/antrean'; allow_http=$true }
$script:requestAddress = $null
$client = New-Object PSObject
$client | Add-Member -MemberType ScriptMethod -Name SendAsync -Value {
    param($request)
    $script:requestAddress = $request.RequestUri.AbsoluteUri
    $response = [Net.Http.HttpResponseMessage]::new([Net.HttpStatusCode]::NotFound)
    $completion = [Threading.Tasks.TaskCompletionSource[Net.Http.HttpResponseMessage]]::new()
    $completion.SetResult($response)
    return $completion.Task
}

$rejected = $false
try {
    Invoke-AgentRequest $client $config '/api/print-agent/enroll' @{} | Out-Null
} catch {
    if ($_.Exception.Data['HttpStatus'] -ne 404) { throw }
    if ($_.Exception.Message -notlike '*HTTP 404*subfolder*unduh installer baru*') { throw }
    $rejected = $true
}
if (-not $rejected) { throw 'A missing endpoint must reject installation with a useful error.' }
if ($script:requestAddress -ne 'http://127.0.0.1/antrean/api/print-agent/enroll') {
    throw 'The application subdirectory was lost from the enrollment request.'
}
Write-Output 'PASS enrollment keeps application subdirectory; HTTP 404 explains the address and new-package recovery.'
