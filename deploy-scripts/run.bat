@echo off
title HRD System - Deploy ke InfinityFree

echo.
echo  ================================================
echo   DEPLOY OTOMATIS - Sistem Penilaian Probation
echo  ================================================
echo.

if not exist node_modules (
    echo  [*] Install dependencies...
    call npm install
    if errorlevel 1 (
        echo  [!] npm install gagal
        pause
        exit /b 1
    )
    echo.
)

echo  Pilih mode deploy:
echo    1. Deploy PENUH  (FTP upload + DB import)
echo    2. DB import SAJA (FTP sudah selesai)
echo.
set /p PILIHAN=  Pilihan (1 atau 2):

if "%PILIHAN%"=="2" goto db_only

:full_deploy
echo.
echo  [1/3] Login ke InfinityFree...
echo.
node 1-login.js
if errorlevel 1 (
    echo  [!] Login gagal.
    pause
    exit /b 1
)

echo.
echo  [2/3] Ambil konfigurasi dari InfinityFree...
echo.
node _scrape-db.js

echo.
echo  [3/3] Upload file + Import database...
echo.
node 2-deploy.js
if errorlevel 1 (
    echo  [!] Deploy gagal.
    pause
    exit /b 1
)
goto done

:db_only
echo.
echo  [1/2] Login ke InfinityFree...
echo.
node 1-login.js
if errorlevel 1 (
    echo  [!] Login gagal.
    pause
    exit /b 1
)

echo.
echo  [2/2] Import database...
echo.
node 3-import-db.js
if errorlevel 1 (
    echo  [!] Import database gagal.
    pause
    exit /b 1
)

:done
echo.
echo  ------------------------------------------------
echo  Selesai!
pause
