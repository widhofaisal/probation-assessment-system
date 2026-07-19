# Import database ke phpMyAdmin via HTTP (tanpa browser)
# Jalankan: powershell -ExecutionPolicy Bypass -File 3-import-db.ps1

$ErrorActionPreference = "Stop"

$DbUser   = "if0_41805346"
$DbPass   = "1RSETenjJ2ir"
$DbName   = "if0_41805346_hrd_system"
$PmaBase  = "https://php-myadmin.net"
$DumpFile = (Resolve-Path "..\database_dump.sql").Path

Write-Host "`n=== Import Database via phpMyAdmin HTTP ===" -ForegroundColor Cyan
Write-Host "  DB   : $DbName"
Write-Host "  File : $DumpFile ($([math]::Round((Get-Item $DumpFile).Length/1KB)) KB)"

# ── Session container (cookies otomatis) ─────────────────────────────────
$session = $null

# ── Langkah 1: GET login page (ambil token CSRF) ─────────────────────────
Write-Host "`n[1/4] Ambil halaman login..." -ForegroundColor Cyan

$resp1 = Invoke-WebRequest -Uri "$PmaBase/index.php" `
    -SessionVariable session `
    -UseBasicParsing `
    -TimeoutSec 30

$html1 = $resp1.Content

# Cari token phpMyAdmin
$tokenMatch = [regex]::Match($html1, 'name="token"\s+value="([a-f0-9]+)"')
if (-not $tokenMatch.Success) {
    $tokenMatch = [regex]::Match($html1, '"token":"([a-f0-9]+)"')
}
$token = if ($tokenMatch.Success) { $tokenMatch.Groups[1].Value } else { "" }
Write-Host "  Token: $token"

# Cari server number
$serverMatch = [regex]::Match($html1, 'name="server"\s+value="(\d+)"')
$server = if ($serverMatch.Success) { $serverMatch.Groups[1].Value } else { "1" }

# ── Langkah 2: POST login ─────────────────────────────────────────────────
Write-Host "`n[2/4] Login ke phpMyAdmin..." -ForegroundColor Cyan

$loginBody = "pma_username=$([Uri]::EscapeDataString($DbUser))&pma_password=$([Uri]::EscapeDataString($DbPass))&server=$server&target=index.php&token=$token"

$resp2 = Invoke-WebRequest -Uri "$PmaBase/index.php" `
    -Method POST `
    -Body $loginBody `
    -ContentType "application/x-www-form-urlencoded" `
    -WebSession $session `
    -UseBasicParsing `
    -TimeoutSec 30

$html2 = $resp2.Content
$finalUrl = $resp2.BaseResponse.ResponseUri.ToString()
Write-Host "  URL setelah login: $finalUrl"

# Update token dari halaman setelah login
$tokenMatch2 = [regex]::Match($html2, 'name="token"\s+value="([a-f0-9]+)"')
if (-not $tokenMatch2.Success) {
    $tokenMatch2 = [regex]::Match($html2, '"token":"([a-f0-9]+)"')
}
if ($tokenMatch2.Success) { $token = $tokenMatch2.Groups[1].Value }
Write-Host "  Token baru: $token"

# Cek apakah login berhasil
if ($html2 -match 'pma_username|login_form|Invalid username' ) {
    Write-Host "`n[!] Login gagal! Cek username/password." -ForegroundColor Red
    # Simpan HTML untuk debug
    $html2 | Out-File ".\debug_login.html" -Encoding utf8
    Write-Host "  Debug HTML: .\debug_login.html"
    exit 1
}
Write-Host "  Login berhasil!" -ForegroundColor Green

# ── Langkah 3: GET import page (ambil token terbaru) ─────────────────────
Write-Host "`n[3/4] Buka halaman Import..." -ForegroundColor Cyan

$importPageUrl = "$PmaBase/index.php?route=/database/import&db=$([Uri]::EscapeDataString($DbName))"
$resp3 = Invoke-WebRequest -Uri $importPageUrl `
    -WebSession $session `
    -UseBasicParsing `
    -TimeoutSec 30

$html3 = $resp3.Content
$tokenMatch3 = [regex]::Match($html3, 'name="token"\s+value="([a-f0-9]+)"')
if (-not $tokenMatch3.Success) {
    $tokenMatch3 = [regex]::Match($html3, '"token":"([a-f0-9]+)"')
}
if ($tokenMatch3.Success) { $token = $tokenMatch3.Groups[1].Value }
Write-Host "  Token import: $token"
Write-Host "  URL: $($resp3.BaseResponse.ResponseUri)"

# ── Langkah 4: POST import (multipart/form-data) ─────────────────────────
Write-Host "`n[4/4] Upload & import database_dump.sql..." -ForegroundColor Cyan

$boundary = [System.Guid]::NewGuid().ToString("N")
$sqlContent = [System.IO.File]::ReadAllBytes($DumpFile)
$sqlFileName = "database_dump.sql"

$bodyParts = [System.Collections.Generic.List[byte]]::new()

function Add-TextPart($name, $value) {
    $header = "--$boundary`r`nContent-Disposition: form-data; name=`"$name`"`r`n`r`n$value`r`n"
    $bodyParts.AddRange([System.Text.Encoding]::UTF8.GetBytes($header))
}
function Add-FilePart($name, $filename, $bytes) {
    $header = "--$boundary`r`nContent-Disposition: form-data; name=`"$name`"; filename=`"$filename`"`r`nContent-Type: application/sql`r`n`r`n"
    $bodyParts.AddRange([System.Text.Encoding]::UTF8.GetBytes($header))
    $bodyParts.AddRange($bytes)
    $bodyParts.AddRange([System.Text.Encoding]::UTF8.GetBytes("`r`n"))
}

Add-TextPart "token"           $token
Add-TextPart "db"              $DbName
Add-TextPart "table"           ""
Add-TextPart "id"              ""
Add-TextPart "charset_of_file" "utf-8"
Add-TextPart "format"          "sql"
Add-TextPart "sql_compatibility_mode" ""
Add-TextPart "allow_interrupt" "yes"
Add-TextPart "skip_queries"    "0"
Add-TextPart "loca_name"       ""
Add-TextPart "do_import"       "1"
Add-FilePart  "import_file"    $sqlFileName $sqlContent

$endBoundary = "--$boundary--`r`n"
$bodyParts.AddRange([System.Text.Encoding]::UTF8.GetBytes($endBoundary))

$importUrl = "$PmaBase/index.php?route=/database/import&db=$([Uri]::EscapeDataString($DbName))"

$resp4 = Invoke-WebRequest -Uri $importUrl `
    -Method POST `
    -Body $bodyParts.ToArray() `
    -ContentType "multipart/form-data; boundary=$boundary" `
    -WebSession $session `
    -UseBasicParsing `
    -TimeoutSec 120

$html4 = $resp4.Content
Write-Host "  Response status: $($resp4.StatusCode)"

# Simpan response untuk debug jika perlu
$html4 | Out-File ".\debug_import.html" -Encoding utf8

# Cek hasil import
if ($html4 -match 'success|Your SQL query has been executed|Import has been successfully|queries affected') {
    Write-Host "`n  Database berhasil diimport!" -ForegroundColor Green
} elseif ($html4 -match 'error|Error|ERROR') {
    $errMatch = [regex]::Match($html4, '<div[^>]*class="[^"]*error[^"]*"[^>]*>(.*?)</div>', [System.Text.RegularExpressions.RegexOptions]::Singleline)
    if ($errMatch.Success) {
        Write-Host "`n  [!] Error: $($errMatch.Groups[1].Value -replace '<[^>]+>', '' -replace '\s+', ' ')" -ForegroundColor Red
    } else {
        Write-Host "`n  [!] Ada error - cek debug_import.html" -ForegroundColor Red
    }
} else {
    Write-Host "`n  Import mungkin berhasil - cek debug_import.html untuk konfirmasi" -ForegroundColor Yellow
}

# Hapus debug files jika sukses
if ($html4 -match 'success|Your SQL query has been executed|Import has been successfully') {
    Remove-Item ".\debug_login.html" -ErrorAction SilentlyContinue
    Remove-Item ".\debug_import.html" -ErrorAction SilentlyContinue
}

Write-Host "`n=== Selesai ===" -ForegroundColor Cyan
Write-Host "URL Aplikasi: $((Get-Content deploy.config.json | ConvertFrom-Json).baseURL)"
