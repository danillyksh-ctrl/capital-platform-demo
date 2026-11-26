param([string]$BaseUrl = 'http://127.0.0.1:8000')
$ErrorActionPreference = 'Stop'
if (-not $env:DEMO_PASSWORD) { throw 'Задайте DEMO_PASSWORD в окружении процесса из локального .env.' }
$homeResponse = Invoke-WebRequest "$BaseUrl/" -SessionVariable webSession
if ($homeResponse.StatusCode -ne 200) { throw 'Главная недоступна.' }
if ($homeResponse.Headers['X-Frame-Options'] -ne 'DENY') { throw 'Нет защитных заголовков.' }
$withoutToken = Invoke-WebRequest "$BaseUrl/login" -Method Post -WebSession $webSession -Body @{email='demo@example.test';password='incorrect'} -SkipHttpErrorCheck
if ($withoutToken.StatusCode -ne 419) { throw "Ожидался CSRF 419, получен $($withoutToken.StatusCode)." }
$login = Invoke-WebRequest "$BaseUrl/login" -WebSession $webSession
$csrfCookie = $webSession.Cookies.GetCookies([Uri]$BaseUrl)['XSRF-TOKEN']
if (-not $csrfCookie) { throw 'CSRF cookie отсутствует.' }
$token = [Uri]::UnescapeDataString($csrfCookie.Value)
$authenticated = Invoke-WebRequest "$BaseUrl/login" -Method Post -WebSession $webSession -Headers @{'X-XSRF-TOKEN'=$token} -Body @{email='demo@example.test';password=$env:DEMO_PASSWORD}
if ($authenticated.Content -notmatch 'Dashboard') { throw 'Вход не открыл кабинет.' }
$export = Invoke-WebRequest "$BaseUrl/profile/export" -WebSession $webSession
$profile = $export.Content | ConvertFrom-Json
if ($profile.profile.email -ne 'demo@example.test' -or $profile.profile.PSObject.Properties.Name -contains 'password') { throw 'Экспорт содержит неверные данные.' }
$forbidden = Invoke-WebRequest "$BaseUrl/admin" -WebSession $webSession -SkipHttpErrorCheck
if ($forbidden.StatusCode -ne 403) { throw 'Обычному пользователю доступна админка.' }
Write-Host 'PASS: HTTP 200, security headers, CSRF 419, login, JSON export, admin 403.'
