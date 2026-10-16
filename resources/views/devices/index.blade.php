@extends('layouts.app')

@section('title', 'Device Management')

@section('content')
<!-- Header & Quick Actions -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill small fw-bold" style="font-size: 0.7rem;">
                <i class="bi bi-hdd-network-fill me-1"></i> MODBUS TCP GATEWAY
            </span>
            <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 small mono-font" style="font-size: 0.7rem;">
                PORT 502
            </span>
        </div>
        <h4 class="fw-bold mb-0 text-dark">Device Management (Instrument Meter)</h4>
        <small class="text-muted">Kelola identitas device, alamat IP gateway RS485-to-TCP, Slave ID, dan parameter koneksi.</small>
    </div>
    <div>
        <a href="{{ route('devices.create') }}" class="btn btn-primary rounded-pill px-3 shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Tambah Device Meter
        </a>
    </div>
</div>

<!-- 4 Mini Summary KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 border-0 shadow-sm h-100" style="border-radius: 14px; border-left: 4px solid #2563eb !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">Total Meter Terdaftar</span>
                    <h3 class="fw-bold mb-0 text-dark mono-font mt-1">{{ $devices->count() }} <span class="fs-6 fw-normal text-muted">Unit</span></h3>
                </div>
                <div class="p-2 bg-primary-subtle text-primary rounded-3 fs-4">
                    <i class="bi bi-speedometer2"></i>
                </div>
            </div>
            <small class="text-muted mt-2 d-block" style="font-size: 0.74rem;">Maksimal 5 panel instrumen</small>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 border-0 shadow-sm h-100" style="border-radius: 14px; border-left: 4px solid #059669 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">Status Gateway</span>
                    <h3 class="fw-bold mb-0 text-success mono-font mt-1">Aktif <span class="fs-6 fw-normal text-muted">TCP</span></h3>
                </div>
                <div class="p-2 bg-success-subtle text-success rounded-3 fs-4">
                    <i class="bi bi-router-fill"></i>
                </div>
            </div>
            <small class="text-muted mt-2 d-block mono-font" style="font-size: 0.74rem;">Port Default: 502 Modbus</small>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 border-0 shadow-sm h-100" style="border-radius: 14px; border-left: 4px solid #d97706 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">Total Register Terpetakan</span>
                    <h3 class="fw-bold mb-0 text-warning mono-font mt-1">{{ $devices->sum('registers_count') }} <span class="fs-6 fw-normal text-muted">Titik</span></h3>
                </div>
                <div class="p-2 bg-warning-subtle text-warning rounded-3 fs-4">
                    <i class="bi bi-list-columns-reverse"></i>
                </div>
            </div>
            <small class="text-muted mt-2 d-block" style="font-size: 0.74rem;">Tegangan, Arus, Daya, Frekuensi, dll</small>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 border-0 shadow-sm h-100" style="border-radius: 14px; border-left: 4px solid #7c3aed !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">Metode Akses</span>
                    <h3 class="fw-bold mb-0 text-purple mono-font mt-1" style="color: #7c3aed;">Socket <span class="fs-6 fw-normal text-muted">TCP</span></h3>
                </div>
                <div class="p-2 bg-purple-subtle rounded-3 fs-4" style="background: rgba(124, 58, 237, 0.1); color: #7c3aed;">
                    <i class="bi bi-cpu-fill"></i>
                </div>
            </div>
            <small class="text-muted mt-2 d-block" style="font-size: 0.74rem;">Direct RS485-to-TCP Engine</small>
        </div>
    </div>
</div>

<!-- Alert Notifikasi Tes Koneksi -->
<div id="testConnAlert" class="alert d-none alert-dismissible fade show mb-3 border-0 shadow-sm" role="alert" style="border-radius: 12px;">
    <div class="d-flex align-items-center gap-2">
        <span id="testConnIcon"></span>
        <div id="testConnMsg" class="small fw-semibold"></div>
    </div>
    <button type="button" class="btn-close" onclick="document.getElementById('testConnAlert').classList.add('d-none')"></button>
</div>

<!-- Daftar Device Meter Table Card -->
<div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden;">
    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
        <div class="fw-bold text-dark d-flex align-items-center gap-2">
            <div class="p-2 bg-primary-subtle text-primary rounded-3">
                <i class="bi bi-hdd-network-fill"></i>
            </div>
            <div>
                <span class="d-block">Daftar Device Terdaftar</span>
                <small class="text-muted fw-normal" style="font-size: 0.75rem;">Konfigurasi parameter Modbus untuk {{ $devices->count() }} unit instrumen</small>
            </div>
        </div>
        <span class="badge bg-light text-dark border mono-font px-2 py-1">
            {{ $devices->where('is_active', true)->count() }} Aktif / {{ $devices->count() }} Total
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-secondary small text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                <tr>
                    <th class="ps-4" style="width: 70px;">Unit</th>
                    <th>Nama Meter & Lokasi</th>
                    <th>Alamat Gateway (IP : Port)</th>
                    <th>Slave ID</th>
                    <th>Timeout</th>
                    <th>Register Terdaftar</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($devices as $index => $device)
                    <tr>
                        <td class="ps-4">
                            @if($device->isPressureSensor())
                                <div class="d-flex align-items-center justify-content-center bg-danger-subtle text-danger rounded-3 fw-bold mono-font" style="width: 38px; height: 38px; font-size: 0.85rem;" title="Sensor Tekanan / Pressure">
                                    <i class="bi bi-speedometer2 fs-5"></i>
                                </div>
                            @elseif($device->isWater())
                                <div class="d-flex align-items-center justify-content-center bg-info-subtle text-info rounded-3 fw-bold mono-font" style="width: 38px; height: 38px; font-size: 0.85rem;" title="Meter Air / Flow Meter">
                                    <i class="bi bi-droplet-fill fs-5"></i>
                                </div>
                            @else
                                <div class="d-flex align-items-center justify-content-center bg-warning-subtle text-warning rounded-3 fw-bold mono-font" style="width: 38px; height: 38px; font-size: 0.85rem;" title="Meter Listrik">
                                    <i class="bi bi-lightning-charge-fill fs-5"></i>
                                </div>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold text-dark">{{ $device->name }}</span>
                                <span class="badge {{ $device->isPressureSensor() ? 'bg-danger-subtle text-danger border border-danger-subtle' : ($device->isWater() ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle') }} rounded-pill" style="font-size: 0.68rem;">
                                    {{ $device->category_label }}
                                </span>
                            </div>
                            <small class="text-muted">{{ $device->description ?? ($device->isPressureSensor() ? 'Instrumen Sensor Tekanan' : ($device->isWater() ? 'Instrumen Meter Air / Flow' : 'Instrumen Panel Listrik')) }}</small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border mono-font py-1 px-2" style="font-size: 0.8rem;">
                                <i class="bi bi-router text-info me-1"></i>{{ $device->ip_address }}:{{ $device->port }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle mono-font py-1 px-2" style="font-size: 0.8rem;">
                                ID: {{ $device->slave_id }}
                            </span>
                        </td>
                        <td class="text-muted small mono-font">
                            {{ $device->timeout_ms }} ms
                        </td>
                        <td>
                            <a href="{{ route('registers.index', ['device_id' => $device->id]) }}" class="badge bg-info-subtle text-info border border-info-subtle text-decoration-none py-1 px-2" style="font-size: 0.78rem;">
                                <i class="bi bi-list-ul me-1"></i>{{ $device->registers_count }} Register &rarr;
                            </a>
                        </td>
                        <td>
                            @if($device->is_active)
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1" style="font-size: 0.75rem;">
                                    <i class="bi bi-check-circle-fill me-1"></i> Aktif
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2 py-1" style="font-size: 0.75rem;">
                                    Non-Aktif
                                </span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <!-- Tombol Test Koneksi Socket -->
                                <button type="button" 
                                        class="btn btn-outline-primary btn-test-conn rounded-start" 
                                        data-id="{{ $device->id }}"
                                        data-url="{{ route('devices.test', $device) }}"
                                        data-name="{{ $device->name }}"
                                        title="Uji Koneksi Gateway Modbus">
                                    <i class="bi bi-reception-4 me-1"></i> Test
                                </button>
                                
                                <a href="{{ route('devices.edit', $device) }}" class="btn btn-outline-secondary" title="Edit Device">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <form action="{{ route('devices.destroy', $device) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus device \'{{ $device->name }}\'? Semua register terkait akan ikut terhapus.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger rounded-end" title="Hapus Device">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <h6 class="fw-bold mb-1">Belum Ada Device Meter Terdaftar</h6>
                            <p class="small text-muted mb-3">Tambahkan instrumen meter dengan gateway IP dan Slave ID Modbus Anda.</p>
                            <a href="{{ route('devices.create') }}" class="btn btn-sm btn-primary rounded-pill px-3">
                                <i class="bi bi-plus-circle me-1"></i> Tambah Device Sekarang
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.querySelectorAll('.btn-test-conn').forEach(button => {
        button.addEventListener('click', function() {
            const btn = this;
            const originalContent = btn.innerHTML;
            const url = btn.getAttribute('data-url');
            const name = btn.getAttribute('data-name');
            const alertBox = document.getElementById('testConnAlert');
            const alertMsg = document.getElementById('testConnMsg');
            const alertIcon = document.getElementById('testConnIcon');

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menguji...';

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(res => res.json())
            .then(data => {
                alertBox.classList.remove('d-none', 'alert-success', 'alert-danger');
                if (data.success) {
                    alertBox.classList.add('alert-success');
                    alertBox.style.background = '#ecfdf5';
                    alertBox.style.color = '#065f46';
                    alertIcon.innerHTML = '<i class="bi bi-check-circle-fill fs-5 text-success"></i>';
                    alertMsg.innerHTML = `<strong>${name}:</strong> ${data.message}`;
                } else {
                    alertBox.classList.add('alert-danger');
                    alertBox.style.background = '#fef2f2';
                    alertBox.style.color = '#991b1b';
                    alertIcon.innerHTML = '<i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>';
                    alertMsg.innerHTML = `<strong>${name}:</strong> ${data.message} <em>(Pastikan gateway RS485-to-TCP aktif di port 502)</em>`;
                }
                alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            })
            .catch(err => {
                alertBox.classList.remove('d-none', 'alert-success');
                alertBox.classList.add('alert-danger');
                alertBox.style.background = '#fef2f2';
                alertBox.style.color = '#991b1b';
                alertIcon.innerHTML = '<i class="bi bi-x-circle-fill fs-5 text-danger"></i>';
                alertMsg.innerHTML = `<strong>${name}:</strong> Terjadi kesalahan jaringan saat tes koneksi: ${err.message}`;
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            });
        });
    });
</script>
@endsection
