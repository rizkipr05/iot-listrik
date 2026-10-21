@extends('layouts.app')

@section('title', 'Tambah Register Modbus')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Tambah Register Modbus Baru</h4>
        <p class="text-muted small mb-0">Petakan alamat register dari datasheet power meter ke parameter aplikasi.</p>
    </div>
    <a href="{{ route('registers.index', ['device_id' => $selectedDeviceId]) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <span class="fw-bold text-dark"><i class="bi bi-list-columns-reverse text-primary me-2"></i>Form Pemetaan Register</span>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('registers.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="device_id" class="form-label fw-semibold small text-secondary">Pilih Device Meter <span class="text-danger">*</span></label>
                        <select name="device_id" id="device_id" class="form-select @error('device_id') is-invalid @enderror" required>
                            <option value="">-- Pilih Device Target --</option>
                            @foreach($devices as $dev)
                                <option value="{{ $dev->id }}" {{ old('device_id', $selectedDeviceId) == $dev->id ? 'selected' : '' }}>
                                    {{ $dev->name }} (Slave ID: {{ $dev->slave_id }} &bull; {{ $dev->ip_address }}:{{ $dev->port }})
                                </option>
                            @endforeach
                        </select>
                        @error('device_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label for="name" class="form-label fw-semibold small text-secondary">Nama Parameter <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   placeholder="Contoh: Tegangan Phase-Neutral L1-N" 
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-5">
                            <label for="parameter_key" class="form-label fw-semibold small text-secondary">Kategori Parameter <span class="text-danger">*</span></label>
                            <select name="parameter_key" id="parameter_key" class="form-select @error('parameter_key') is-invalid @enderror" required>
                                <option value="flow_rate" {{ old('parameter_key', 'flow_rate') == 'flow_rate' ? 'selected' : '' }}>Flow Rate / Laju Alir (m³/h)</option>
                                <option value="totalizer" {{ old('parameter_key') == 'totalizer' ? 'selected' : '' }}>Totalizer / Akumulasi Volume (m³)</option>
                                <option value="pressure" {{ old('parameter_key') == 'pressure' ? 'selected' : '' }}>Tekanan / Pressure Value (Bar)</option>
                                <option value="ph" {{ old('parameter_key') == 'ph' ? 'selected' : '' }}>Nilai pH / pH Value (pH)</option>
                                <option value="energy" {{ old('parameter_key') == 'energy' ? 'selected' : '' }}>Total kWh / Energi (kWh)</option>
                                <option value="voltage" {{ old('parameter_key') == 'voltage' ? 'selected' : '' }}>Tegangan (Voltage V)</option>
                                <option value="current" {{ old('parameter_key') == 'current' ? 'selected' : '' }}>Arus (Current A)</option>
                                <option value="power" {{ old('parameter_key') == 'power' ? 'selected' : '' }}>Daya Aktif (Active Power kW)</option>
                                <option value="frequency" {{ old('parameter_key') == 'frequency' ? 'selected' : '' }}>Frekuensi (Frequency Hz)</option>
                                <option value="power_factor" {{ old('parameter_key') == 'power_factor' ? 'selected' : '' }}>Power Factor (Cos Phi)</option>
                                <option value="other" {{ old('parameter_key') == 'other' ? 'selected' : '' }}>Lainnya (Custom)</option>
                            </select>
                            @error('parameter_key')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="register_address" class="form-label fw-semibold small text-secondary">Alamat Register (Offset/Address) <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control mono-font @error('register_address') is-invalid @enderror" 
                                   id="register_address" 
                                   name="register_address" 
                                   value="{{ old('register_address', 0) }}" 
                                   min="0" 
                                   required>
                            <div class="form-text small">Alamat register Modbus dari manual alat (misal: 3000, 0, 30001).</div>
                            @error('register_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="function_code" class="form-label fw-semibold small text-secondary">Function Code <span class="text-danger">*</span></label>
                            <select name="function_code" id="function_code" class="form-select @error('function_code') is-invalid @enderror" required>
                                <option value="3" {{ old('function_code', 3) == 3 ? 'selected' : '' }}>FC03 - Read Holding Registers (4xxxx)</option>
                                <option value="4" {{ old('function_code') == 4 ? 'selected' : '' }}>FC04 - Read Input Registers (3xxxx)</option>
                            </select>
                            @error('function_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="data_type" class="form-label fw-semibold small text-secondary">Tipe Data <span class="text-danger">*</span></label>
                            <select name="data_type" id="data_type" class="form-select @error('data_type') is-invalid @enderror" required>
                                <option value="float32" {{ old('data_type', 'float32') == 'float32' ? 'selected' : '' }}>Float 32-bit (2 Register IEEE 754)</option>
                                <option value="uint16" {{ old('data_type') == 'uint16' ? 'selected' : '' }}>UInt 16-bit (1 Register Unsigned)</option>
                                <option value="int16" {{ old('data_type') == 'int16' ? 'selected' : '' }}>Int 16-bit (1 Register Signed)</option>
                                <option value="uint32" {{ old('data_type') == 'uint32' ? 'selected' : '' }}>UInt 32-bit (2 Register Unsigned)</option>
                                <option value="int32" {{ old('data_type') == 'int32' ? 'selected' : '' }}>Int 32-bit (2 Register Signed)</option>
                            </select>
                            @error('data_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="byte_order" class="form-label fw-semibold small text-secondary">Byte / Word Order <span class="text-danger">*</span></label>
                            <select name="byte_order" id="byte_order" class="form-select @error('byte_order') is-invalid @enderror" required>
                                <option value="CDAB" {{ old('byte_order', 'CDAB') == 'CDAB' ? 'selected' : '' }}>CDAB - Word Swap (Standar Power Meter)</option>
                                <option value="ABCD" {{ old('byte_order') == 'ABCD' ? 'selected' : '' }}>ABCD - Big Endian</option>
                                <option value="BADC" {{ old('byte_order') == 'BADC' ? 'selected' : '' }}>BADC - Byte Swap</option>
                                <option value="DCBA" {{ old('byte_order') == 'DCBA' ? 'selected' : '' }}>DCBA - Little Endian</option>
                            </select>
                            <div class="form-text small">Umumnya meter listrik menggunakan format <strong>CDAB</strong>.</div>
                            @error('byte_order')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="multiplier" class="form-label fw-semibold small text-secondary">Faktor Skala (Multiplier) <span class="text-danger">*</span></label>
                            <input type="number" 
                                   step="any"
                                   class="form-control mono-font @error('multiplier') is-invalid @enderror" 
                                   id="multiplier" 
                                   name="multiplier" 
                                   value="{{ old('multiplier', '1.0') }}" 
                                   required>
                            <div class="form-text small">Contoh: 1.0 (nilai asli), 0.1, atau 0.001 (konversi Watt ke kW).</div>
                            @error('multiplier')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="unit" class="form-label fw-semibold small text-secondary">Satuan Unit (Opsional)</label>
                            <input type="text" 
                                   class="form-control mono-font @error('unit') is-invalid @enderror" 
                                   id="unit" 
                                   name="unit" 
                                   value="{{ old('unit', 'V') }}" 
                                   placeholder="Contoh: V, A, kW, kWh, Hz, PF">
                            @error('unit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4 form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold small text-secondary" for="is_active">Aktifkan Pembacaan Register Ini</label>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('registers.index', ['device_id' => $selectedDeviceId]) }}" class="btn btn-light border">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Simpan Register
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
