$ErrorActionPreference = 'Stop'
Set-Location (Split-Path $PSScriptRoot -Parent)
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
$configuration = Get-Content .env -Raw
if ($configuration -notmatch '(?m)^DB_CONNECTION=pgsql\s*$') { throw 'Для Docker используйте .env.example. Существующий .env оставлен без изменения.' }
# Секреты генерируются локально и сохраняются исключительно в исключённом .env.
foreach ($setting in @('DB_PASSWORD', 'DEMO_PASSWORD')) {
    if ($configuration -match "(?m)^${setting}=\s*$") {
        $bytes = [byte[]]::new(24)
        [System.Security.Cryptography.RandomNumberGenerator]::Fill($bytes)
        $secret = [Convert]::ToHexString($bytes).ToLowerInvariant()
        $configuration = $configuration -replace "(?m)^${setting}=[^\r\n]*", "${setting}=$secret"
    }
}
$configuration | Set-Content .env -Encoding utf8NoBOM
docker compose config --quiet
if ($LASTEXITCODE -ne 0) { throw 'Некорректная конфигурация Compose.' }
docker compose build
if ($LASTEXITCODE -ne 0) { throw 'Не удалось собрать образ.' }
if ($configuration -match '(?m)^APP_KEY=\s*$') {
    $generatedKey = docker compose run --rm --no-deps app php artisan key:generate --show
    if ($LASTEXITCODE -ne 0 -or $generatedKey -notmatch '^base64:') { throw 'Не удалось создать ключ приложения.' }
    $configuration = $configuration -replace '(?m)^APP_KEY=[^\r\n]*', ('APP_KEY=' + $generatedKey.Trim())
    $configuration | Set-Content .env -Encoding utf8NoBOM
}
docker compose up -d --wait
if ($LASTEXITCODE -ne 0) { throw 'Не удалось запустить сервисы.' }
docker compose exec -T app php artisan migrate --seed --force
if ($LASTEXITCODE -ne 0) { throw 'Не удалось выполнить миграции.' }
Write-Host 'Кабинет: http://localhost:8000 | Почта: http://localhost:8025'
Write-Host 'Пароль демо-аккаунтов находится в DEMO_PASSWORD локального .env.'
