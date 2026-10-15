@extends('layouts.app')

@section('title', 'Tambah Device Meter')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Tambah Device Meter Baru</h4>
        <p class="text-muted small mb-0">Konfigurasikan gateway RS485-to-TCP/IP dan Modbus Slave ID instrumen.</p>
    </div>
    <a href="{{ route('devices.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <span class="fw-bold text-dark"><i class="bi bi-hdd-network text-primary me-2"></i>Formulir Parameter Device</span>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('devices.store') }}" method="POST">
                    @csrf

                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label for="name" class="form-label fw-semibold small text-secondary">Nama Device / Lokasi Meter <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   placeholder="Contoh: Meter Air RS485, kWh Meter RS485" 
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-5">
                            <label for="category" class="form-label fw-semibold small text-secondary">Tipe / Kategori Hardware</label>
                            <select name="category" id="category" class="form-select @error('category') is-invalid @enderror">
                                <option value="flow_meter" {{ old('category', 'flow_meter') == 'flow_meter' ? 'selected' : '' }}>Electromagnetic Flowmeter (Flowrate & Totalizer)</option>
                                <option value="ph_sensor" {{ old('category') == 'ph_sensor' ? 'selected' : '' }}>Sensor pH (0 ~ 14 pH)</option>
                                <option value="water_meter" {{ old('category') == 'water_meter' ? 'selected' : '' }}>Meter Air Mekanik (Totalizer m³)</option>
                                <option value="digital_water_meter" {{ old('category') == 'digital_water_meter' ? 'selected' : '' }}>Meter Air Digital RS485 (Totalizer & Flow)</option>
                                <option value="kwh_meter" {{ old('category') == 'kwh_meter' ? 'selected' : '' }}>kWh Meter RS485 (kWh, V, A)</option>
                                <option value="power_meter" {{ old('category') == 'power_meter' ? 'selected' : '' }}>Power Meter RS485 (kWh, V, A, kW)</option>
                                <option value="electricity" {{ old('category') == 'electricity' ? 'selected' : '' }}>Meter Listrik Umum</option>
                            </select>
                            @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold small text-secondary">Deskripsi / Catatan Tambahan</label>
                        <input type="text" 
                               class="form-control @error('description') is-invalid @enderror" 
                               id="description" 
                               name="description" 
                               value="{{ old('description') }}" 
                               placeholder="Contoh: Terhubung via Gateway RS485 to TCP/IP Port 502">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label for="ip_address" class="form-label fw-semibold small text-secondary">Alamat IP Gateway (RS485 to TCP/IP) <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control mono-font @error('ip_address') is-invalid @enderror" 
                                   id="ip_address" 
                                   name="ip_address" 
                                   value="{{ old('ip_address', '192.168.1.100') }}" 
                                   placeholder="Contoh: 192.168.1.100 atau 127.0.0.1" 
                                   required>
                            @error('ip_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="port" class="form-label fw-semibold small text-secondary">Port Modbus TCP <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control mono-font @error('port') is-invalid @enderror" 
                                   id="port" 
                                   name="port" 
                                   value="{{ old('port', 502) }}" 
                                   min="1" 
                                   max="65535" 
                                   required>
                            @error('port')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="slave_id" class="form-label fw-semibold small text-secondary">Modbus Slave ID (Unit ID) <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control mono-font @error('slave_id') is-invalid @enderror" 
                                   id="slave_id" 
                                   name="slave_id" 
                                   value="{{ old('slave_id', 1) }}" 
                                   min="1" 
                                   max="247" 
                                   required>
                            <div class="form-text small">Nomor ID slave serial RS485 (1 s/d 247).</div>
                            @error('slave_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="timeout_ms" class="form-label fw-semibold small text-secondary">Socket Timeout (ms) <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control mono-font @error('timeout_ms') is-invalid @enderror" 
                                   id="timeout_ms" 
                                   name="timeout_ms" 
                                   value="{{ old('timeout_ms', 1500) }}" 
                                   min="200" 
                                   max="10000" 
                                   step="100" 
                                   required>
                            <div class="form-text small">Waktu tunggu maksimal respon TCP (default: 1500 ms).</div>
                            @error('timeout_ms')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4 form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold small text-secondary" for="is_active">Aktifkan Pembacaan Meter Ini</label>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('devices.index') }}" class="btn btn-light border">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Simpan Device
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Informasi Petunjuk Teknis -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-3 bg-light">
            <div class="card-body">
                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-info-circle text-primary me-2"></i>Panduan Konfigurasi</h6>
                <p class="small text-muted mb-2">
                    Aplikasi ini berkomunikasi dengan power meter menggunakan protokol <strong>Modbus TCP</strong> melalui konverter gateway RS485-to-Ethernet/WiFi (contoh: USR-TCP232, Waveshare, Moxa).
                </p>
                <ul class="small text-muted ps-3 mb-0">
                    <li><strong>Alamat IP:</strong> IP lokal dari modul gateway dalam satu LAN/subnet.</li>
                    <li><strong>Port:</strong> Standar Modbus TCP adalah <code>502</code>.</li>
                    <li><strong>Slave ID:</strong> Alamat serial RS485 dari unit power meter fisik.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
