@extends('layouts.app')

@section('title', 'Pengaturan Skala Maksimal Gauge')

@section('styles')
<style>
    .scale-meter-card {
        border-radius: 16px;
        border: 1px solid rgba(226, 232, 240, 0.9);
        background: #ffffff;
        position: relative;
        overflow: hidden;
        transition: all 0.25s ease;
        box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04);
    }
    .scale-meter-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px -4px rgba(15, 23, 42, 0.08);
    }
    .scale-meter-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
    }
    .scale-meter-card.meter-rms::before { background: linear-gradient(90deg, #2563eb, #06b6d4); }
    .scale-meter-card.meter-mhs::before { background: linear-gradient(90deg, #059669, #10b981); }
    .scale-meter-card.meter-hrms::before { background: linear-gradient(90deg, #7c3aed, #a855f7); }
    .scale-meter-card.meter-ph::before { background: linear-gradient(90deg, #d97706, #f59e0b); }

    .preset-chip-btn {
        font-size: 0.74rem;
        padding: 0.22rem 0.55rem;
        border-radius: 20px;
        transition: all 0.15s ease;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        color: #334155;
    }
    .preset-chip-btn:hover {
        background: #e2e8f0;
        color: #0f172a;
    }
</style>
@endsection

@section('content')
<!-- Header & Navigasi Cepat -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-pill small fw-bold" style="font-size: 0.7rem;">
                <i class="bi bi-speedometer2 me-1"></i> GAUGE SCALE CALIBRATION
            </span>
            <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 small mono-font" style="font-size: 0.7rem;">
                SCADA FULL SCALE 100%
            </span>
        </div>
        <h4 class="fw-bold mb-0 text-dark">Pengaturan Skala Maksimal Gauge Meter</h4>
        <small class="text-muted">Tentukan kapasitas aliran (m³/h) dan tekanan (Bar) sebagai acuan 100% penuh lingkaran meter pada dashboard.</small>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('dashboard') }}" class="btn btn-outline-primary rounded-pill px-3 shadow-sm">
            <i class="bi bi-speedometer2 me-1"></i> Buka Dashboard
        </a>
        <a href="{{ route('settings.index') }}" class="btn btn-light border rounded-pill px-3 shadow-sm">
            <i class="bi bi-sliders me-1"></i> Pengaturan Sistem
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>
                <strong>Berhasil Disimpan!</strong> {{ session('success') }}
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div>
                <strong>Perhatian:</strong> Mohon periksa kembali input skala yang Anda masukkan.
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Kotak Panduan Skala SCADA -->
<div class="alert alert-info py-3 px-4 rounded-4 border-0 shadow-sm mb-4" style="background: rgba(2, 132, 199, 0.08); color: #0369a1;">
    <div class="d-flex gap-3 align-items-start">
        <div class="fs-4 lh-1 text-primary">
            <i class="bi bi-info-circle-fill"></i>
        </div>
        <div class="small">
            <h6 class="fw-bold text-dark mb-1" style="font-size: 0.9rem;">Prinsip Normalisasi Skala 100% Gauge</h6>
            <p class="mb-0 text-secondary">
                Nilai yang Anda atur pada halaman ini menentukan titik <strong>100% penuh</strong> pada putaran lingkaran meter di halaman utama Dashboard.
                Contoh: Jika Anda mengatur skala Flowmeter 1 sebesar <strong>4.0 m³/h</strong>, maka saat aliran air mengalir <strong>1.5 m³/h</strong>, jarum/lingkaran meter akan otomatis menunjukkan <strong>38%</strong>.
            </p>
        </div>
    </div>
</div>

<form action="{{ route('settings.scales.update') }}" method="POST">
    @csrf
    @method('PUT')

    <div class="row g-4 mb-4">
        <!-- 1. Meter 1: Flowmeter RMS -->
        <div class="col-md-6">
            <div class="card scale-meter-card meter-rms h-100 p-4">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill small px-2 py-1 mb-2 fw-bold" style="font-size: 0.7rem;">
                            METER 1 (SLAVE ID: 1)
                        </span>
                        <h5 class="fw-bold text-dark mb-0">{{ $labelRms }}</h5>
                        <small class="text-muted d-block mt-1">{{ $descRms }}</small>
                    </div>
                    <div class="p-2 bg-primary-subtle text-primary rounded-3 fs-4">
                        <i class="bi bi-droplet-fill"></i>
                    </div>
                </div>

                <div class="my-3">
                    <label for="max_flowrate_rms" class="form-label fw-bold small text-secondary">
                        Kapasitas Aliran Penuh (100% Gauge) <span class="text-danger">*</span>
                    </label>
                    <div class="input-group mb-2">
                        <input type="number" step="0.1" min="0.1" max="10000" 
                               class="form-control form-control-lg mono-font fw-bold text-primary @error('max_flowrate_rms') is-invalid @enderror" 
                               id="max_flowrate_rms" 
                               name="max_flowrate_rms" 
                               value="{{ old('max_flowrate_rms', $maxFlowRms) }}" 
                               required>
                        <span class="input-group-text bg-light mono-font fw-bold text-muted">m³/h</span>
                    </div>
                    @error('max_flowrate_rms')
                        <div class="text-danger small mb-2">{{ $message }}</div>
                    @enderror

                    <!-- Preset Cepat Ukuran Pipa -->
                    <div class="d-flex flex-wrap align-items-center gap-1 mt-2">
                        <span class="small text-muted me-1 fw-semibold" style="font-size: 0.72rem;">Preset:</span>
                        <button type="button" class="btn preset-chip-btn" onclick="applyPreset('max_flowrate_rms', 4.0)">4 m³/h (3/4")</button>
                        <button type="button" class="btn preset-chip-btn" onclick="applyPreset('max_flowrate_rms', 10.0)">10 m³/h (1")</button>
                        <button type="button" class="btn preset-chip-btn" onclick="applyPreset('max_flowrate_rms', 25.0)">25 m³/h (1.5")</button>
                        <button type="button" class="btn preset-chip-btn" onclick="applyPreset('max_flowrate_rms', 50.0)">50 m³/h (2")</button>
                        <button type="button" class="btn preset-chip-btn" onclick="applyPreset('max_flowrate_rms', 300.0)">300 m³/h (6")</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Meter 2: Flowmeter MHS (Toilet) -->
        <div class="col-md-6">
            <div class="card scale-meter-card meter-mhs h-100 p-4">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small px-2 py-1 mb-2 fw-bold" style="font-size: 0.7rem;">
                            METER 2 (SLAVE ID: 2)
                        </span>
                        <h5 class="fw-bold text-dark mb-0">{{ $labelMhs }}</h5>
                        <small class="text-muted d-block mt-1">{{ $descMhs }}</small>
                    </div>
                    <div class="p-2 bg-success-subtle text-success rounded-3 fs-4">
                        <i class="bi bi-droplet-fill"></i>
                    </div>
                </div>

                <div class="my-3">
                    <label for="max_flowrate_mhs" class="form-label fw-bold small text-secondary">
                        Kapasitas Aliran Penuh (100% Gauge) <span class="text-danger">*</span>
                    </label>
                    <div class="input-group mb-2">
                        <input type="number" step="0.1" min="0.1" max="10000" 
                               class="form-control form-control-lg mono-font fw-bold text-success @error('max_flowrate_mhs') is-invalid @enderror" 
                               id="max_flowrate_mhs" 
                               name="max_flowrate_mhs" 
                               value="{{ old('max_flowrate_mhs', $maxFlowMhs) }}" 
                               required>
                        <span class="input-group-text bg-light mono-font fw-bold text-muted">m³/h</span>
                    </div>
                    @error('max_flowrate_mhs')
                        <div class="text-danger small mb-2">{{ $message }}</div>
                    @enderror

                    <!-- Preset Cepat Ukuran Pipa -->
                    <div class="d-flex flex-wrap align-items-center gap-1 mt-2">
                        <span class="small text-muted me-1 fw-semibold" style="font-size: 0.72rem;">Preset:</span>
                        <button type="button" class="btn preset-chip-btn" onclick="applyPreset('max_flowrate_mhs', 4.0)">4 m³/h</button>
                        <button type="button" class="btn preset-chip-btn" onclick="applyPreset('max_flowrate_mhs', 10.0)">10 m³/h</button>
                        <button type="button" class="btn preset-chip-btn" onclick="applyPreset('max_flowrate_mhs', 25.0)">25 m³/h</button>
                        <button type="button" class="btn preset-chip-btn" onclick="applyPreset('max_flowrate_mhs', 50.0)">50 m³/h</button>
                        <button type="button" class="btn preset-chip-btn" onclick="applyPreset('max_flowrate_mhs', 300.0)">300 m³/h</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Meter 3: Flowmeter H-RMS (Office) -->
        <div class="col-md-6">
            <div class="card scale-meter-card meter-hrms h-100 p-4">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="badge bg-purple-subtle text-purple border rounded-pill small px-2 py-1 mb-2 fw-bold" style="background: rgba(124, 58, 237, 0.1); color: #7c3aed; font-size: 0.7rem;">
                            METER 3 (SLAVE ID: 3)
                        </span>
                        <h5 class="fw-bold text-dark mb-0">{{ $labelHrms }}</h5>
                        <small class="text-muted d-block mt-1">{{ $descHrms }}</small>
                    </div>
                    <div class="p-2 rounded-3 fs-4" style="background: rgba(124, 58, 237, 0.1); color: #7c3aed;">
                        <i class="bi bi-droplet-fill"></i>
                    </div>
                </div>

                <div class="my-3">
                    <label for="max_flowrate_hrms" class="form-label fw-bold small text-secondary">
                        Kapasitas Aliran Penuh (100% Gauge) <span class="text-danger">*</span>
                    </label>
                    <div class="input-group mb-2">
                        <input type="number" step="0.1" min="0.1" max="10000" 
                               class="form-control form-control-lg mono-font fw-bold @error('max_flowrate_hrms') is-invalid @enderror" 
                               style="color: #7c3aed;"
                               id="max_flowrate_hrms" 
                               name="max_flowrate_hrms" 
                               value="{{ old('max_flowrate_hrms', $maxFlowHrms) }}" 
                               required>
                        <span class="input-group-text bg-light mono-font fw-bold text-muted">m³/h</span>
                    </div>
                    @error('max_flowrate_hrms')
                        <div class="text-danger small mb-2">{{ $message }}</div>
                    @enderror

                    <!-- Preset Cepat Ukuran Pipa -->
                    <div class="d-flex flex-wrap align-items-center gap-1 mt-2">
                        <span class="small text-muted me-1 fw-semibold" style="font-size: 0.72rem;">Preset:</span>
                        <button type="button" class="btn preset-chip-btn" onclick="applyPreset('max_flowrate_hrms', 4.0)">4 m³/h</button>
                        <button type="button" class="btn preset-chip-btn" onclick="applyPreset('max_flowrate_hrms', 10.0)">10 m³/h</button>
                        <button type="button" class="btn preset-chip-btn" onclick="applyPreset('max_flowrate_hrms', 25.0)">25 m³/h</button>
                        <button type="button" class="btn preset-chip-btn" onclick="applyPreset('max_flowrate_hrms', 50.0)">50 m³/h</button>
                        <button type="button" class="btn preset-chip-btn" onclick="applyPreset('max_flowrate_hrms', 300.0)">300 m³/h</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Meter 4: Pressure Sensor -->
        <div class="col-md-6">
            <div class="card scale-meter-card meter-ph h-100 p-4">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill small px-2 py-1 mb-2 fw-bold" style="font-size: 0.7rem;">
                            METER 4 (SLAVE ID: 4)
                        </span>
                        <h5 class="fw-bold text-dark mb-0">{{ $labelPh }}</h5>
                        <small class="text-muted d-block mt-1">{{ $descPh }} &bull; Normal: 1.0 ~ 4.0 Bar</small>
                    </div>
                    <div class="p-2 bg-warning-subtle text-warning rounded-3 fs-4">
                        <i class="bi bi-speedometer2"></i>
                    </div>
                </div>

                <div class="my-3">
                    <label for="max_pressure" class="form-label fw-bold small text-secondary">
                        Kapasitas Tekanan Penuh (100% Gauge) <span class="text-danger">*</span>
                    </label>
                    <div class="input-group mb-2">
                        <input type="number" step="0.1" min="0.1" max="1000" 
                               class="form-control form-control-lg mono-font fw-bold text-warning-emphasis @error('max_pressure') is-invalid @enderror" 
                               id="max_pressure" 
                               name="max_pressure" 
                               value="{{ old('max_pressure', $maxPressure) }}" 
                               required>
                        <span class="input-group-text bg-light mono-font fw-bold text-muted">Bar</span>
                    </div>
                    @error('max_pressure')
                        <div class="text-danger small mb-2">{{ $message }}</div>
                    @enderror

                    <!-- Preset Cepat Bar -->
                    <div class="d-flex flex-wrap align-items-center gap-1 mt-2">
                        <span class="small text-muted me-1 fw-semibold" style="font-size: 0.72rem;">Preset:</span>
                        <button type="button" class="btn preset-chip-btn" onclick="applyPreset('max_pressure', 4.0)">4 Bar</button>
                        <button type="button" class="btn preset-chip-btn" onclick="applyPreset('max_pressure', 6.0)">6 Bar</button>
                        <button type="button" class="btn preset-chip-btn" onclick="applyPreset('max_pressure', 10.0)">10 Bar (Standar)</button>
                        <button type="button" class="btn preset-chip-btn" onclick="applyPreset('max_pressure', 16.0)">16 Bar</button>
                        <button type="button" class="btn preset-chip-btn" onclick="applyPreset('max_pressure', 25.0)">25 Bar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ambang Batas Peringatan Aliran Tinggi (%) -->
    <div class="card shadow-sm border-0 rounded-4 p-4 mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-bell-fill text-warning fs-5"></i>
            <div>
                <h6 class="fw-bold text-dark mb-0">Ambang Batas Peringatan Aliran Tinggi (High Flow Warning)</h6>
                <small class="text-muted">Persentase aliran yang memicu perubahan warna status gauge menjadi kuning / peringatan.</small>
            </div>
        </div>
        <div class="row align-items-center g-3">
            <div class="col-sm-4 col-md-3">
                <div class="input-group">
                    <input type="number" min="1" max="100" class="form-control mono-font fw-bold text-center" 
                           id="flow_warning_percent" name="flow_warning_percent" 
                           value="{{ old('flow_warning_percent', $flowWarningPct) }}">
                    <span class="input-group-text bg-light mono-font">%</span>
                </div>
            </div>
            <div class="col-sm-8 col-md-9">
                <small class="text-muted">
                    Rekomendasi: <strong>80% ~ 90%</strong>. Saat debit aliran melewati persentase ini dari kapasitas maksimal, visualisasi indikator gauge akan otomatis berubah status menjadi <em>Warning</em>.
                </small>
            </div>
        </div>
    </div>

    <!-- Tombol Simpan Aksi -->
    <div class="d-flex flex-wrap gap-2 justify-content-end">
        <a href="{{ route('dashboard') }}" class="btn btn-light border rounded-pill px-4 py-2">
            Batal
        </a>
        <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 shadow fw-bold">
            <i class="bi bi-floppy me-1"></i> Simpan Pengaturan Skala Gauge
        </button>
    </div>
</form>
@endsection

@section('scripts')
<script>
    function applyPreset(inputId, value) {
        const el = document.getElementById(inputId);
        if (el) {
            el.value = value;
            el.focus();
            // Efek highlight sesaat
            el.classList.add('bg-warning-subtle');
            setTimeout(() => {
                el.classList.remove('bg-warning-subtle');
            }, 300);
        }
    }
</script>
@endsection

