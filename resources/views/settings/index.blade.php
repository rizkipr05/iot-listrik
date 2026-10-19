@extends('layouts.app')

@section('title', 'Pengaturan Sistem')

@section('styles')
<style>
    .btn-preset-logger {
        font-size: 0.78rem;
        padding: 0.45rem 0.65rem;
        border-radius: 10px;
        transition: all 0.2s ease;
        text-align: center;
    }
    .btn-preset-logger.active {
        background: #2563eb !important;
        color: #ffffff !important;
        border-color: #2563eb !important;
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
    }
</style>
@endsection

@section('content')
<!-- Header & Quick Actions -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill small fw-bold" style="font-size: 0.7rem;">
                <i class="bi bi-sliders me-1"></i> SYSTEM CONFIGURATION
            </span>
            <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 small mono-font" style="font-size: 0.7rem;">
                SQLITE &bull; DAEMON LOGGER &bull; MODBUS
            </span>
        </div>
        <h4 class="fw-bold mb-0 text-dark">Pengaturan Sistem (Settings)</h4>
        <small class="text-muted">Konfigurasi interval data logger, manajemen database SQLite, dan profil akun operator.</small>
    </div>
</div>

<!-- 4 Summary KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 border-0 shadow-sm h-100" style="border-radius: 14px; border-left: 4px solid #2563eb !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">Total Meter Terdaftar</span>
                    <h3 class="fw-bold mb-0 text-dark mono-font mt-1">{{ $totalDevices }} <span class="fs-6 fw-normal text-muted">Unit</span></h3>
                </div>
                <div class="p-2 bg-primary-subtle text-primary rounded-3 fs-4">
                    <i class="bi bi-hdd-network"></i>
                </div>
            </div>
            <small class="text-muted mt-2 d-block" style="font-size: 0.74rem;">Instrumen Modbus TCP aktif</small>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 border-0 shadow-sm h-100" style="border-radius: 14px; border-left: 4px solid #059669 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">Titik Register</span>
                    <h3 class="fw-bold mb-0 text-success mono-font mt-1">{{ $totalRegisters }} <span class="fs-6 fw-normal text-muted">Titik</span></h3>
                </div>
                <div class="p-2 bg-success-subtle text-success rounded-3 fs-4">
                    <i class="bi bi-list-columns-reverse"></i>
                </div>
            </div>
            <small class="text-muted mt-2 d-block" style="font-size: 0.74rem;">Parameter holding & input</small>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 border-0 shadow-sm h-100" style="border-radius: 14px; border-left: 4px solid #d97706 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">Data Log Tersimpan</span>
                    <h3 class="fw-bold mb-0 text-warning mono-font mt-1">{{ number_format($totalLogs) }} <span class="fs-6 fw-normal text-muted">Baris</span></h3>
                </div>
                <div class="p-2 bg-warning-subtle text-warning rounded-3 fs-4">
                    <i class="bi bi-database-check"></i>
                </div>
            </div>
            <small class="text-muted mt-2 d-block" style="font-size: 0.74rem;">Tercatat di SQLite lokal</small>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 border-0 shadow-sm h-100" style="border-radius: 14px; border-left: 4px solid #7c3aed !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">Ukuran Database</span>
                    <h3 class="fw-bold mb-0 text-purple mono-font mt-1" style="color: #7c3aed;">{{ $dbSizeFormatted }}</h3>
                </div>
                <div class="p-2 bg-purple-subtle rounded-3 fs-4" style="background: rgba(124, 58, 237, 0.1); color: #7c3aed;">
                    <i class="bi bi-hdd-fill"></i>
                </div>
            </div>
            <small class="text-muted mt-2 d-block mono-font" style="font-size: 0.74rem;">database.sqlite</small>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Kartu Pengaturan Data Logger -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 h-100" style="border-radius: 16px;">
            <div class="card-header bg-white py-3 px-4 d-flex align-items-center gap-2 border-bottom">
                <div class="p-2 bg-primary-subtle text-primary rounded-3">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-0">Pengaturan Interval Data Logger</h6>
                    <small class="text-muted">Atur frekuensi pencatatan telemetri periodik ke database lokal</small>
                </div>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('settings.logger.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Switch Aktif / Non-aktif Data Logger -->
                    <div class="mb-4 p-3 bg-light rounded-3 border">
                        <div class="form-check form-switch mb-1">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   role="switch" 
                                   id="logger_enabled" 
                                   name="logger_enabled" 
                                   value="1" 
                                   {{ $loggerEnabled ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold text-dark" for="logger_enabled">
                                Status Data Logger Otomatis
                            </label>
                        </div>
                        <small class="text-muted d-block ps-4">
                            Jika aktif, sistem merekam pembacaan seluruh register ke tabel <code>meter_logs</code> sesuai interval.
                        </small>
                    </div>

                    <!-- Preset Interval Cepat (Lengkap: Detik, Menit, Jam, Hari, Minggu, Bulan) -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Preset Interval Cepat</label>
                        <div class="row g-2 mb-2">
                            <!-- Detik -->
                            <div class="col-4 col-sm-3">
                                <button type="button" class="btn btn-outline-secondary w-100 btn-preset-logger {{ $loggerIntervalSeconds == 5 ? 'active' : '' }}" data-seconds="5">
                                    5 Detik
                                </button>
                            </div>
                            <div class="col-4 col-sm-3">
                                <button type="button" class="btn btn-outline-secondary w-100 btn-preset-logger {{ $loggerIntervalSeconds == 10 ? 'active' : '' }}" data-seconds="10">
                                    10 Detik
                                </button>
                            </div>
                            <div class="col-4 col-sm-3">
                                <button type="button" class="btn btn-outline-secondary w-100 btn-preset-logger {{ $loggerIntervalSeconds == 30 ? 'active' : '' }}" data-seconds="30">
                                    30 Detik
                                </button>
                            </div>
                            <!-- Menit -->
                            <div class="col-4 col-sm-3">
                                <button type="button" class="btn btn-outline-secondary w-100 btn-preset-logger {{ $loggerIntervalSeconds == 60 ? 'active' : '' }}" data-seconds="60">
                                    1 Menit
                                </button>
                            </div>
                            <div class="col-4 col-sm-3">
                                <button type="button" class="btn btn-outline-secondary w-100 btn-preset-logger {{ $loggerIntervalSeconds == 300 ? 'active' : '' }}" data-seconds="300">
                                    5 Menit
                                </button>
                            </div>
                            <!-- Jam -->
                            <div class="col-4 col-sm-3">
                                <button type="button" class="btn btn-outline-secondary w-100 btn-preset-logger {{ $loggerIntervalSeconds == 3600 ? 'active' : '' }}" data-seconds="3600">
                                    1 Jam
                                </button>
                            </div>
                            <!-- Hari / Minggu / Bulan -->
                            <div class="col-4 col-sm-3">
                                <button type="button" class="btn btn-outline-secondary w-100 btn-preset-logger {{ $loggerIntervalSeconds == 86400 ? 'active' : '' }}" data-seconds="86400">
                                    1 Hari (Harian)
                                </button>
                            </div>
                            <div class="col-4 col-sm-3">
                                <button type="button" class="btn btn-outline-secondary w-100 btn-preset-logger {{ $loggerIntervalSeconds == 604800 ? 'active' : '' }}" data-seconds="604800">
                                    1 Minggu
                                </button>
                            </div>
                            <div class="col-4 col-sm-3">
                                <button type="button" class="btn btn-outline-secondary w-100 btn-preset-logger {{ $loggerIntervalSeconds == 2592000 ? 'active' : '' }}" data-seconds="2592000">
                                    1 Bulan
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Input Kustom Detik & Kalkulator Satuan -->
                    <div class="mb-4">
                        <label for="logger_interval_seconds" class="form-label fw-bold small text-secondary">
                            Durasi Interval Detik Kustom <span class="text-danger">*</span>
                        </label>
                        <div class="input-group mb-2">
                            <input type="number" 
                                   class="form-control mono-font @error('logger_interval_seconds') is-invalid @enderror" 
                                   id="logger_interval_seconds" 
                                   name="logger_interval_seconds" 
                                   value="{{ old('logger_interval_seconds', $loggerIntervalSeconds) }}" 
                                   min="1" 
                                   max="31536000" 
                                   required>
                            <span class="input-group-text bg-light text-muted mono-font">Detik</span>
                        </div>
                        <div class="p-2 bg-light rounded-2 border d-flex flex-wrap align-items-center gap-2 small text-muted">
                            <span class="fw-semibold text-dark">Kalkulator Cepat:</span>
                            <div class="d-flex align-items-center gap-1">
                                <input type="number" id="calcValue" class="form-control form-control-sm mono-font" style="width: 70px;" value="1" min="1">
                                <select id="calcUnit" class="form-select form-select-sm" style="width: 100px;">
                                    <option value="1">Detik</option>
                                    <option value="60">Menit</option>
                                    <option value="3600">Jam</option>
                                    <option value="86400">Hari</option>
                                    <option value="604800">Minggu</option>
                                    <option value="2592000">Bulan</option>
                                </select>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnApplyCalc">
                                    Terapkan &rarr;
                                </button>
                            </div>
                        </div>
                        @error('logger_interval_seconds')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="bi bi-floppy me-1"></i> Simpan Pengaturan Logger
                    </button>
                </form>
            </div>
        </div>

        <!-- Kartu Pengaturan Skala & Rentang Maksimal Meter (Kondisi 100% Gauge) -->
        <div class="card shadow-sm border-0 mt-4" style="border-radius: 16px;">
            <div class="card-header bg-white py-3 px-4 d-flex align-items-center gap-2 border-bottom">
                <div class="p-2 bg-info-subtle text-info rounded-3">
                    <i class="bi bi-speedometer2"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-0">Pengaturan Skala & Rentang Maksimal (Kondisi 100% Gauge)</h6>
                    <small class="text-muted">Tentukan kapasitas aliran (m³/h) dan tekanan (Bar) acuan 100% pada circular gauge SCADA</small>
                </div>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('settings.scales.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Meter 1: RMS -->
                    <div class="mb-3 p-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="max_flowrate_rms" class="form-label fw-bold small text-dark mb-0">
                                <i class="bi bi-droplet-fill text-primary me-1"></i> Flowmeter 1 (Area RMS): {{ $labelRms }}
                            </label>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill small px-2">Meter 1</span>
                        </div>
                        <small class="text-muted d-block mb-2">Batas kapasitas maksimal (Range 0 ~ X m³/h) saat flowrate mencapai 100%:</small>
                        <div class="input-group input-group-sm mb-2">
                            <input type="number" step="0.1" min="0.1" max="10000" class="form-control mono-font fw-bold" id="max_flowrate_rms" name="max_flowrate_rms" value="{{ $maxFlowRms }}" required>
                            <span class="input-group-text bg-white mono-font">m³/h</span>
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            <span class="small text-muted me-1 align-self-center" style="font-size: 0.72rem;">Preset:</span>
                            <button type="button" class="btn btn-outline-secondary py-0 px-2" style="font-size: 0.72rem; border-radius: 6px;" onclick="document.getElementById('max_flowrate_rms').value = '4.0'">4 m³/h (DN20 3/4")</button>
                            <button type="button" class="btn btn-outline-secondary py-0 px-2" style="font-size: 0.72rem; border-radius: 6px;" onclick="document.getElementById('max_flowrate_rms').value = '10.0'">10 m³/h (DN25 1")</button>
                            <button type="button" class="btn btn-outline-secondary py-0 px-2" style="font-size: 0.72rem; border-radius: 6px;" onclick="document.getElementById('max_flowrate_rms').value = '25.0'">25 m³/h (DN40 1.5")</button>
                            <button type="button" class="btn btn-outline-secondary py-0 px-2" style="font-size: 0.72rem; border-radius: 6px;" onclick="document.getElementById('max_flowrate_rms').value = '50.0'">50 m³/h (DN50 2")</button>
                            <button type="button" class="btn btn-outline-secondary py-0 px-2" style="font-size: 0.72rem; border-radius: 6px;" onclick="document.getElementById('max_flowrate_rms').value = '300.0'">300 m³/h (DN150 6")</button>
                        </div>
                    </div>

                    <!-- Meter 2: MHS / Toilet -->
                    <div class="mb-3 p-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="max_flowrate_mhs" class="form-label fw-bold small text-dark mb-0">
                                <i class="bi bi-droplet-fill text-success me-1"></i> Flowmeter 2 (Area Toilet): {{ $labelMhs }}
                            </label>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small px-2">Meter 2</span>
                        </div>
                        <small class="text-muted d-block mb-2">Batas kapasitas maksimal (Range 0 ~ X m³/h) saat flowrate mencapai 100%:</small>
                        <div class="input-group input-group-sm mb-2">
                            <input type="number" step="0.1" min="0.1" max="10000" class="form-control mono-font fw-bold" id="max_flowrate_mhs" name="max_flowrate_mhs" value="{{ $maxFlowMhs }}" required>
                            <span class="input-group-text bg-white mono-font">m³/h</span>
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            <span class="small text-muted me-1 align-self-center" style="font-size: 0.72rem;">Preset:</span>
                            <button type="button" class="btn btn-outline-secondary py-0 px-2" style="font-size: 0.72rem; border-radius: 6px;" onclick="document.getElementById('max_flowrate_mhs').value = '4.0'">4 m³/h</button>
                            <button type="button" class="btn btn-outline-secondary py-0 px-2" style="font-size: 0.72rem; border-radius: 6px;" onclick="document.getElementById('max_flowrate_mhs').value = '10.0'">10 m³/h</button>
                            <button type="button" class="btn btn-outline-secondary py-0 px-2" style="font-size: 0.72rem; border-radius: 6px;" onclick="document.getElementById('max_flowrate_mhs').value = '50.0'">50 m³/h</button>
                            <button type="button" class="btn btn-outline-secondary py-0 px-2" style="font-size: 0.72rem; border-radius: 6px;" onclick="document.getElementById('max_flowrate_mhs').value = '300.0'">300 m³/h</button>
                        </div>
                    </div>

                    <!-- Meter 3: H-RMS / Hydrant -->
                    <div class="mb-3 p-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="max_flowrate_hrms" class="form-label fw-bold small text-dark mb-0">
                                <i class="bi bi-droplet-fill text-warning me-1"></i> Flowmeter 3 (Area Hydrant): {{ $labelHrms }}
                            </label>
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill small px-2">Meter 3</span>
                        </div>
                        <small class="text-muted d-block mb-2">Batas kapasitas maksimal (Range 0 ~ X m³/h) saat flowrate mencapai 100%:</small>
                        <div class="input-group input-group-sm mb-2">
                            <input type="number" step="0.1" min="0.1" max="10000" class="form-control mono-font fw-bold" id="max_flowrate_hrms" name="max_flowrate_hrms" value="{{ $maxFlowHrms }}" required>
                            <span class="input-group-text bg-white mono-font">m³/h</span>
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            <span class="small text-muted me-1 align-self-center" style="font-size: 0.72rem;">Preset:</span>
                            <button type="button" class="btn btn-outline-secondary py-0 px-2" style="font-size: 0.72rem; border-radius: 6px;" onclick="document.getElementById('max_flowrate_hrms').value = '10.0'">10 m³/h</button>
                            <button type="button" class="btn btn-outline-secondary py-0 px-2" style="font-size: 0.72rem; border-radius: 6px;" onclick="document.getElementById('max_flowrate_hrms').value = '50.0'">50 m³/h</button>
                            <button type="button" class="btn btn-outline-secondary py-0 px-2" style="font-size: 0.72rem; border-radius: 6px;" onclick="document.getElementById('max_flowrate_hrms').value = '100.0'">100 m³/h</button>
                            <button type="button" class="btn btn-outline-secondary py-0 px-2" style="font-size: 0.72rem; border-radius: 6px;" onclick="document.getElementById('max_flowrate_hrms').value = '300.0'">300 m³/h</button>
                        </div>
                    </div>

                    <!-- Meter 4: Pressure Sensor -->
                    <div class="mb-3 p-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="max_pressure" class="form-label fw-bold small text-dark mb-0">
                                <i class="bi bi-gauge2 text-danger me-1"></i> Sensor Tekanan: {{ $labelPh }}
                            </label>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill small px-2">Pressure</span>
                        </div>
                        <small class="text-muted d-block mb-2">Batas kapasitas tekanan maksimal (Range 0 ~ X Bar) untuk 100%:</small>
                        <div class="input-group input-group-sm mb-2">
                            <input type="number" step="0.1" min="0.1" max="1000" class="form-control mono-font fw-bold" id="max_pressure" name="max_pressure" value="{{ $maxPressure }}" required>
                            <span class="input-group-text bg-white mono-font">Bar</span>
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            <span class="small text-muted me-1 align-self-center" style="font-size: 0.72rem;">Preset:</span>
                            <button type="button" class="btn btn-outline-secondary py-0 px-2" style="font-size: 0.72rem; border-radius: 6px;" onclick="document.getElementById('max_pressure').value = '4.0'">4 Bar</button>
                            <button type="button" class="btn btn-outline-secondary py-0 px-2" style="font-size: 0.72rem; border-radius: 6px;" onclick="document.getElementById('max_pressure').value = '6.0'">6 Bar</button>
                            <button type="button" class="btn btn-outline-secondary py-0 px-2" style="font-size: 0.72rem; border-radius: 6px;" onclick="document.getElementById('max_pressure').value = '10.0'">10 Bar</button>
                            <button type="button" class="btn btn-outline-secondary py-0 px-2" style="font-size: 0.72rem; border-radius: 6px;" onclick="document.getElementById('max_pressure').value = '16.0'">16 Bar</button>
                        </div>
                    </div>

                    <!-- Ambang Batas Peringatan / Warning Alarm % -->
                    <div class="mb-4 p-3 bg-light rounded-3 border">
                        <label for="flow_warning_percent" class="form-label fw-bold small text-dark mb-1">
                            <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> Ambang Batas Aliran Tinggi (High Flow Warning %)
                        </label>
                        <small class="text-muted d-block mb-2">Persentase flowrate (%) yang dianggap kondisi tinggi / mendekati kapasitas maksimal:</small>
                        <div class="input-group input-group-sm">
                            <input type="number" min="1" max="100" class="form-control mono-font fw-bold" id="flow_warning_percent" name="flow_warning_percent" value="{{ $flowWarningPct }}">
                            <span class="input-group-text bg-white mono-font">%</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-info text-white rounded-pill px-4 shadow-sm">
                        <i class="bi bi-floppy me-1"></i> Simpan Pengaturan Rentang Maksimal
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Ringkasan Navigasi & Backup Database -->
    <div class="col-lg-5">
        <div class="d-flex flex-column gap-3">
            <!-- Backup / Download Database SQLite -->
            <div class="card shadow-sm border-0 p-3" style="border-radius: 16px; background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="p-3 bg-success-subtle text-success rounded-3 fs-4">
                        <i class="bi bi-database-down"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Backup Database SQLite</h6>
                        <small class="text-muted">Unduh salinan penuh seluruh konfigurasi dan data log.</small>
                    </div>
                </div>
                <div class="bg-white p-2 px-3 rounded-3 border mb-3 small d-flex justify-content-between align-items-center">
                    <span class="text-muted">Ukuran File: <strong class="mono-font text-dark">{{ $dbSizeFormatted }}</strong></span>
                    <span class="text-muted">Log Terakhir: <strong class="mono-font text-dark" style="font-size: 0.75rem;">{{ $lastLoggedAt }}</strong></span>
                </div>
                <a href="{{ route('settings.backup-db') }}" class="btn btn-outline-success rounded-pill w-100 shadow-sm">
                    <i class="bi bi-download me-1"></i> Unduh File Database (.sqlite)
                </a>
            </div>

            <!-- Akun Pengguna & Password -->
            <div class="card shadow-sm border-0 p-3" style="border-radius: 16px;">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 bg-primary-subtle text-primary rounded-3 fs-4">
                            <i class="bi bi-person-gear"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Akun Operator & Password</h6>
                            <small class="text-muted">Ganti username login dan kata sandi akun.</small>
                        </div>
                    </div>
                    <a href="{{ route('settings.profile') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        Kelola &rarr;
                    </a>
                </div>
            </div>

            <!-- Device Meter Management Link -->
            <div class="card shadow-sm border-0 p-3" style="border-radius: 16px;">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 bg-info-subtle text-info rounded-3 fs-4">
                            <i class="bi bi-hdd-network"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Device Meter (5 Unit)</h6>
                            <small class="text-muted">{{ $totalDevices }} unit instrumen meter terdaftar.</small>
                        </div>
                    </div>
                    <a href="{{ route('devices.index') }}" class="btn btn-sm btn-outline-info rounded-pill px-3">
                        Kelola &rarr;
                    </a>
                </div>
            </div>

            <!-- Register Modbus Mapping Link -->
            <div class="card shadow-sm border-0 p-3" style="border-radius: 16px;">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 bg-warning-subtle text-warning rounded-3 fs-4">
                            <i class="bi bi-list-columns-reverse"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Register Modbus Mapping</h6>
                            <small class="text-muted">{{ $totalRegisters }} titik parameter register aktif.</small>
                        </div>
                    </div>
                    <a href="{{ route('registers.index') }}" class="btn btn-sm btn-outline-warning rounded-pill px-3">
                        Kelola &rarr;
                    </a>
                </div>
            </div>

            <!-- Panduan Local Daemon Logger -->
            <div class="card shadow-sm border-0 bg-dark text-white p-3" style="border-radius: 16px;">
                <h6 class="fw-bold mb-1 text-info"><i class="bi bi-terminal-fill me-2"></i>Perintah Background Logger (Localhost)</h6>
                <p class="small text-secondary mb-2" style="font-size: 0.76rem;">
                    Jalankan perintah ini di Command Prompt / Terminal untuk logging otomatis terus-menerus:
                </p>
                <div class="bg-black p-2 rounded-3 border border-secondary border-opacity-50 mono-font small text-warning mb-2" style="font-size: 0.78rem;">
                    php artisan meter:log --loop
                </div>
                <small class="text-secondary" style="font-size: 0.72rem;">
                    Atau gunakan launcher <code>run.bat</code> / <code>run.sh</code> bawaan untuk auto-start.
                </small>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Presets Button Click Handler
    document.querySelectorAll('.btn-preset-logger').forEach(button => {
        button.addEventListener('click', function() {
            const seconds = this.getAttribute('data-seconds');
            document.getElementById('logger_interval_seconds').value = seconds;

            document.querySelectorAll('.btn-preset-logger').forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
        });
    });

    // Unit Calculator Helper
    document.getElementById('btnApplyCalc').addEventListener('click', function() {
        const val = Number(document.getElementById('calcValue').value) || 1;
        const multiplier = Number(document.getElementById('calcUnit').value) || 1;
        const totalSeconds = val * multiplier;
        
        document.getElementById('logger_interval_seconds').value = totalSeconds;
        
        // Match with any preset if equal
        document.querySelectorAll('.btn-preset-logger').forEach(btn => {
            if (btn.getAttribute('data-seconds') == totalSeconds) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    });
</script>
@endsection
