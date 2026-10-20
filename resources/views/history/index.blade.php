@extends('layouts.app')

@section('title', 'Data History Log')

@section('styles')
<style>
    .kpi-card-hist {
        border-radius: 16px;
        border: 1px solid rgba(226, 232, 240, 0.9);
        background: #ffffff;
        position: relative;
        overflow: hidden;
        transition: all 0.25s ease;
        box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04);
    }
    .kpi-card-hist::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3.5px;
    }
    .kpi-card-hist.kpi-power::before { background: linear-gradient(90deg, #2563eb, #06b6d4); }
    .kpi-card-hist.kpi-voltage::before { background: linear-gradient(90deg, #059669, #10b981); }
    .kpi-card-hist.kpi-current::before { background: linear-gradient(90deg, #dc2626, #f97316); }
    .kpi-card-hist.kpi-energy::before { background: linear-gradient(90deg, #7c3aed, #a855f7); }

    .kpi-card-hist:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px -4px rgba(15, 23, 42, 0.08);
    }

    .quick-preset-btn {
        font-size: 0.78rem;
        padding: 0.25rem 0.65rem;
        border-radius: 20px;
        transition: all 0.15s ease;
    }
</style>
@endsection

@section('content')
<!-- Header & Quick Actions -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill small fw-bold" style="font-size: 0.7rem;">
                <i class="bi bi-clock-history me-1"></i> HISTORICAL DATA LOG
            </span>
            <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 small mono-font" style="font-size: 0.7rem;">
                SQLITE LOCAL STORAGE
            </span>
        </div>
        <h4 class="fw-bold mb-0 text-dark">Data History & Log Meter</h4>
        <small class="text-muted">Riwayat pencatatan otomatis parameter flowmeter, tekanan pipa, tren debit air, dan ekspor data CSV.</small>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <form action="{{ route('history.log-now') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-primary rounded-pill px-3 shadow-sm">
                <i class="bi bi-camera-fill me-1"></i> Catat Log Sekarang
            </button>
        </form>
        <a href="{{ route('history.export', request()->query()) }}" class="btn btn-outline-success rounded-pill px-3 shadow-sm">
            <i class="bi bi-file-earmark-excel me-1"></i> Ekspor CSV
        </a>
        <button type="button" class="btn btn-outline-danger rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPurgeLogs">
            <i class="bi bi-trash3 me-1"></i> Bersihkan Log
        </button>
    </div>
</div>

<!-- 4 Kartu Statistik Periode Terpilih (SCADA Style) -->
<div class="row g-3 mb-4">
    <!-- Jumlah Titik Log -->
    <div class="col-sm-6 col-xl-3">
        <div class="card kpi-card-hist kpi-energy p-3 h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">Jumlah Titik Log</span>
                <span class="badge bg-purple-subtle rounded-pill small" style="background: rgba(124, 58, 237, 0.1); color: #7c3aed; font-size: 0.7rem;">Recorded</span>
            </div>
            <div class="d-flex align-items-center gap-3 my-1">
                <div class="p-2 rounded-3 fs-4" style="background: rgba(124, 58, 237, 0.1); color: #7c3aed;">
                    <i class="bi bi-database-fill"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0 mono-font text-dark">{{ number_format($totalLogs) }} <span class="fs-6 fw-normal text-muted">Baris</span></h3>
                    <small class="text-muted" style="font-size: 0.74rem;">Sampel telemetri tersimpan</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Akumulasi Air (m3) -->
    <div class="col-sm-6 col-xl-3">
        <div class="card kpi-card-hist kpi-power p-3 h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">{{ $volumeLabel ?? 'Volume Air Kumulatif' }}</span>
                <span class="badge bg-info-subtle text-info rounded-pill small" style="font-size: 0.7rem;">Totalizer</span>
            </div>
            <div class="d-flex align-items-center gap-3 my-1">
                <div class="p-2 bg-info-subtle text-info rounded-3 fs-4">
                    <i class="bi bi-droplet-fill"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0 mono-font text-info">{{ number_format($totalWaterVolume, 1) }} <span class="fs-6 fw-normal text-muted">m³</span></h3>
                    <small class="text-muted" style="font-size: 0.74rem;">{{ $volumeDesc ?? 'Delta volume flowmeter' }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Rata-rata Laju Aliran Air (m3/h) -->
    <div class="col-sm-6 col-xl-3">
        <div class="card kpi-card-hist kpi-current p-3 h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">Laju Alir Rata-rata</span>
                <span class="badge bg-primary-subtle text-primary rounded-pill small" style="font-size: 0.7rem;">Flow Rate</span>
            </div>
            <div class="d-flex align-items-center gap-3 my-1">
                <div class="p-2 bg-primary-subtle text-primary rounded-3 fs-4">
                    <i class="bi bi-water"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0 mono-font text-primary">{{ number_format($avgFlowRate, 1) }} <span class="fs-6 fw-normal text-muted">m³/h</span></h3>
                    <small class="text-muted" style="font-size: 0.74rem;">Kecepatan aliran rata-rata</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Rata-rata Tekanan Pipa / Sensor -->
    <div class="col-sm-6 col-xl-3">
        <div class="card kpi-card-hist kpi-voltage p-3 h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">{{ $hasPressureSensor || ! $hasPhSensor ? 'Rata-rata Tekanan Pipa' : 'Rata-rata pH Sensor' }}</span>
                <span class="badge bg-success-subtle text-success rounded-pill small" style="font-size: 0.7rem;">{{ $hasPressureSensor || ! $hasPhSensor ? 'Pressure' : 'pH Range' }}</span>
            </div>
            <div class="d-flex align-items-center gap-3 my-1">
                <div class="p-2 bg-success-subtle text-success rounded-3 fs-4">
                    <i class="bi {{ $hasPressureSensor || ! $hasPhSensor ? 'bi-speedometer2' : 'bi-activity' }}"></i>
                </div>
                <div>
                    @if($hasPressureSensor || ! $hasPhSensor)
                        <h3 class="fw-bold mb-0 mono-font text-success">{{ number_format($avgPressure, 2) }} <span class="fs-6 fw-normal text-muted">Bar</span></h3>
                        <small class="text-muted" style="font-size: 0.74rem;">Status: <span class="text-success fw-bold">NORMAL (1.0 - 4.0 Bar)</span></small>
                    @else
                        <h3 class="fw-bold mb-0 mono-font text-success">{{ number_format($avgPh, 2) }} <span class="fs-6 fw-normal text-muted">pH</span></h3>
                        <small class="text-muted" style="font-size: 0.74rem;">Status: <span class="text-success fw-bold">NORMAL (7.0 - 8.5)</span></small>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Panel Filter Periode & Device -->
<div class="card shadow-sm border-0 mb-4" style="border-radius: 16px;">
    <div class="card-header bg-white py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
        <span class="fw-bold text-dark d-flex align-items-center gap-2">
            <i class="bi bi-funnel-fill text-primary"></i> Filter Rentang Waktu & Meter
        </span>
        <!-- Preset Cepat Filter Waktu -->
        <div class="d-flex flex-wrap gap-1">
            <span class="small text-muted me-1 align-self-center" style="font-size: 0.75rem;">Preset Cepat:</span>
            <button type="button" class="btn btn-outline-secondary quick-preset-btn" onclick="applyQuickFilter(1)">1 Jam Terakhir</button>
            <button type="button" class="btn btn-outline-secondary quick-preset-btn" onclick="applyQuickFilter(24)">24 Jam Terakhir</button>
            <button type="button" class="btn btn-outline-secondary quick-preset-btn" onclick="applyQuickFilter(168)">7 Hari Terakhir</button>
            <button type="button" class="btn btn-outline-secondary quick-preset-btn" onclick="applyQuickFilter(720)">30 Hari Terakhir</button>
        </div>
    </div>
    <div class="card-body p-3 p-md-4">
        @if(!empty($activeOnlineDevice) && (string)$selectedDeviceId !== (string)$activeOnlineDevice->id)
            <div class="alert alert-info py-2 px-3 small d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 border-0 rounded-3 shadow-sm" style="background: rgba(2, 132, 199, 0.08);">
                <div class="d-flex align-items-center gap-2">
                    <span class="spinner-grow spinner-grow-sm text-info" role="status"></span>
                    <span>
                        Device meter yang sedang online / berjalan: <strong>{{ $activeOnlineDevice->name }}</strong> (Slave ID: {{ $activeOnlineDevice->slave_id }})
                    </span>
                </div>
                <a href="{{ route('history.index', ['device_id' => $activeOnlineDevice->id, 'source' => $selectedSource, 'date_start' => $dateStart, 'date_end' => $dateEnd]) }}" class="btn btn-primary btn-sm rounded-pill px-3 py-1 shadow-sm" style="font-size: 0.78rem;">
                    <i class="bi bi-funnel-fill me-1"></i> Filter Hanya Device Ini
                </a>
            </div>
        @endif

        <form method="GET" action="{{ route('history.index') }}" id="historyFilterForm" class="row g-3 align-items-end">
            <!-- Filter Device -->
            <div class="col-md-3">
                <label for="device_id" class="form-label fw-bold small text-secondary">Pilih Device Meter</label>
                <select name="device_id" id="device_id" class="form-select form-select-sm">
                    <option value="">-- Semua Meter ({{ $devices->count() }} Unit) --</option>
                    @foreach($devices as $dev)
                        <option value="{{ $dev->id }}" {{ (string)$selectedDeviceId === (string)$dev->id ? 'selected' : '' }}>
                            {{ $dev->name }} (Slave ID: {{ $dev->slave_id }}){{ !empty($dev->is_online_now) ? ' • [ONLINE / AKTIF]' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Sumber Data -->
            <div class="col-md-2">
                <label for="source" class="form-label fw-bold small text-secondary">Sumber Data</label>
                <select name="source" id="source" class="form-select form-select-sm">
                    <option value="all" {{ ($selectedSource ?? 'all') === 'all' ? 'selected' : '' }}>Semua Data</option>
                    <option value="real" {{ ($selectedSource ?? 'all') === 'real' ? 'selected' : '' }}>Hanya Data Real (Live)</option>
                    <option value="simulated" {{ ($selectedSource ?? 'all') === 'simulated' ? 'selected' : '' }}>Data Simulasi (Sim)</option>
                </select>
            </div>

            <!-- Dari Tanggal & Jam -->
            <div class="col-md-2">
                <label for="date_start" class="form-label fw-bold small text-secondary">Dari Tanggal & Jam</label>
                <input type="datetime-local" 
                       class="form-control form-select-sm mono-font" 
                       id="date_start" 
                       name="date_start" 
                       value="{{ $dateStart }}">
            </div>

            <!-- Sampai Tanggal & Jam -->
            <div class="col-md-2">
                <label for="date_end" class="form-label fw-bold small text-secondary">Sampai Tanggal & Jam</label>
                <input type="datetime-local" 
                       class="form-control form-select-sm mono-font" 
                       id="date_end" 
                       name="date_end" 
                       value="{{ $dateEnd }}">
            </div>

            <!-- Tombol Aksi -->
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 rounded-pill">
                    <i class="bi bi-search me-1"></i> Terapkan
                </button>
                <a href="{{ route('history.index') }}" class="btn btn-light btn-sm border rounded-pill" title="Reset filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

@if(count($chartLabels) > 0)
<!-- Grafik Tren Historis Beban Daya, Flow Rate & pH -->
<div class="card shadow-sm border-0 mb-4" style="border-radius: 16px;">
    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
        <div class="d-flex align-items-center gap-2">
            <div class="p-2 bg-primary-subtle text-primary rounded-3">
                <i class="bi bi-graph-up"></i>
            </div>
            <div>
                <span class="fw-bold text-dark d-block">Grafik Tren Historis Telemetri Instrument</span>
                <small class="text-muted fw-normal" style="font-size: 0.74rem;">Visualisasi fluktuasi laju alir (m³/h), tekanan pipa (Bar), dan akumulasi volume sepanjang rentang waktu terpilih</small>
            </div>
        </div>
        <span class="badge bg-light text-muted border mono-font px-2 py-1">{{ count($chartLabels) }} Titik Sampel</span>
    </div>
    <div class="card-body p-3 p-md-4">
        <div style="height: 270px; position: relative;">
            <canvas id="historyTrendChart"></canvas>
        </div>
    </div>
</div>
@endif

<!-- Tabel Riwayat Data Log -->
<div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden;">
    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
        <div class="fw-bold text-dark d-flex align-items-center gap-2">
            <div class="p-2 bg-info-subtle text-info rounded-3">
                <i class="bi bi-table"></i>
            </div>
            <div>
                <span class="d-block">Tabel Log Parameter Terinci</span>
                <small class="text-muted fw-normal" style="font-size: 0.74rem;">Menampilkan {{ $logs->count() }} baris pada halaman ini (Total {{ number_format($logs->total()) }} log)</small>
            </div>
        </div>
        <span class="badge bg-light text-dark border mono-font px-2 py-1">
            Halaman {{ $logs->currentPage() }} / {{ $logs->lastPage() }}
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-secondary small text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                <tr>
                    <th class="ps-4" style="width: 50px;">#</th>
                    <th>Waktu Log</th>
                    <th>Device Instrument</th>
                    <th>Totalizer (m³)</th>
                    <th>Flow Rate (m³/h)</th>
                    <th>{{ $hasPhSensor ? 'Tekanan / pH' : 'Tekanan Pipa (Bar)' }}</th>
                    @if($avgPower > 0 || $maxPower > 0)
                        <th>Tegangan (V)</th>
                        <th>Arus (A)</th>
                        <th>Daya (kW)</th>
                        <th>Total (kWh)</th>
                    @endif
                    <th class="text-center pe-4">Sumber</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $index => $log)
                    <tr>
                        <td class="ps-4 text-muted fw-semibold mono-font">{{ $logs->firstItem() + $index }}</td>
                        <td>
                            <div class="mono-font fw-semibold text-dark">{{ $log->logged_at->format('d M Y') }}</div>
                            <small class="text-muted mono-font">{{ $log->logged_at->format('H:i:s') }} WIB</small>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                <span class="fw-bold text-dark">{{ $log->device->name ?? 'Meter Dihapus' }}</span>
                                @if($log->device && $log->device->isPressureSensor())
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill py-0 px-1" style="font-size: 0.65rem;">{{ $log->device->category_badge }}</span>
                                @elseif($log->device && $log->device->isPhSensor())
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill py-0 px-1" style="font-size: 0.65rem;">{{ $log->device->category_badge }}</span>
                                @elseif($log->device && ($log->device->isFlowMeter() || $log->device->isWater()))
                                    <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill py-0 px-1" style="font-size: 0.65rem;">{{ $log->device->category_badge }}</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill py-0 px-1" style="font-size: 0.65rem;">{{ $log->device->category_badge ?? 'Meter' }}</span>
                                @endif
                            </div>
                            <small class="text-muted mono-font">Slave ID: {{ $log->device->slave_id ?? '-' }} &bull; {{ $log->device->ip_address ?? '-' }}</small>
                        </td>
                        <td>
                            @if($log->totalizer !== null)
                                <span class="mono-font fw-bold text-info">{{ number_format($log->totalizer, 1) }} m³</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($log->flow_rate !== null)
                                <span class="mono-font fw-semibold text-primary">{{ number_format($log->flow_rate, 1) }} m³/h</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($log->ph !== null)
                                @if($log->device && $log->device->isPressureSensor())
                                    <span class="mono-font fw-bold text-primary">{{ number_format($log->ph, 2) }} Bar</span>
                                @elseif($log->device && $log->device->isPhSensor())
                                    <span class="mono-font fw-bold text-success">{{ number_format($log->ph, 2) }} pH</span>
                                @else
                                    <span class="mono-font fw-bold text-primary">{{ number_format($log->ph, 2) }} Bar</span>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        @if($avgPower > 0 || $maxPower > 0)
                            <td>
                                @if($log->voltage !== null)
                                    <span class="mono-font fw-semibold text-primary">{{ number_format($log->voltage, 1) }} V</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($log->current !== null)
                                    <span class="mono-font fw-semibold text-warning">{{ number_format($log->current, 1) }} A</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($log->power !== null)
                                    <span class="mono-font fw-bold text-danger">{{ number_format($log->power, 2) }} kW</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($log->energy !== null)
                                    <span class="mono-font fw-bold text-success">{{ number_format($log->energy, 1) }} kWh</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        @endif
                        <td class="text-center pe-4">
                            @if($log->is_simulated)
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-1" title="Mode Simulator Offline">
                                    Sim
                                </span>
                            @else
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1" title="Terbaca dari Gateway Modbus TCP Fisik">
                                    Live
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ ($avgPower > 0 || $maxPower > 0) ? 11 : 7 }}" class="text-center py-5 text-muted">
                            <i class="bi bi-clock-history fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <h6 class="fw-bold mb-1">Belum Ada Data Log Telemetri Tersimpan</h6>
                            <p class="small text-muted mb-0">Data log riwayat otomatis dicatat saat instrumen meter merespons (online) pada Gateway RS485 loop.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div class="card-footer bg-white py-3 px-4 border-top">
            {{ $logs->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

<!-- Modal Pembersihan Log (Purge History) -->
<div class="modal fade" id="modalPurgeLogs" tabindex="-1" aria-labelledby="modalPurgeLogsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-header bg-danger text-white border-0 py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    <h6 class="modal-title fw-bold mb-0" id="modalPurgeLogsLabel">
                        Pembersihan Data Log
                    </h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('history.purge') }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body p-4">
                    <p class="text-secondary small mb-3">
                        Pilih rentang waktu data log yang ingin dihapus dari database SQLite lokal untuk menghemat memori laptop:
                    </p>
                    <div class="d-flex flex-column gap-2 mb-3">
                        <div class="form-check p-2 border rounded-3">
                            <input class="form-check-input ms-1" type="radio" name="range" id="range_30" value="older_30_days" checked>
                            <label class="form-check-label ps-2 fw-semibold text-dark" for="range_30">
                                Hapus log lebih dari 30 hari yang lalu
                            </label>
                        </div>
                        <div class="form-check p-2 border rounded-3">
                            <input class="form-check-input ms-1" type="radio" name="range" id="range_7" value="older_7_days">
                            <label class="form-check-label ps-2 fw-semibold text-dark" for="range_7">
                                Hapus log lebih dari 7 hari yang lalu
                            </label>
                        </div>
                        <div class="form-check p-2 border rounded-3">
                            <input class="form-check-input ms-1" type="radio" name="range" id="range_1" value="older_24_hours">
                            <label class="form-check-label ps-2 fw-semibold text-dark" for="range_1">
                                Hapus log lebih dari 24 jam yang lalu
                            </label>
                        </div>
                        <div class="form-check p-2 border border-warning-subtle bg-warning-subtle rounded-3">
                            <input class="form-check-input ms-1" type="radio" name="range" id="range_sim" value="simulated_only">
                            <label class="form-check-label ps-2 fw-semibold text-warning-emphasis" for="range_sim">
                                Hapus hanya data anomali simulasi (SIM) — Pertahankan data meter fisik real
                            </label>
                        </div>
                        <div class="form-check p-2 border border-danger-subtle bg-danger-subtle rounded-3">
                            <input class="form-check-input ms-1" type="radio" name="range" id="range_all" value="all">
                            <label class="form-check-label ps-2 fw-bold text-danger" for="range_all">
                                Hapus SELURUH riwayat data log (Kosongkan tabel)
                            </label>
                        </div>
                    </div>
                    <div class="alert alert-warning py-2 px-3 small mb-0 border-0" style="border-radius: 10px;">
                        <i class="bi bi-info-circle-fill me-1"></i> Tindakan penghapusan ini permanen pada database SQLite.
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3">
                        <i class="bi bi-trash3-fill me-1"></i> Ya, Hapus Data Log
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/chart.umd.min.js') }}"></script>
<script>
    // Quick Period Presets Helper
    function applyQuickFilter(hoursAgo) {
        const now = new Date();
        const start = new Date(now.getTime() - (hoursAgo * 60 * 60 * 1000));
        
        function formatDatetimeLocal(d) {
            const pad = n => String(n).padStart(2, '0');
            return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
        }
        
        document.getElementById('date_start').value = formatDatetimeLocal(start);
        document.getElementById('date_end').value = formatDatetimeLocal(now);
        document.getElementById('historyFilterForm').submit();
    }

    // Chart Historis
    const chartLabels = @json($chartLabels);
    const chartPowers = @json($chartPowers);
    const chartCurrents = @json($chartCurrents);
    const chartFlowRates = @json($chartFlowRates);
    const chartPhs = @json($chartPhs);

    if (chartLabels.length > 0 && document.getElementById('historyTrendChart')) {
        const ctx = document.getElementById('historyTrendChart').getContext('2d');
        const hasFlow = chartFlowRates.some(v => v > 0);
        const hasPh = chartPhs.some(v => v > 0);
        const hasPower = chartPowers.some(v => v > 0);

        let datasets = [];
        let yUnit = 'kW';
        let y1Unit = 'A';

        if (hasFlow || hasPh) {
            const gradFlow = ctx.createLinearGradient(0, 0, 0, 240);
            gradFlow.addColorStop(0, 'rgba(2, 132, 199, 0.25)');
            gradFlow.addColorStop(1, 'rgba(2, 132, 199, 0.0)');

            datasets.push({
                label: 'Flow Rate (m³/h)',
                data: chartFlowRates,
                borderColor: '#0284c7',
                backgroundColor: gradFlow,
                borderWidth: 2.2,
                tension: 0.35,
                fill: true,
                pointRadius: 2,
                yAxisID: 'y'
            });

            datasets.push({
                label: '{{ $hasPressureSensor || ! $hasPhSensor ? 'Tekanan Pipa (Bar)' : 'Nilai pH' }}',
                data: chartPhs,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.05)',
                borderWidth: 2,
                borderDash: [3, 3],
                tension: 0.35,
                fill: false,
                pointRadius: 2,
                yAxisID: 'y1'
            });

            if (hasPower) {
                datasets.push({
                    label: 'Daya Aktif (kW)',
                    data: chartPowers,
                    borderColor: '#f59e0b',
                    borderWidth: 1.5,
                    tension: 0.35,
                    fill: false,
                    pointRadius: 2,
                    yAxisID: 'y'
                });
            }

            yUnit = 'm³/h';
            y1Unit = '{{ $hasPressureSensor || ! $hasPhSensor ? 'Bar' : 'pH' }}';
        } else {
            const gradPower = ctx.createLinearGradient(0, 0, 0, 240);
            gradPower.addColorStop(0, 'rgba(37, 99, 235, 0.2)');
            gradPower.addColorStop(1, 'rgba(37, 99, 235, 0.0)');

            datasets.push({
                label: 'Daya Aktif (kW)',
                data: chartPowers,
                borderColor: '#2563eb',
                backgroundColor: gradPower,
                borderWidth: 2.2,
                tension: 0.35,
                fill: true,
                pointRadius: 2,
                yAxisID: 'y'
            });

            datasets.push({
                label: 'Arus (A)',
                data: chartCurrents,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.05)',
                borderWidth: 1.8,
                borderDash: [4, 4],
                tension: 0.35,
                fill: false,
                pointRadius: 2,
                yAxisID: 'y1'
            });
        }

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { boxWidth: 14, font: { family: 'Plus Jakarta Sans', size: 12 } }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        padding: 10,
                        titleFont: { family: 'JetBrains Mono', size: 12 },
                        bodyFont: { family: 'JetBrains Mono', size: 12 }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'JetBrains Mono', size: 10 }, color: '#64748b' }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            font: { family: 'JetBrains Mono', size: 10 },
                            color: '#0284c7',
                            callback: function(v) { return v + ' ' + yUnit; }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        ticks: {
                            font: { family: 'JetBrains Mono', size: 10 },
                            color: hasFlow || hasPh ? '#10b981' : '#f59e0b',
                            callback: function(v) { return v + ' ' + y1Unit; }
                        }
                    }
                }
            }
        });
    }
</script>
@endsection
