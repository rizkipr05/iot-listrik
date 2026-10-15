@extends('layouts.app')

@section('title', 'Register Configuration')

@section('content')
<!-- Header & Quick Actions -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill small fw-bold" style="font-size: 0.7rem;">
                <i class="bi bi-list-columns-reverse me-1"></i> MODBUS PROTOCOL MAPPING
            </span>
            <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 small mono-font" style="font-size: 0.7rem;">
                FC03 / FC04 &bull; FLOAT32 &bull; INT16
            </span>
        </div>
        <h4 class="fw-bold mb-0 text-dark">Register Configuration (Modbus Mapping)</h4>
        <small class="text-muted">Pemetaan alamat register Modbus, tipe data, endian byte order, dan faktor pengali per instrumen.</small>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCheatSheet">
            <i class="bi bi-book-half me-1"></i> Panduan Modbus Cheat Sheet
        </button>
        <a href="{{ route('registers.create', ['device_id' => $selectedDeviceId]) }}" class="btn btn-primary rounded-pill px-3 shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Tambah Register
        </a>
    </div>
</div>

<!-- 4 Summary KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 border-0 shadow-sm h-100" style="border-radius: 14px; border-left: 4px solid #2563eb !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">Total Register Terpetakan</span>
                    <h3 class="fw-bold mb-0 text-dark mono-font mt-1">{{ $registers->total() }} <span class="fs-6 fw-normal text-muted">Titik</span></h3>
                </div>
                <div class="p-2 bg-primary-subtle text-primary rounded-3 fs-4">
                    <i class="bi bi-cpu"></i>
                </div>
            </div>
            <small class="text-muted mt-2 d-block" style="font-size: 0.74rem;">Dari {{ $devices->count() }} unit device meter</small>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 border-0 shadow-sm h-100" style="border-radius: 14px; border-left: 4px solid #059669 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">Function Code</span>
                    <h3 class="fw-bold mb-0 text-success mono-font mt-1">FC03 / FC04</h3>
                </div>
                <div class="p-2 bg-success-subtle text-success rounded-3 fs-4">
                    <i class="bi bi-code-slash"></i>
                </div>
            </div>
            <small class="text-muted mt-2 d-block" style="font-size: 0.74rem;">Holding & Input Registers</small>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 border-0 shadow-sm h-100" style="border-radius: 14px; border-left: 4px solid #d97706 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">Format Byte Order</span>
                    <h3 class="fw-bold mb-0 text-warning mono-font mt-1">ABCD / CDAB</h3>
                </div>
                <div class="p-2 bg-warning-subtle text-warning rounded-3 fs-4">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
            </div>
            <small class="text-muted mt-2 d-block" style="font-size: 0.74rem;">Standard & Word-Swapped IEEE-754</small>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 border-0 shadow-sm h-100" style="border-radius: 14px; border-left: 4px solid #7c3aed !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">Faktor Skala</span>
                    <h3 class="fw-bold mb-0 text-purple mono-font mt-1" style="color: #7c3aed;">Multiplier</h3>
                </div>
                <div class="p-2 bg-purple-subtle rounded-3 fs-4" style="background: rgba(124, 58, 237, 0.1); color: #7c3aed;">
                    <i class="bi bi-calculator"></i>
                </div>
            </div>
            <small class="text-muted mt-2 d-block" style="font-size: 0.74rem;">Auto kalkulasi integer ke float presisi</small>
        </div>
    </div>
</div>

<!-- Filter Berdasarkan Device -->
<div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('registers.index') }}" class="row g-2 align-items-center">
            <div class="col-auto">
                <label for="filter_device" class="col-form-label fw-bold small text-secondary">
                    <i class="bi bi-funnel-fill text-primary me-1"></i>Filter Perangkat Meter:
                </label>
            </div>
            <div class="col-md-5">
                <select name="device_id" id="filter_device" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Semua Device Meter ({{ $devices->count() }} Terdaftar) --</option>
                    @foreach($devices as $dev)
                        <option value="{{ $dev->id }}" {{ $selectedDeviceId == $dev->id ? 'selected' : '' }}>
                            {{ $dev->name }} (Slave ID: {{ $dev->slave_id }} - {{ $dev->ip_address }})
                        </option>
                    @endforeach
                </select>
            </div>
            @if($selectedDeviceId)
                <div class="col-auto">
                    <a href="{{ route('registers.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-x-circle me-1"></i> Reset Filter
                    </a>
                </div>
            @endif
        </form>
    </div>
</div>

<!-- Tabel Daftar Register -->
<div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden;">
    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
        <div class="fw-bold text-dark d-flex align-items-center gap-2">
            <div class="p-2 bg-primary-subtle text-primary rounded-3">
                <i class="bi bi-list-check"></i>
            </div>
            <div>
                <span class="d-block">Daftar Titik Register Modbus</span>
                <small class="text-muted fw-normal" style="font-size: 0.75rem;">Total {{ $registers->total() }} parameter terkonfigurasi</small>
            </div>
        </div>
        <span class="badge bg-light text-dark border mono-font px-2 py-1">
            Halaman {{ $registers->currentPage() }} dari {{ $registers->lastPage() }}
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-secondary small text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                <tr>
                    <th class="ps-4" style="width: 60px;">#</th>
                    <th>Device Meter</th>
                    <th>Nama Parameter</th>
                    <th>Alamat Register</th>
                    <th>Function Code</th>
                    <th>Tipe Data & Byte Order</th>
                    <th>Skala & Satuan</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($registers as $index => $register)
                    <tr>
                        <td class="ps-4 text-muted fw-semibold mono-font">{{ $registers->firstItem() + $index }}</td>
                        <td>
                            <div class="fw-bold text-dark">{{ $register->device->name ?? 'Device Terhapus' }}</div>
                            <small class="text-muted mono-font">Slave ID: {{ $register->device->slave_id ?? '-' }} &bull; {{ $register->device->ip_address ?? '-' }}</small>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $register->name }}</div>
                            <span class="badge {{ in_array($register->parameter_key, ['totalizer', 'flow_rate']) ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-light text-secondary border' }} small mono-font" style="font-size: 0.7rem;">
                                {{ $register->parameter_key_label }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-dark mono-font px-2 py-1" style="font-size: 0.85rem;">
                                {{ $register->register_address }}
                            </span>
                        </td>
                        <td>
                            @if($register->function_code == 3)
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1" style="font-size: 0.75rem;">
                                    FC03 (Holding)
                                </span>
                            @else
                                <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2 py-1" style="font-size: 0.75rem;">
                                    FC04 (Input)
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="mono-font small fw-bold text-dark">{{ strtoupper($register->data_type) }}</div>
                            <small class="text-muted mono-font">Order: {{ $register->byte_order }}</small>
                        </td>
                        <td>
                            <span class="mono-font fw-bold text-primary">{{ $register->multiplier }}</span>
                            <span class="badge bg-light text-dark border mono-font">{{ $register->unit ?? '-' }}</span>
                        </td>
                        <td>
                            @if($register->is_active)
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1" style="font-size: 0.75rem;">
                                    Aktif
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2 py-1" style="font-size: 0.75rem;">
                                    Non-Aktif
                                </span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('registers.edit', $register) }}" class="btn btn-outline-secondary" title="Edit Register">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('registers.destroy', $register) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus register \'{{ $register->name }}\'?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" title="Hapus Register">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <h6 class="fw-bold mb-1">Belum Ada Register Ditemukan</h6>
                            <p class="small text-muted mb-3">Tidak ada register Modbus untuk filter yang dipilih.</p>
                            <a href="{{ route('registers.create', ['device_id' => $selectedDeviceId]) }}" class="btn btn-sm btn-primary rounded-pill px-3">
                                <i class="bi bi-plus-circle me-1"></i> Tambah Register Sekarang
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($registers->hasPages())
        <div class="card-footer bg-white py-3 px-4 border-top">
            {{ $registers->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

<!-- Modal Panduan Modbus Cheat Sheet -->
<div class="modal fade" id="modalCheatSheet" tabindex="-1" aria-labelledby="modalCheatSheetLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-header bg-dark text-white border-0 py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-primary rounded-3 text-white">
                        <i class="bi bi-book-half"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="modalCheatSheetLabel">Modbus Quick Reference Cheat Sheet</h5>
                        <small class="text-secondary">Panduan konfigurasi parameter instrumen kelistrikan</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- 1. Function Code -->
                <div class="mb-4">
                    <h6 class="fw-bold text-dark d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-pill">1</span> Function Code (Kode Fungsi)
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm small align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama Fungsi</th>
                                    <th>Tipe Akses</th>
                                    <th>Rentang Alamat Khas</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong class="mono-font text-primary">03</strong></td>
                                    <td>Read Holding Registers</td>
                                    <td>Read / Write</td>
                                    <td class="mono-font">40001 - 49999</td>
                                    <td>Digunakan untuk register konfigurasi atau memori pembacaan umum (Schneider, PEMS, Selec).</td>
                                </tr>
                                <tr>
                                    <td><strong class="mono-font text-info">04</strong></td>
                                    <td>Read Input Registers</td>
                                    <td>Read Only</td>
                                    <td class="mono-font">30001 - 39999</td>
                                    <td>Digunakan untuk hasil pengukuran sensor/meter analog aktual.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 2. Byte Order / Endianness -->
                <div class="mb-4">
                    <h6 class="fw-bold text-dark d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark rounded-pill">2</span> Format Data 32-bit & Byte Order
                    </h6>
                    <div class="row g-2 small">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <span class="badge bg-primary mb-1 mono-font">ABCD (Big Endian)</span>
                                <div class="fw-semibold text-dark">High Word First, High Byte First</div>
                                <p class="text-muted mb-0 mt-1">Urutan byte standar IEEE-754. Register 1 berisi word teratas, Register 2 word terbawah.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <span class="badge bg-warning text-dark mb-1 mono-font">CDAB (Word Swapped)</span>
                                <div class="fw-semibold text-dark">Low Word First, High Byte First</div>
                                <p class="text-muted mb-0 mt-1">Format umum pada power meter Schneider PM5000 / Eastron SDM / PEMS. Word kedua dibalik ke depan.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Multiplier & Skala -->
                <div class="mb-3">
                    <h6 class="fw-bold text-dark d-flex align-items-center gap-2">
                        <span class="badge bg-success rounded-pill">3</span> Multiplier (Faktor Pengali Presisi)
                    </h6>
                    <div class="p-3 bg-light rounded-3 border small">
                        <p class="mb-2">Jika register bertipe <strong>INT16</strong> atau <strong>UINT32</strong> menyimpan nilai integer tanpa desimal, gunakan Multiplier untuk mengonversinya ke satuan asli:</p>
                        <ul class="mb-0 ps-3">
                            <li>Contoh: Nilai raw tegangan = <code>2205</code> &rarr; gunakan multiplier <code>0.1</code> &rarr; hasil = <strong>220.5 V</strong>.</li>
                            <li>Contoh: Nilai raw arus = <code>1542</code> &rarr; gunakan multiplier <code>0.01</code> &rarr; hasil = <strong>15.42 A</strong>.</li>
                            <li>Jika tipe data <strong>FLOAT32</strong>, biasanya nilai sudah langsung dalam satuan nyata &rarr; gunakan multiplier <code>1.0</code>.</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-3 bg-light">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup Panduan</button>
            </div>
        </div>
    </div>
</div>
@endsection
