@extends('layouts.app')

@section('title', 'Edit Device Meter')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Edit Device Meter</h4>
        <p class="text-muted small mb-0">Perbarui konfigurasi koneksi dan parameter gateway meter.</p>
    </div>
    <a href="{{ route('devices.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark"><i class="bi bi-pencil-square text-primary me-2"></i>Ubah Device: {{ $device->name }}</span>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('devices.update', $device) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label for="name" class="form-label fw-semibold small text-secondary">Nama Device / Lokasi Meter <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $device->name) }}" 
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-5">
                            <label for="category" class="form-label fw-semibold small text-secondary">Tipe / Kategori Hardware</label>
                            <select name="category" id="category" class="form-select @error('category') is-invalid @enderror">
                                <option value="flow_meter" {{ old('category', $device->category) == 'flow_meter' ? 'selected' : '' }}>Electromagnetic Flowmeter (Flowrate & Totalizer)</option>
                                <option value="ph_sensor" {{ old('category', $device->category) == 'ph_sensor' ? 'selected' : '' }}>Sensor pH (0 ~ 14 pH)</option>
                                <option value="water_meter" {{ old('category', $device->category) == 'water_meter' ? 'selected' : '' }}>Meter Air Mekanik (Totalizer m³)</option>
                                <option value="digital_water_meter" {{ old('category', $device->category) == 'digital_water_meter' ? 'selected' : '' }}>Meter Air Digital RS485 (Totalizer & Flow)</option>
                                <option value="kwh_meter" {{ old('category', $device->category) == 'kwh_meter' ? 'selected' : '' }}>kWh Meter RS485 (kWh, V, A)</option>
                                <option value="power_meter" {{ old('category', $device->category) == 'power_meter' ? 'selected' : '' }}>Power Meter RS485 (kWh, V, A, kW)</option>
                                <option value="electricity" {{ old('category', $device->category) == 'electricity' ? 'selected' : '' }}>Meter Listrik Umum</option>
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
                               value="{{ old('description', $device->description) }}">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label for="ip_address" class="form-label fw-semibold small text-secondary">Alamat IP Gateway (RS485 to TCP/IP) <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control mono-font @error('ip_address') is-invalid @enderror" 
                                   id="ip_address" 
                                   name="ip_address" 
                                   value="{{ old('ip_address', $device->ip_address) }}" 
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
                                   value="{{ old('port', $device->port) }}" 
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
                                   value="{{ old('slave_id', $device->slave_id) }}" 
                                   min="1" 
                                   max="247" 
                                   required>
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
                                   value="{{ old('timeout_ms', $device->timeout_ms) }}" 
                                   min="200" 
                                   max="10000" 
                                   step="100" 
                                   required>
                            @error('timeout_ms')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4 form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', $device->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold small text-secondary" for="is_active">Aktifkan Pembacaan Meter Ini</label>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('devices.index') }}" class="btn btn-light border">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
