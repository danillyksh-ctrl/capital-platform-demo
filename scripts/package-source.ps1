param([string]$Php = 'php')
$ErrorActionPreference = 'Stop'
Set-Location (Split-Path $PSScriptRoot -Parent)
& $Php scripts/check-publication.php
if ($LASTEXITCODE -ne 0) { throw 'Проверка публикации не пройдена.' }
git diff --cached --check
if ($LASTEXITCODE -ne 0) { throw 'Исправьте замечания staged diff перед упаковкой.' }
$sourceFiles = @(git ls-files --cached)
if ($LASTEXITCODE -ne 0 -or $sourceFiles.Count -eq 0) { throw 'Сначала подготовьте проверенный набор файлов через git add.' }
git diff --quiet
if ($LASTEXITCODE -ne 0) { throw 'Есть изменения вне индекса. Сначала проверьте их и обновите staged-набор.' }
$outputDirectory = Join-Path (Get-Location) 'artifacts'
New-Item -ItemType Directory -Force $outputDirectory | Out-Null
$archivePath = Join-Path $outputDirectory 'capital-platform-demo-source.zip'
$stream = [System.IO.File]::Open($archivePath, [System.IO.FileMode]::Create)
$archive = [System.IO.Compression.ZipArchive]::new($stream, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    foreach ($sourceFile in $sourceFiles) {
        # Список берётся только из Git; инструменты, базы, окружение и зависимости не копируются.
        $entry = $archive.CreateEntry('capital-platform-demo/' + $sourceFile, [System.IO.Compression.CompressionLevel]::Optimal)
        $entryStream = $entry.Open()
        $inputStream = [System.IO.File]::OpenRead((Join-Path (Get-Location) $sourceFile))
        try { $inputStream.CopyTo($entryStream) } finally { $inputStream.Dispose(); $entryStream.Dispose() }
    }
} finally { $archive.Dispose(); $stream.Dispose() }
$hash = (Get-FileHash $archivePath -Algorithm SHA256).Hash.ToLowerInvariant()
"$hash  capital-platform-demo-source.zip" | Set-Content ($archivePath + '.sha256') -Encoding utf8NoBOM
Write-Host "Создан исходный архив: artifacts/capital-platform-demo-source.zip ($($sourceFiles.Count) файлов)."
