@echo off
title IoT Meter Monitoring - Localhost
cd /d "%~dp0"

echo ============================================================
echo     MEMULAI IOT METER MONITORING (LOCAL DEPLOYMENT)        
echo ============================================================

if not exist "database\database.sqlite" (
    echo [!] Membuat file database SQLite...
    type nul > "database\database.sqlite"
    php artisan migrate --force
    php artisan db:seed --force
)

echo [*] Alamat Aplikasi: http://127.0.0.1:8000
echo [*] Akun Login     : admin / admin123
echo [*] Tekan Ctrl + C untuk mematikan server.
echo ============================================================

php artisan serve --host=127.0.0.1 --port=8000 --no-reload
pause
