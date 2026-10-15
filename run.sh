#!/usr/bin/env bash
# ============================================================
# Launcher Script - IoT Meter Monitoring Web Application
# ============================================================

echo "============================================================"
echo "    MEMULAI IOT METER MONITORING (LOCAL DEPLOYMENT)        "
echo "============================================================"

# Pindah ke direktori skrip berada
cd "$(dirname "$0")"

# Pastikan file database SQLite ada
if [ ! -f "database/database.sqlite" ]; then
    echo "[!] Membuat database SQLite baru..."
    touch database/database.sqlite
    php artisan migrate --force
    php artisan db:seed --force
fi

echo "[*] Alamat Aplikasi: http://127.0.0.1:8000"
echo "[*] Akun Login     : admin / admin123"
echo "[*] Tekan Ctrl + C untuk mematikan server."
echo "============================================================"

# Jalankan server dengan 4 concurrent worker
php artisan serve --host=127.0.0.1 --port=8000 --no-reload
