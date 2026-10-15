# IoT Meter Monitoring & SCADA Telemetry System
### Integrasi Multi-Hardware RS485 via Gateway RS485-to-TCP/IP | Laravel 11 / PHP 8.3

Sistem monitoring telemetri IoT berbasis web yang membaca, mencatat, memvisualisasikan, dan menganalisis parameter utilitas secara terpusat dari **5 unit instrumen lapangan RS485** melalui **1 unit Gateway RS485-to-TCP/IP**.

---

## 📋 Daftar Hardware yang Terintegrasi

| No | Nama Perangkat | Tipe | Slave ID | Parameter | Satuan |
|:--:|---|---|:---:|---|:---:|
| **1** | Meter Air RS485 | Meter Air Mekanik / Elektromagnetik | `1` | Totalizer | m³ |
| **2** | Meter Air Digital RS485 | Meter Air Digital / Ultrasonik | `2` | Totalizer, Flow Rate | m³, m³/h |
| **3** | Flow Meter RS485 | Flow Meter Industri | `3` | Totalizer, Flow Rate | m³, m³/h |
| **4** | kWh Meter RS485 | kWh Meter Listrik Digital | `4` | Total kWh, Tegangan, Arus | kWh, V, A |
| **5** | Power Meter RS485 | Power Analyzer Multifungsi | `5` | Total kWh, Tegangan, Arus, Daya | kWh, V, A, kW |

---

## 🌐 Topologi Jaringan & Wiring Fisik

```
┌─────────────────────────────────────────────────────────────────────┐
│                  INSTRUMEN LAPANGAN (RS485 Bus)                     │
│                                                                     │
│  [Meter Air RS485]         Slave ID: 1  ──┐                         │
│  [Meter Air Digital RS485] Slave ID: 2  ──┤                         │
│  [Flow Meter RS485]        Slave ID: 3  ──┼── A(+) B(-) GND        │
│  [kWh Meter RS485]         Slave ID: 4  ──┤   (2-wire RS485 bus)   │
│  [Power Meter RS485]       Slave ID: 5  ──┘                         │
└──────────────────────────────┬──────────────────────────────────────┘
                               │ Kabel RS485 (2 kawat + GND)
                               ▼
              ┌────────────────────────────────┐
              │   GATEWAY RS485 to TCP/IP      │
              │   (Modbus RTU → Modbus TCP)    │
              │   IP: 192.168.1.100  Port: 502 │
              └────────────────┬───────────────┘
                               │ Kabel LAN / Switch Ethernet
                               ▼
              ┌────────────────────────────────┐
              │   SERVER APLIKASI (Laravel)    │
              │   Modbus TCP Client (PHP Socket)│
              │   http://127.0.0.1:8000        │
              └────────────────────────────────┘
```

---

## 🔌 Panduan Pemasangan Hardware (Wiring Fisik)

### Langkah 1 — Pengkabelan Bus RS485

RS485 adalah komunikasi 2-kawat half-duplex. Semua perangkat dirangkai **daisy-chain** (paralel di jalur yang sama):

```
Gateway       Meter Air     Meter Air Digital   Flow Meter    kWh Meter   Power Meter
  A(+) ─────── A(+) ──────── A(+) ────────────── A(+) ──────── A(+) ─────── A(+)
  B(-) ─────── B(-) ──────── B(-) ────────────── B(-) ──────── B(-) ─────── B(-)
  GND  ─────── GND  ──────── GND  ────────────── GND  ──────── GND  ─────── GND
 [120Ω] ← Resistor terminasi di ujung pertama                          [120Ω] ← Ujung terakhir
```

> **Penting:**
> - Pasang **resistor terminasi 120Ω** di kedua ujung fisik jalur bus RS485 (bukan di tengah).
> - Gunakan kabel **twisted-pair** (misal: kabel Belden 9841 atau kabel LAN Cat5e dipotong).
> - Panjang maksimum bus RS485: ±1200 meter pada 9600 bps.
> - Jangan ada lebih dari 32 node dalam satu segmen.

---

### Langkah 2 — Setting Slave ID di Setiap Perangkat

Setiap perangkat **wajib** memiliki Slave ID yang unik. Setting dilakukan via DIP switch, keypad, atau software sesuai manual masing-masing:

| Perangkat | Slave ID yang Harus Di-set | Catatan |
|---|:---:|---|
| Meter Air RS485 | **1** | Via DIP switch atau keypad perangkat |
| Meter Air Digital RS485 | **2** | Via DIP switch atau keypad perangkat |
| Flow Meter RS485 | **3** | Via DIP switch atau keypad perangkat |
| kWh Meter RS485 | **4** | Via DIP switch atau keypad perangkat |
| Power Meter RS485 | **5** | Via DIP switch atau keypad perangkat |

> ⚠️ **Slave ID tidak boleh ada yang sama** — akan menyebabkan konflik dan data tidak terbaca.

---

### Langkah 3 — Setting Serial Parameter di Semua Perangkat

Semua perangkat di bus RS485 **harus menggunakan parameter serial yang sama**:

| Parameter | Nilai Standar | Keterangan |
|---|:---:|---|
| Baud Rate | **9600 bps** | Bisa 4800/19200 asal semua seragam |
| Data Bits | **8** | — |
| Parity | **None** | Beberapa meter pakai Even, sesuaikan |
| Stop Bits | **1** | — |
| Mode | **Modbus RTU** | Bukan ASCII |

---

### Langkah 4 — Konfigurasi Gateway RS485 to TCP/IP

Gateway mengubah komunikasi Modbus RTU (RS485) menjadi Modbus TCP (Ethernet). Contoh gateway yang kompatibel: **Waveshare RS485 to ETH**, **USR-TCP232-306**, **Moxa NPort**, **ZLAN5143D**.

Buka web interface gateway (biasanya di `192.168.1.x`) dan atur:

| Setting Gateway | Nilai |
|---|---|
| **IP Address Gateway** | `192.168.1.100` (sesuaikan dengan jaringan LAN Anda) |
| **Subnet Mask** | `255.255.255.0` |
| **Port TCP** | `502` (port standar Modbus TCP) |
| **Work Mode** | `TCP Server` atau `Modbus TCP ↔ RTU Gateway` |
| **Baud Rate Serial** | Sama dengan perangkat meter (misal `9600`) |
| **Data Bits** | `8` |
| **Parity** | `None` |
| **Stop Bits** | `1` |

> **Catatan:** Pastikan IP gateway dan IP server aplikasi berada dalam subnet yang sama (misal keduanya di `192.168.1.x`).

---

## 🚀 Instalasi Aplikasi (Windows)

### Kebutuhan Sistem

| Komponen | Keterangan |
|---|---|
| **PHP** | Versi 8.2 atau 8.3 — download di [windows.php.net](https://windows.php.net/download/) (pilih **Thread Safe x64**) |
| **Composer** | Download installer di [getcomposer.org](https://getcomposer.org/download/) |
| **Database** | SQLite — sudah terintegrasi, **tidak perlu install database server** |
| **OS** | Windows 10 / 11 (64-bit) |

> **Ekstensi PHP yang harus diaktifkan** di `php.ini` (hapus tanda `;` di depan baris berikut):
> ```ini
> extension=pdo_sqlite
> extension=sqlite3
> extension=curl
> extension=mbstring
> extension=fileinfo
> extension=openssl
> extension=sockets
> ```

---

### Metode 1 — Script Otomatis (Direkomendasikan)

Cukup **klik dua kali** file `run.bat` di folder proyek:

```
run.bat
```

Script akan otomatis:
1. Memeriksa apakah database SQLite sudah ada
2. Menjalankan migrasi & seeding jika database belum ada
3. Menyalakan web server di `http://127.0.0.1:8000`

Buka browser dan akses `http://127.0.0.1:8000` — selesai.

---

### Metode 2 — Manual via Command Prompt

Buka **Command Prompt** (`cmd`) atau **PowerShell**, lalu jalankan satu per satu:

#### Step 1 — Masuk ke Folder Proyek

```cmd
cd C:\path\ke\iotlistrik
```

> Contoh nyata: `cd C:\Users\Rizky\Documents\iotlistrik`

#### Step 2 — Install Dependensi PHP

```cmd
composer install
```

#### Step 3 — Buat File Environment

```cmd
copy .env.example .env
php artisan key:generate
```

#### Step 4 — Inisialisasi Database & Data Hardware

Perintah ini membuat semua tabel dan mengisi data awal **5 unit hardware** beserta **konfigurasi register Modbus**-nya:

```cmd
php artisan migrate:fresh --seed --force
```

> `--seed` otomatis menjalankan:
> - `DeviceAndRegisterSeeder` — mendaftarkan 5 perangkat + semua register Modbus
> - `MeterLogHistorySeeder` — mengisi riwayat log simulasi agar grafik langsung tampil

#### Step 5 — Jalankan Web Server

```cmd
php artisan serve --host=127.0.0.1 --port=8000
```

> Jangan tutup jendela Command Prompt ini selama aplikasi digunakan.

#### Step 6 — Buka di Browser

| Item | Value |
|---|---|
| URL | `http://127.0.0.1:8000` |
| Username | `admin` |
| Password | `admin123` |

---

## ⏱️ Menjalankan Data Logger (Background Worker)

Data logger membaca semua perangkat secara periodik dan menyimpan hasilnya ke database.

### Eksekusi Sekali (untuk test / debugging)

```bash
php artisan meter:log
```

Output contoh:
```
=== IoT Listrik Data Logger Engine ===
[2026-10-15 06:00:00] Memulai pencatatan 5 device...
  [LIVE] Meter Air RS485 (ID: 1)         => Totalizer: 1482.350 m3
  [LIVE] Meter Air Digital RS485 (ID: 2) => Totalizer: 987.120 m3, Flow: 4.25 m3/h
  [LIVE] Flow Meter RS485 (ID: 3)        => Totalizer: 2341.780 m3, Flow: 6.80 m3/h
  [LIVE] kWh Meter RS485 (ID: 4)         => V: 220.5V, I: 12.3A, kWh: 1580.0kWh
  [LIVE] Power Meter RS485 (ID: 5)       => V: 221.0V, I: 15.8A, kWh: 2340.5kWh
Pencatatan log selesai.
```

> Jika gateway tidak terjangkau, label `[LIVE]` berubah menjadi `[SIM]` (data simulasi otomatis aktif).

---

### Jalankan Terus-menerus (Daemon / Loop)

```bash
# Default interval sesuai pengaturan aplikasi (default: 60 detik)
php artisan meter:log --loop

# Override interval, misal setiap 30 detik
php artisan meter:log --loop --interval=30

# Hanya catat device dengan ID tertentu
php artisan meter:log --loop --device=1
```

Tekan `Ctrl + C` untuk menghentikan.

---

### Jalankan Logger di Background (Windows — CMD Terpisah)

Buka **jendela Command Prompt baru** (jangan tutup cmd yang menjalankan server), lalu:

```cmd
cd C:\path\ke\iotlistrik
php artisan meter:log --loop
```

Jendela cmd ini akan terus berjalan sebagai logger. Tekan `Ctrl + C` untuk menghentikan.

---

### Jadwal Otomatis via Windows Task Scheduler (Production)

Agar logger otomatis berjalan setiap menit tanpa perlu buka cmd secara manual:

1. Buka **Task Scheduler** (`taskschd.msc`) dari menu Start.
2. Klik **Create Basic Task...**
3. Isi nama: `IoT Meter Logger`
4. Trigger: pilih **Daily** → set waktu mulai, centang **Repeat task every: 1 minute**
5. Action: pilih **Start a program**
   - Program: `php`
   - Arguments: `artisan schedule:run`
   - Start in: `C:\path\ke\iotlistrik`
6. Klik **Finish**.

Laravel Scheduler akan menangani interval logger sesuai pengaturan di menu **Pengaturan Sistem** aplikasi.

---

## ⚙️ Konfigurasi Setelah Login

### A. Mengubah IP Gateway & Port

Jika IP gateway Anda bukan `192.168.1.100:502`:

1. Login ke aplikasi.
2. Buka menu **Manajemen Device** di sidebar.
3. Klik tombol **Edit** pada perangkat yang ingin diubah.
4. Ubah kolom **Alamat IP Gateway** dan **Port**.
5. Klik **Simpan**.
6. Klik tombol **Test Koneksi** — jika berhasil, status berubah menjadi ✅ hijau.

> Semua 5 perangkat menggunakan **1 IP gateway yang sama** karena terhubung ke 1 unit gateway. Yang membedakan adalah **Slave ID** masing-masing perangkat.

---

### B. Mengubah Slave ID Perangkat

Jika Slave ID hardware Anda berbeda dari default (1–5):

1. Buka menu **Manajemen Device**.
2. Klik **Edit** pada perangkat terkait.
3. Ubah kolom **Slave ID** sesuai setting yang ada di hardware fisik.
4. Klik **Simpan**.

---

### C. Menyesuaikan Alamat Register Modbus

Setiap merek meter memiliki alamat register yang berbeda. Cek **Modbus Register Map** di buku manual / datasheet hardware Anda, lalu:

1. Buka menu **Peta Register** di sidebar.
2. Pilih perangkat dari dropdown.
3. Klik **Edit** pada register yang ingin diubah:

| Field | Keterangan | Contoh |
|---|---|---|
| **Alamat Register** | Nomor register Modbus (desimal) | `100`, `3000`, `0x0BB8` = `3000` |
| **Function Code** | FC03 = Holding Register, FC04 = Input Register | `3` atau `4` |
| **Format Data** | Tipe data biner nilai register | `float32`, `uint16`, `int32`, `uint32` |
| **Byte Order** | Urutan byte untuk nilai 32-bit | `CDAB`, `ABCD`, `BADC`, `DCBA` |
| **Multiplier** | Pengali skala hasil baca | `1.0`, `0.1`, `0.001` |
| **Unit** | Satuan yang ditampilkan | `m³`, `kWh`, `V`, `A` |

**Panduan Byte Order:**

| Byte Order | Nama Lain | Kapan Digunakan |
|---|---|---|
| `CDAB` | Word Swap / Modicon | Paling umum — Schneider, kebanyakan meter China |
| `ABCD` | Big Endian | Standard IEEE 754 |
| `BADC` | Byte Swap | Beberapa meter Eropa |
| `DCBA` | Little Endian | Jarang — beberapa PLC tertentu |

4. Klik **Simpan Register**.
5. Kembali ke **Dashboard** untuk melihat nilai hasil pembacaan real.

---

### D. Mengubah Interval Pencatatan

1. Buka menu **Pengaturan Sistem** di sidebar.
2. Ubah nilai **Interval Pencatatan** (dalam detik, minimum 1 detik).
3. Klik Simpan — perubahan berlaku pada siklus berikutnya.

---

## 🔍 Troubleshooting

### Gateway Tidak Terjangkau (Data Simulasi Aktif / Status Merah)

| Kemungkinan Penyebab | Solusi |
|---|---|
| IP gateway salah | Cek & edit IP di **Manajemen Device** |
| Gateway belum ON / tidak terhubung ke LAN | Pastikan kabel LAN terpasang, gateway menyala |
| Firewall memblokir port 502 | Buka port 502 di firewall server/PC |
| Server & gateway beda subnet | Pastikan IP keduanya di subnet yang sama (misal `192.168.1.x`) |
| Cache status gateway belum expired | Klik **Test Koneksi** untuk reset cache, atau tunggu 60 detik |

---

### Data Selalu 0 (Terbaca tapi Nol)

| Kemungkinan Penyebab | Solusi |
|---|---|
| Alamat register salah | Cek Modbus Map di datasheet meter, sesuaikan di **Peta Register** |
| Function code salah | Coba ganti FC03 ↔ FC04 |
| Byte order tidak sesuai | Coba urutan: `CDAB` → `ABCD` → `BADC` → `DCBA` |
| Slave ID tidak cocok | Pastikan Slave ID di aplikasi = Slave ID yang di-set di hardware |
| Multiplier terlalu besar/kecil | Sesuaikan multiplier (misal `0.01` jika nilai terlalu besar) |

---

### Modbus Exception Code di Log

| Exception Code | Artinya | Solusi |
|:---:|---|---|
| **1** | Illegal Function | Ganti Function Code (FC03 ↔ FC04) |
| **2** | Illegal Data Address | Alamat register salah, cek datasheet meter |
| **3** | Illegal Data Value | Jumlah register yang diminta tidak valid |
| **4** | Server Failure | Perangkat error, coba restart meter |

---

### Data Logger Tidak Mencatat

```bash
# Cek apakah proses logger masih berjalan
ps aux | grep "meter:log"

# Cek log error Laravel
tail -f storage/logs/laravel.log

# Pastikan Data Logger diaktifkan di UI
# Buka Pengaturan Sistem → Toggle "Data Logger Aktif" harus ON
```

---

## 🗂️ Struktur File Penting

```
iotlistrik/
├── app/
│   ├── Console/Commands/
│   │   └── LogMeterDataCommand.php       # Worker pencatatan data berkala
│   ├── Http/Controllers/
│   │   ├── Api/TelemetryController.php   # REST API live telemetry (dashboard)
│   │   ├── DeviceController.php          # Manajemen device
│   │   ├── RegisterController.php        # Manajemen peta register Modbus
│   │   ├── HistoryController.php         # Riwayat log & ekspor CSV
│   │   └── SettingsController.php        # Pengaturan sistem & interval logger
│   ├── Models/
│   │   ├── Device.php                    # Model perangkat meter
│   │   ├── Register.php                  # Model register Modbus
│   │   └── MeterLog.php                  # Model log telemetri
│   └── Services/
│       └── ModbusTcpService.php          # Driver Modbus TCP (raw PHP socket)
├── database/
│   ├── migrations/                       # Skema tabel database
│   └── seeders/
│       ├── DeviceAndRegisterSeeder.php   # Data awal 5 perangkat + register Modbus
│       └── MeterLogHistorySeeder.php     # Data riwayat simulasi untuk demo
├── resources/views/
│   ├── dashboard.blade.php               # Dashboard SCADA real-time
│   ├── devices/                          # Halaman manajemen device
│   ├── registers/                        # Halaman peta register Modbus
│   └── history/                          # Riwayat log & ekspor
├── run.sh                                # Script launcher Linux/macOS
├── run.bat                               # Script launcher Windows
└── .env                                  # Konfigurasi environment (DB, App Key, dll)
```

---

## 🧪 Menjalankan Automated Test Suite

```bash
# Jalankan semua test
php artisan test --compact

# Jalankan test spesifik
php artisan test --filter=TelemetryTest
```

---

## 📖 Alur Kerja Sistem (Ringkasan)

```
[Hardware RS485]
      │  Modbus RTU (serial 2-kawat)
      ▼
[Gateway RS485-to-TCP/IP]    ← Setting: IP, Port 502, mode TCP Server, baudrate seragam
      │  Modbus TCP (Ethernet / LAN)
      ▼
[ModbusTcpService.php]       ← PHP raw socket, MBAP frame, decode float32/uint16/int32
      │
      ├──► readRegister()          → Baca 1 register spesifik
      └──► readDeviceRegisters()   → Baca semua register aktif 1 device
                 │
                 ▼
       [LogMeterDataCommand]       ← php artisan meter:log --loop
                 │
                 ▼
          [meter_logs tabel]       ← totalizer, flow_rate, voltage, current, energy
                 │
                 ▼
       [TelemetryController API]   ← Endpoint JSON untuk polling dashboard
                 │
                 ▼
          [Dashboard Web]          ← Grafik & kartu nilai real-time (auto-refresh)
```

---

> **Mode Simulasi Otomatis:**
> Jika gateway fisik tidak terjangkau, sistem otomatis menggunakan **data simulasi realistis** sehingga dashboard tetap bisa dilihat dan dikembangkan tanpa hardware fisik. Data simulasi ditandai dengan label `[SIM]` di output logger dan badge kuning di dashboard.
