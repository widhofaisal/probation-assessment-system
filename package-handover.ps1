<#
.SYNOPSIS
    Membuat paket ZIP serah-terima (handover) yang bersih untuk klien.

.DESCRIPTION
    Daftar file diambil dari `git ls-files --cached --others --exclude-standard`,
    sehingga:
      * mengikuti .gitignore -> .env, secrets.json, deploy.config.json, vendor/,
        node_modules/, log, dan cache otomatis TIDAK ikut;
      * file baru yang belum sempat di-commit tetap ikut, jadi tidak ada fitur
        yang hilang dari paket.

    Lapisan kedua: DENY-LIST eksplisit. File yang cocok tetap dibuang walaupun
    suatu saat ter-commit ke git tanpa sengaja. .gitignore melindungi git,
    deny-list melindungi ZIP.

    Lapisan ketiga: isi paket dipindai untuk mencari pola kredensial. Jika ada
    temuan, ZIP TIDAK dibuat dan lokasinya dilaporkan.

.PARAMETER IncludeDeployScripts
    Sertakan folder deploy-scripts/. Default: TIDAK disertakan, karena isinya
    tooling deploy untuk akun hosting showcase milik developer, bukan bagian
    dari produk yang diserahkan ke klien.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File .\package-handover.ps1

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File .\package-handover.ps1 -IncludeDeployScripts
#>
[CmdletBinding()]
param(
    [string] $OutDir      = 'dist',
    [string] $PackageName = 'hrd-system-ci-handover',
    [switch] $IncludeDeployScripts,
    [switch] $Force
)

$ErrorActionPreference = 'Stop'

function Write-Step  { param($m) Write-Host "`n==> $m" -ForegroundColor Cyan }
function Write-Ok    { param($m) Write-Host "    [ok]   $m" -ForegroundColor Green }
function Write-Skip  { param($m) Write-Host "    [skip] $m" -ForegroundColor DarkGray }
function Write-Note  { param($m) Write-Host "    [!]    $m" -ForegroundColor Yellow }
function Write-Bad   { param($m) Write-Host "    [X]    $m" -ForegroundColor Red }

# ---------------------------------------------------------------- repo root --
$repoRoot = & git rev-parse --show-toplevel
if ($LASTEXITCODE -ne 0) {
    throw 'Bukan git repository. Skrip ini butuh git untuk menentukan file mana yang ikut.'
}
$repoRoot = $repoRoot.Trim()
Set-Location $repoRoot
Write-Step "Repo: $repoRoot"

# Perubahan yang belum di-commit tetap ikut ke paket, tapi perlu diberitahukan.
$dirty = & git status --porcelain
if ($dirty) {
    Write-Note 'Ada perubahan yang belum di-commit. File tersebut TETAP ikut ke paket,'
    Write-Note 'tapi sebaiknya di-commit dulu agar isi paket sama dengan isi repo.'
}

# -------------------------------------------------------------- daftar file --
Write-Step 'Menyusun daftar file'

# tracked + untracked-yang-tidak-ignored => persis working tree minus .gitignore
$files = @(& git ls-files --cached --others --exclude-standard | Where-Object { $_ })
$files = $files | ForEach-Object { $_ -replace '\\', '/' }
Write-Ok "$($files.Count) file dari git (vendor/, node_modules/, .env, secrets sudah tersaring)"

# ALLOW-LIST: file ter-ignore yang WAJIB ikut supaya aplikasi bisa disetup.
# database_dump.sql satu-satunya sumber skema DB - belum ada migration/seeder.
$allowList = @('database_dump.sql')
foreach ($a in $allowList) {
    if (Test-Path -LiteralPath $a) {
        $files += ($a -replace '\\', '/')
        Write-Ok "disertakan manual: $a (skema + data dummy; tanpa ini DB tidak bisa dibuat)"
    }
    else {
        Write-Note "allow-list tidak ditemukan: $a"
    }
}
$files = $files | Sort-Object -Unique

# DENY-LIST: lapisan kedua, tetap jalan walaupun file ter-commit tanpa sengaja.
$denyPatterns = @(
    '(^|/)\.env$'                 # kredensial DB + encryption key
    '(^|/)\.env\.(?!example)'     # .env.local, .env.production, dst
    '(^|/)env\.local\.php$'
    '(^|/)secrets\.json$'         # password FTP + MySQL
    '(^|/)deploy\.config\.json$'  # host, user, encKey produksi
    '(^|/)session\.json$'
    '(^|/)\.claude(/|$)'
    '(^|/)node_modules/'
    '(^|/)vendor/'
    '(^|/)build/'
    '(^|/)dist/'
    '\.log$'
    '\.bak$'
    '\.backup$'
    '\.sql\.gz$'

    # Dulu ada berkas 'user' di root berisi daftar password polos. Sudah dihapus
    # dan digantikan DEMO-ACCOUNTS.md, tapi aturannya dipertahankan supaya berkas
    # serupa tidak ikut terkirim kalau suatu saat dibuat lagi.
    '^user$'

    # File sesi runtime. Isinya user_id dan role pengguna yang sedang login,
    # jadi tidak boleh ikut diserahkan. Yang di public/ pernah ter-commit tanpa
    # sengaja karena session.savePath sempat memakai path relatif.
    '(^|/)writable/session/'
    '(^|/)public/writable/'

    # Skrip pemaketan ini bagian dari alur kerja developer, bukan produk klien.
    '(^|/)package-handover\.ps1$'
)
if (-not $IncludeDeployScripts) {
    $denyPatterns += '^deploy-scripts/'
}

$denied  = @()
$payload = @()
foreach ($f in $files) {
    $hit = $null
    foreach ($p in $denyPatterns) {
        if ($f -match $p) { $hit = $p; break }
    }
    if ($hit) { $denied += [pscustomobject]@{ File = $f; Rule = $hit } }
    else      { $payload += $f }
}

if ($denied.Count -gt 0) {
    Write-Step "Dibuang oleh deny-list ($($denied.Count) file)"
    foreach ($d in ($denied | Select-Object -First 15)) { Write-Skip $d.File }
    if ($denied.Count -gt 15) { Write-Skip "... dan $($denied.Count - 15) lainnya" }
}
if (-not $IncludeDeployScripts) {
    Write-Skip 'deploy-scripts/ (pakai -IncludeDeployScripts bila memang mau diserahkan)'
}

if ($payload.Count -eq 0) { throw 'Tidak ada file tersisa untuk dipaketkan.' }
Write-Step "Isi paket: $($payload.Count) file"

# ------------------------------------------------------- pemindaian rahasia --
Write-Step 'Memindai isi paket untuk pola kredensial'

$scanExt = @('.php', '.js', '.json', '.example', '.md', '.sql', '.ps1', '.bat',
             '.yml', '.yaml', '.xml', '.ini', '.txt', '.html', '.htaccess', '.dist')
# Catatan: pakai [^\S\r\n] (spasi/tab saja), BUKAN \s. \s ikut mencocokkan
# baris baru, sehingga "password =" yang kosong akan tersambung ke baris
# berikutnya dan menghasilkan false positive.
$h = '[^\S\r\n]*'
$secretPatterns = @(
    @{ Name = 'akun hosting InfinityFree'; Pattern = 'if0_\d{4,}' }
    @{ Name = 'host DB showcase';          Pattern = 'sql\d+\.infinityfree\.com' }
    @{ Name = 'password DB terisi';        Pattern = "database\.default\.password$h=$h(?![A-Z_]+(?:\s|$))\S+" }
    # Nama kunci bisa muncul dalam beberapa bentuk penulisan:
    #   encryption.key = 'xxx'              (berkas .env)
    #   $_ENV['encryption.key'] = 'xxx'     (berkas env.local.php)
    #   "encKey": "xxx"                     (berkas JSON)
    # Karena itu di antara nama kunci dan tanda sama dengan diizinkan ada
    # penutup kutip dan kurung siku. Versi sebelumnya hanya mengizinkan spasi,
    # dan gara-gara itu melewatkan kunci enkripsi sungguhan yang tertulis di
    # env.local.php.example.
    @{ Name = 'encryption key terisi';     Pattern = "(encryption\.key|encKey)['`"\]]*$h[=:]$h[`"']?[A-Za-z0-9+/=]{16,}" }
    @{ Name = 'password FTP/DB literal';   Pattern = "(ftpPass|dbPass)$h[:=]$h[`"'][^`"'$]{4,}" }
    @{ Name = 'isi file sesi PHP';         Pattern = 'user_id\|s:\d+:' }
)

$findings = @()
foreach ($f in $payload) {
    $ext = [System.IO.Path]::GetExtension($f)
    if ($ext -and ($scanExt -notcontains $ext.ToLower())) { continue }
    if (-not (Test-Path -LiteralPath $f)) { continue }
    if ((Get-Item -LiteralPath $f).Length -gt 5MB) { continue }

    $text = Get-Content -LiteralPath $f -Raw -ErrorAction SilentlyContinue
    if (-not $text) { continue }

    foreach ($sp in $secretPatterns) {
        $m = [regex]::Match($text, $sp.Pattern)
        if ($m.Success) {
            $line = ($text.Substring(0, $m.Index) -split "`n").Count
            $findings += [pscustomobject]@{ File = $f; Line = $line; Jenis = $sp.Name }
        }
    }
}

if ($findings.Count -gt 0) {
    Write-Step 'PEMINDAIAN MENEMUKAN KREDENSIAL'
    foreach ($x in $findings) { Write-Bad "$($x.File):$($x.Line)  -> $($x.Jenis)" }
    Write-Host ''
    if (-not $Force) {
        Write-Host 'ZIP tidak dibuat. Bersihkan file di atas (atau tambahkan ke deny-list),' -ForegroundColor Yellow
        Write-Host 'lalu jalankan ulang skrip ini.' -ForegroundColor Yellow
        exit 1
    }
    Write-Note '-Force aktif: tetap dilanjutkan meski ada temuan.'
}
else {
    Write-Ok 'Tidak ditemukan pola kredensial.'
}

# --------------------------------------------------------------------- zip --
# Sengaja TIDAK memakai Compress-Archive. Di Windows PowerShell 5.1 cmdlet itu
# menulis pemisah path backslash ke dalam entri ZIP, padahal spesifikasi ZIP
# mewajibkan forward slash. Akibatnya saat diekstrak di Linux/macOS struktur
# folder runtuh menjadi file datar bernama "app\Config\App.php". Klien besar
# kemungkinan mengekstrak di shared hosting Linux, jadi entri ditulis manual.

$stamp   = Get-Date -Format 'yyyyMMdd'
$zipPath = Join-Path $repoRoot (Join-Path $OutDir "$PackageName-$stamp.zip")

Write-Step 'Membuat ZIP'
$outFull = Split-Path $zipPath -Parent
if (-not (Test-Path -LiteralPath $outFull)) { New-Item -ItemType Directory -Path $outFull -Force | Out-Null }
if (Test-Path -LiteralPath $zipPath) {
    if (-not $Force) { throw "$zipPath sudah ada. Hapus dulu, atau jalankan dengan -Force." }
    Remove-Item -LiteralPath $zipPath -Force
}

Add-Type -AssemblyName System.IO.Compression | Out-Null
Add-Type -AssemblyName System.IO.Compression.FileSystem | Out-Null

$level  = [System.IO.Compression.CompressionLevel]::Optimal
$stream = [System.IO.File]::Open($zipPath, [System.IO.FileMode]::CreateNew)
$zip    = New-Object System.IO.Compression.ZipArchive($stream, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    foreach ($f in $payload) {
        $srcPath = Join-Path $repoRoot ($f -replace '/', '\')
        if (-not (Test-Path -LiteralPath $srcPath)) { Write-Note "lewat (tidak ada): $f"; continue }

        $entry   = $zip.CreateEntry($f, $level)   # $f sudah memakai forward slash
        $entryIO = $entry.Open()
        $srcIO   = [System.IO.File]::OpenRead($srcPath)
        try     { $srcIO.CopyTo($entryIO) }
        finally { $srcIO.Dispose(); $entryIO.Dispose() }
    }

    # Folder writable/ harus ada di paket walau isinya kosong - runtime menulis
    # cache, session, dan log ke sini. Entri diakhiri "/" agar dikenali direktori.
    foreach ($w in @('writable/cache', 'writable/logs', 'writable/session',
                     'writable/uploads', 'writable/pdfs', 'writable/debugbar')) {
        $zip.CreateEntry("$w/") | Out-Null
    }
}
finally {
    $zip.Dispose()
    $stream.Dispose()
}
Write-Ok "$($payload.Count) file ditulis dengan pemisah path forward slash"

$sizeMb = [math]::Round((Get-Item -LiteralPath $zipPath).Length / 1MB, 2)

Write-Host ''
Write-Host '========================================================' -ForegroundColor Green
Write-Host ' PAKET SERAH-TERIMA SIAP' -ForegroundColor Green
Write-Host '========================================================' -ForegroundColor Green
Write-Host "  Lokasi : $zipPath"
Write-Host "  Isi    : $($payload.Count) file, $sizeMb MB"
Write-Host ''
Write-Host '  TIDAK ikut (disengaja):' -ForegroundColor DarkGray
Write-Host '    .env, secrets.json, deploy.config.json  -> kredensial Anda' -ForegroundColor DarkGray
Write-Host '    vendor/, node_modules/                  -> hasil composer/npm install' -ForegroundColor DarkGray
Write-Host '    writable/logs, cache, session           -> sisa runtime' -ForegroundColor DarkGray
if (-not $IncludeDeployScripts) {
    Write-Host '    deploy-scripts/                         -> tooling hosting showcase' -ForegroundColor DarkGray
}
Write-Host ''
Write-Host '  Setup klien: composer install -> salin .env.example jadi .env -> import database_dump.sql' -ForegroundColor DarkGray
Write-Host ''
