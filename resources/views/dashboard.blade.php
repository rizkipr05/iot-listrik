@extends('layouts.app')

@section('title', 'FLOW & WATER METER & PRESSURE MONITORING SYSTEM')

@section('scada-fullscreen', true)

@section('styles')
<style>
    /* SCADA Theme Tokens */
    :root {
        --scada-navy-dark: #0a1329;
        --scada-navy-mid: #102144;
        --scada-navy-header: #1e3a8a;
        --scada-blue: #1d4ed8;
        --scada-green: #059669;
        --scada-purple: #7c3aed;
        --scada-orange: #ea580c;
        --scada-card-bg: #ffffff;
        --scada-border: #e2e8f0;
    }

    /* Top Banner Styling */
    .scada-banner {
        background: linear-gradient(135deg, #0b1c3b 0%, #15325b 55%, #0e2142 100%);
        border: 1px solid rgba(59, 130, 246, 0.28);
        border-radius: 12px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 18px rgba(11, 28, 59, 0.15);
    }

    .scada-banner::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 80% 30%, rgba(56, 189, 248, 0.12) 0%, transparent 60%);
        pointer-events: none;
    }

    .scada-home-btn {
        width: 44px;
        height: 44px;
        border-radius: 9px;
        background: #2563eb;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.4);
        flex-shrink: 0;
        transition: transform 0.15s ease, background-color 0.15s ease;
    }

    .scada-home-btn:hover {
        background: #1d4ed8;
        transform: scale(1.04);
        color: #ffffff;
    }

    .scada-time-box {
        background: rgba(7, 18, 41, 0.72);
        border: 1px solid rgba(96, 165, 250, 0.25);
        border-radius: 8px;
        padding: 6px 18px;
        text-align: right;
        min-width: 140px;
        box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.4);
    }

    /* SCADA Card Styling */
    .scada-card {
        background: #ffffff;
        border: 1px solid var(--scada-border);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
        display: flex;
        flex-direction: column;
        height: 100%;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .scada-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
    }

    .scada-card-header {
        padding: 7px 14px;
        color: #ffffff;
        font-weight: 700;
        font-size: 0.85rem;
        letter-spacing: 0.03em;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        text-transform: uppercase;
    }

    .scada-card-header.header-rms { background-color: var(--scada-blue); }
    .scada-card-header.header-mhs { background-color: var(--scada-green); }
    .scada-card-header.header-h-rms { background-color: var(--scada-purple); }
    .scada-card-header.header-ph { background-color: var(--scada-orange); }
    .scada-card-header.header-navy { background-color: var(--scada-navy-header); }

    .scada-card-footer {
        background: #f8fafc;
        border-top: 1px solid #f1f5f9;
        padding: 5px 12px;
        font-size: 0.72rem;
        color: #64748b;
        font-weight: 500;
        text-align: center;
        margin-top: auto;
    }

    /* Circular SVG Gauge */
    .circular-gauge-container {
        position: relative;
        width: 110px;
        height: 110px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .circular-gauge-svg {
        transform: rotate(-90deg);
        width: 100%;
        height: 100%;
    }

    .gauge-bg-circle {
        fill: none;
        stroke: #e2e8f0;
        stroke-width: 8;
    }

    .gauge-progress-circle {
        fill: none;
        stroke-width: 8;
        stroke-linecap: round;
        transition: stroke-dashoffset 0.8s ease-in-out;
    }

    .gauge-inner-text {
        position: absolute;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        line-height: 1.1;
    }

    /* Color Bars & Elements */
    .ph-gradient-bar {
        height: 11px;
        border-radius: 6px;
        background: linear-gradient(to right, 
            #ef4444 0%, 
            #eab308 10%, 
            #10b981 15%, 
            #10b981 40%, 
            #eab308 55%, 
            #ef4444 80%, 
            #b91c1c 100%
        );
        position: relative;
    }

    .ph-pointer-indicator {
        position: absolute;
        top: -6px;
        transform: translateX(-50%);
        width: 0;
        height: 0;
        border-left: 6px solid transparent;
        border-right: 6px solid transparent;
        border-top: 8px solid #0f172a;
        transition: left 0.5s ease;
    }

    /* Dark SCADA Chart Box */
    .scada-chart-darkbox {
        background: #091326;
        border-radius: 8px;
        padding: 12px;
        position: relative;
    }

    /* Table SCADA styling */
    .scada-table {
        margin-bottom: 0;
        font-size: 0.82rem;
    }

    .scada-table thead tr th {
        background: #f1f5f9;
        color: #334155;
        font-weight: 700;
        text-align: center;
        vertical-align: middle;
        border-color: #cbd5e1;
        padding: 6px 8px;
    }

    .scada-table tbody tr td {
        text-align: center;
        vertical-align: middle;
        padding: 5px 8px;
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.8rem;
        border-color: #e2e8f0;
        color: #1e293b;
    }

    .scada-table tbody tr:nth-child(even) {
        background-color: #f8fafc;
    }

    .scada-table tbody tr:hover {
        background-color: #f0f7ff;
    }

    /* Bottom Copyright Banner */
    .scada-bottom-banner {
        background: linear-gradient(90deg, #091834 0%, #10264d 50%, #091834 100%);
        color: #cbd5e1;
        font-size: 0.78rem;
        padding: 7px 16px;
        border-radius: 6px;
        text-align: center;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    }

    .scada-bottom-banner a {
        color: #38bdf8;
        text-decoration: none;
        font-weight: 600;
    }

    .scada-bottom-banner a:hover {
        text-decoration: underline;
    }

    /* Match the compact, full-screen SCADA reference layout. */
    body.scada-fullscreen { background: #dcebf2; color: #18334d; overflow-x: hidden; }
    body.scada-fullscreen #main-content { margin-left: 0; min-height: 100vh; width: 100%; min-width: 0; }
    body.scada-fullscreen #main-content { transition: margin-left .25s ease, width .25s ease; }
    body.scada-fullscreen #main-content > main { padding: 4px !important; }
    body.scada-fullscreen #sidebar { transform: translateX(-100%); margin-left: 0; }
    body.scada-fullscreen #sidebar.active { transform: translateX(0); }
    .scada-sidebar-backdrop { display: none; position: fixed; inset: 0; z-index: 999; background: rgba(3, 12, 28, .32); }
    .scada-sidebar-backdrop.active { display: block; }
    body.scada-fullscreen .container-fluid { padding: 0 !important; }
    .scada-fullscreen .scada-banner { min-height: 72px; border-radius: 5px; padding: 10px 12px !important; margin-bottom: 8px !important; background-image: linear-gradient(100deg, #052d66 0%, rgba(18, 103, 179, .94) 56%, rgba(18, 61, 118, .62) 100%), url('{{ asset('assets/scada-header-background.jpg') }}'); background-position: center, right center; background-repeat: no-repeat; background-size: 100% 100%, auto 100%; }
    .scada-fullscreen .scada-banner::after { content: ''; position: absolute; inset: 0 18% 0 56%; background: linear-gradient(90deg, transparent, rgba(103, 190, 239, .25), transparent); pointer-events: none; }
    .scada-fullscreen .scada-home-btn { min-width: 108px; height: 34px; padding: 0 10px; gap: 6px; border-radius: 6px; background: rgba(255, 255, 255, .13); box-shadow: none; font-size: 1rem; }
    .scada-fullscreen .scada-home-btn:hover { background: rgba(255, 255, 255, .24); }
    .scada-menu-label { color: #fff; font-size: .68rem; font-weight: 700; letter-spacing: .04em; white-space: nowrap; }
    .scada-fullscreen .scada-banner .text-info-subtle { color: #d9eeff !important; background: transparent !important; }
    .scada-fullscreen .scada-time-box { min-width: 120px; padding: 3px 8px; border: 0; background: transparent; box-shadow: none; }
    .scada-fullscreen .scada-card { border: 1px solid #a9c9da; border-radius: 6px; box-shadow: 0 1px 4px rgba(22, 67, 98, .12); }
    .scada-fullscreen .scada-card-header { min-height: 30px; padding: 4px 9px; font-size: .74rem; }
    .scada-fullscreen .scada-card-header.header-rms { background-color: #1d4ed8; }
    .scada-fullscreen .scada-card-header.header-mhs { background-color: #059669; }
    .scada-fullscreen .scada-card-header.header-h-rms { background-color: #7c3aed; }
    .scada-fullscreen .scada-card-header.header-ph { background-color: #ea580c; }
    .scada-fullscreen .row { --bs-gutter-x: .45rem; --bs-gutter-y: .45rem; }
    .scada-fullscreen .row > [class*="col-"] { min-width: 0; }
    .scada-fullscreen .scada-card { min-width: 0; }
    .scada-fullscreen .row.mb-3 { margin-bottom: 7px !important; }
    .scada-fullscreen .scada-card > .p-3 { padding: 8px !important; }
    .scada-fullscreen .circular-gauge-container { width: clamp(76px, 8vw, 104px); height: clamp(76px, 8vw, 104px); }
    .scada-fullscreen .scada-card-footer { padding: 3px 6px; font-size: .64rem; }
    .scada-fullscreen .scada-chart-darkbox { padding: 7px; }
    .scada-fullscreen .scada-chart-darkbox > div { height: clamp(160px, 22vh, 210px) !important; }
    .scada-fullscreen .scada-bottom-banner { border-radius: 3px; padding: 5px 10px; background: linear-gradient(90deg, #073268, #145da0, #073268); }
    .scada-fullscreen .scada-table { font-size: .68rem; }
    .scada-fullscreen .scada-table thead tr th { padding: 3px 5px; }
    .scada-fullscreen .scada-table tbody tr td { padding: 2px 5px; font-size: .66rem; }
    .scada-fullscreen .scada-card.mb-3 { margin-bottom: 7px !important; }
    @media (min-width: 768px) {
        .scada-fullscreen .col-sm-6.col-xl-3 { flex: 0 0 25%; width: 25%; max-width: 25%; }
    }
    @media (min-width: 992px) {
        body.scada-fullscreen.scada-sidebar-open #main-content { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); }
        body.scada-fullscreen.scada-sidebar-open .scada-sidebar-backdrop { display: none; }
        .scada-fullscreen .col-xl-8 { width: 63%; }
        .scada-fullscreen .col-xl-4 { width: 37%; }
    }
    @media (min-width: 1200px) {
        .scada-fullscreen .scada-card-header .fs-5 { font-size: 1rem !important; }
    }
    @media (max-width: 767.98px) {
        body.scada-fullscreen { overflow-x: auto; }
        .scada-fullscreen .scada-table { min-width: 850px; }
        .scada-fullscreen .scada-banner h4 { font-size: 1rem !important; }
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-1 py-1">

    <!-- 1. Top Header Banner -->
    <div class="scada-banner p-3 p-md-3 mb-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="scada-home-btn border-0" id="scadaSidebarToggle" title="Buka menu" aria-label="Buka menu" aria-expanded="false">
                    <i class="bi bi-list fs-4"></i>
                    <span class="scada-menu-label" id="scadaSidebarToggleLabel">BUKA MENU</span>
                </button>
                <div>
                    <h4 class="text-white fw-bold mb-0 text-uppercase" style="letter-spacing: 0.03em; font-size: 1.35rem;">
                        FLOW & WATER METER & PRESSURE MONITORING SYSTEM
                    </h4>
                    <div class="text-info-subtle small fw-medium mt-1" style="font-size: 0.82rem; opacity: 0.95;">
                        <span>2* Electromagnetic, 1* WM Ultrasonic, 1* WM Photoelectric, 1* Pressure Sensor</span>
                    </div>
                </div>
            </div>

            <!-- Real-time Clock & Date Widget -->
            <div class="scada-time-box">
                <div class="text-white-50 mono-font small lh-1" id="scadaLiveDate" style="font-size: 0.78rem;">
                    30 Sep 2026
                </div>
                <div class="text-white fw-bold mono-font fs-4 my-1 lh-1" id="scadaLiveClock">
                    13:24:36
                </div>
                <div class="text-white-50 small lh-1" id="scadaLiveDay" style="font-size: 0.74rem;">
                    Rabu
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Row 1: The 4 Main Metric Cards (RMS, MHS, H-RMS, pH Sensor) -->
    <div class="row g-3 mb-3">
        <!-- Card 1: Flowmeter 2" Area Chiller -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="scada-card">
                <div class="scada-card-header header-rms">
                    <div class="d-flex align-items-center gap-2 text-truncate">
                        <i class="bi bi-broadcast fs-5 flex-shrink-0"></i>
                        <span class="text-truncate">{{ $labelRms }}</span>
                    </div>
                    <span class="badge rounded-pill bg-white {{ $isRmsOnline ? 'text-success border-success' : 'text-danger border-danger' }} border fw-bold px-2 py-0" style="font-size: 0.7rem; text-transform: lowercase;" id="headerBadgeRMS">
                        {{ $isRmsOnline ? 'online' : 'offline' }}
                    </span>
                </div>
                <div class="p-3">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <!-- Gauge Circle (Left) -->
                        <div class="text-center">
                            <div class="circular-gauge-container">
                                <svg class="circular-gauge-svg" viewBox="0 0 100 100">
                                    <circle class="gauge-bg-circle" cx="50" cy="50" r="40"></circle>
                                    <circle class="gauge-progress-circle" id="circleProgressRMS" cx="50" cy="50" r="40"
                                        stroke="#1d4ed8" stroke-dasharray="251.32" stroke-dashoffset="{{ $gaugeOffsetRms }}">
                                    </circle>
                                </svg>
                                <div class="gauge-inner-text">
                                    <span class="text-muted" style="font-size: 0.65rem;">Flowrate</span>
                                    <span class="fw-bold mono-font text-dark fs-5" id="valFlowrateRMS">{{ number_format($currentRmsFlow, 1) }}</span>
                                    <span class="text-muted" style="font-size: 0.65rem;">m³/h</span>
                                </div>
                            </div>
                            <div class="fw-bold mono-font mt-1" style="color: #1d4ed8; font-size: 0.95rem;" id="pctRMS">{{ $gaugePctRms }}%</div>
                            <div class="fw-bold mono-font {{ $isRmsOnline ? 'text-success' : 'text-danger' }}" style="font-size: 0.75rem; text-transform: lowercase; line-height: 1;" id="statusTextRMS">
                                {{ $isRmsOnline ? 'online' : 'offline' }}
                            </div>
                        </div>

                        <!-- Details (Right) -->
                        <div class="flex-grow-1 ps-2">
                            <!-- Flowrate Detail -->
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <i class="bi bi-droplet-fill fs-5" style="color: #1d4ed8;"></i>
                                <div style="line-height: 1.2;">
                                    <div class="text-muted small" style="font-size: 0.72rem;">Flowrate</div>
                                    <div class="fw-bold mono-font text-dark fs-6">
                                        <span id="detailFlowrateRMS">{{ number_format($currentRmsFlow, 1) }}</span> <small class="fw-normal text-muted" style="font-size: 0.75rem;">m³/h</small>
                                    </div>
                                    <div class="text-secondary" style="font-size: 0.68rem;" id="rangeTextRMS">(Range 0 ~ {{ $maxRms }} m³/h)</div>
                                </div>
                            </div>

                            <!-- Totality Detail -->
                            <div class="d-flex align-items-start gap-2">
                                <span class="fw-bold fs-5 lh-1" style="color: #1d4ed8; font-family: serif;">Σ</span>
                                <div style="line-height: 1.2;">
                                    <div class="text-muted small" style="font-size: 0.72rem;">Totality</div>
                                    <div class="fw-bold mono-font text-dark fs-6">
                                        <span id="detailTotalityRMS">{{ number_format($currentRmsTot, 1) }}</span> <small class="fw-normal text-muted" style="font-size: 0.75rem;">m³</small>
                                    </div>
                                    <div class="text-secondary" style="font-size: 0.68rem;">(Totalizer)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="scada-card-footer">
                    {{ $descRms }}
                </div>
            </div>
        </div>

        <!-- Card 2: Water meter 3/4" Area Toilet -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="scada-card">
                <div class="scada-card-header header-mhs">
                    <div class="d-flex align-items-center gap-2 text-truncate">
                        <i class="bi bi-broadcast fs-5 flex-shrink-0"></i>
                        <span class="text-truncate">{{ $labelMhs }}</span>
                    </div>
                    <span class="badge rounded-pill bg-white {{ $isMhsOnline ? 'text-success border-success' : 'text-danger border-danger' }} border fw-bold px-2 py-0" style="font-size: 0.7rem; text-transform: lowercase;" id="headerBadgeMHS">
                        {{ $isMhsOnline ? 'online' : 'offline' }}
                    </span>
                </div>
                <div class="p-3">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <!-- Gauge Circle (Left) -->
                        <div class="text-center">
                            <div class="circular-gauge-container">
                                <svg class="circular-gauge-svg" viewBox="0 0 100 100">
                                    <circle class="gauge-bg-circle" cx="50" cy="50" r="40"></circle>
                                    <circle class="gauge-progress-circle" id="circleProgressMHS" cx="50" cy="50" r="40"
                                        stroke="#059669" stroke-dasharray="251.32" stroke-dashoffset="{{ $gaugeOffsetMhs }}">
                                    </circle>
                                </svg>
                                <div class="gauge-inner-text">
                                    <span class="text-muted" style="font-size: 0.65rem;">Flowrate</span>
                                    <span class="fw-bold mono-font text-dark fs-5" id="valFlowrateMHS">{{ number_format($currentMhsFlow, 1) }}</span>
                                    <span class="text-muted" style="font-size: 0.65rem;">m³/h</span>
                                </div>
                            </div>
                            <div class="fw-bold mono-font mt-1" style="color: #059669; font-size: 0.95rem;" id="pctMHS">{{ $gaugePctMhs }}%</div>
                            <div class="fw-bold mono-font {{ $isMhsOnline ? 'text-success' : 'text-danger' }}" style="font-size: 0.75rem; text-transform: lowercase; line-height: 1;" id="statusTextMHS">
                                {{ $isMhsOnline ? 'online' : 'offline' }}
                            </div>
                        </div>

                        <!-- Details (Right) -->
                        <div class="flex-grow-1 ps-2">
                            <!-- Flowrate Detail -->
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <i class="bi bi-droplet-fill fs-5" style="color: #059669;"></i>
                                <div style="line-height: 1.2;">
                                    <div class="text-muted small" style="font-size: 0.72rem;">Flowrate</div>
                                    <div class="fw-bold mono-font text-dark fs-6">
                                        <span id="detailFlowrateMHS">{{ number_format($currentMhsFlow, 1) }}</span> <small class="fw-normal text-muted" style="font-size: 0.75rem;">m³/h</small>
                                    </div>
                                    <div class="text-secondary" style="font-size: 0.68rem;" id="rangeTextMHS">(Range 0 ~ {{ $maxMhs }} m³/h)</div>
                                </div>
                            </div>

                            <!-- Totality Detail -->
                            <div class="d-flex align-items-start gap-2">
                                <span class="fw-bold fs-5 lh-1" style="color: #059669; font-family: serif;">Σ</span>
                                <div style="line-height: 1.2;">
                                    <div class="text-muted small" style="font-size: 0.72rem;">Totality</div>
                                    <div class="fw-bold mono-font text-dark fs-6">
                                        <span id="detailTotalityMHS">{{ number_format($currentMhsTot, 1) }}</span> <small class="fw-normal text-muted" style="font-size: 0.75rem;">m³</small>
                                    </div>
                                    <div class="text-secondary" style="font-size: 0.68rem;">(Totalizer)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="scada-card-footer">
                    {{ $descMhs }}
                </div>
            </div>
        </div>

        <!-- Card 3: Water meter 1/2" Area Office -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="scada-card">
                <div class="scada-card-header header-h-rms">
                    <div class="d-flex align-items-center gap-2 text-truncate">
                        <i class="bi bi-broadcast fs-5 flex-shrink-0"></i>
                        <span class="text-truncate">{{ $labelHrms }}</span>
                    </div>
                    <span class="badge rounded-pill bg-white {{ $isHrmsOnline ? 'text-success border-success' : 'text-danger border-danger' }} border fw-bold px-2 py-0" style="font-size: 0.7rem; text-transform: lowercase;" id="headerBadgeHRMS">
                        {{ $isHrmsOnline ? 'online' : 'offline' }}
                    </span>
                </div>
                <div class="p-3">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <!-- Gauge Circle (Left) -->
                        <div class="text-center">
                            <div class="circular-gauge-container">
                                <svg class="circular-gauge-svg" viewBox="0 0 100 100">
                                    <circle class="gauge-bg-circle" cx="50" cy="50" r="40"></circle>
                                    <circle class="gauge-progress-circle" id="circleProgressHRMS" cx="50" cy="50" r="40"
                                        stroke="#7c3aed" stroke-dasharray="251.32" stroke-dashoffset="{{ $gaugeOffsetHrms }}">
                                    </circle>
                                </svg>
                                <div class="gauge-inner-text">
                                    <span class="text-muted" style="font-size: 0.65rem;">Flowrate</span>
                                    <span class="fw-bold mono-font text-dark fs-5" id="valFlowrateHRMS">{{ number_format($currentHrmsFlow, 1) }}</span>
                                    <span class="text-muted" style="font-size: 0.65rem;">m³/h</span>
                                </div>
                            </div>
                            <div class="fw-bold mono-font mt-1" style="color: #7c3aed; font-size: 0.95rem;" id="pctHRMS">{{ $gaugePctHrms }}%</div>
                            <div class="fw-bold mono-font {{ $isHrmsOnline ? 'text-success' : 'text-danger' }}" style="font-size: 0.75rem; text-transform: lowercase; line-height: 1;" id="statusTextHRMS">
                                {{ $isHrmsOnline ? 'online' : 'offline' }}
                            </div>
                        </div>

                        <!-- Details (Right) -->
                        <div class="flex-grow-1 ps-2">
                            <!-- Flowrate Detail -->
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <i class="bi bi-droplet-fill fs-5" style="color: #7c3aed;"></i>
                                <div style="line-height: 1.2;">
                                    <div class="text-muted small" style="font-size: 0.72rem;">Flowrate</div>
                                    <div class="fw-bold mono-font text-dark fs-6">
                                        <span id="detailFlowrateHRMS">{{ number_format($currentHrmsFlow, 1) }}</span> <small class="fw-normal text-muted" style="font-size: 0.75rem;">m³/h</small>
                                    </div>
                                    <div class="text-secondary" style="font-size: 0.68rem;" id="rangeTextHRMS">(Range 0 ~ {{ $maxHrms }} m³/h)</div>
                                </div>
                            </div>

                            <!-- Totality Detail -->
                            <div class="d-flex align-items-start gap-2">
                                <span class="fw-bold fs-5 lh-1" style="color: #7c3aed; font-family: serif;">Σ</span>
                                <div style="line-height: 1.2;">
                                    <div class="text-muted small" style="font-size: 0.72rem;">Totality</div>
                                    <div class="fw-bold mono-font text-dark fs-6">
                                        <span id="detailTotalityHRMS">{{ number_format($currentHrmsTot, 1) }}</span> <small class="fw-normal text-muted" style="font-size: 0.75rem;">m³</small>
                                    </div>
                                    <div class="text-secondary" style="font-size: 0.68rem;">(Totalizer)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="scada-card-footer">
                    {{ $descHrms }}
                </div>
            </div>
        </div>

        <!-- Card 4: Pressure Sensor -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="scada-card">
                <div class="scada-card-header header-ph">
                    <div class="d-flex align-items-center gap-2 text-truncate">
                        <i class="bi bi-speedometer2 fs-5 flex-shrink-0"></i>
                        <span class="text-truncate">{{ $labelPh }}</span>
                    </div>
                    <span class="badge rounded-pill bg-white {{ $isPhOnline ? 'text-success border-success' : 'text-danger border-danger' }} border fw-bold px-2 py-0" style="font-size: 0.7rem; text-transform: lowercase;" id="headerBadgePH">
                        {{ $isPhOnline ? 'online' : 'offline' }}
                    </span>
                </div>
                <div class="p-3">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <!-- Gauge Circle (Left) -->
                        <div class="text-center">
                            <div class="circular-gauge-container">
                                <svg class="circular-gauge-svg" viewBox="0 0 100 100">
                                    <circle class="gauge-bg-circle" cx="50" cy="50" r="40"></circle>
                                    <circle class="gauge-progress-circle" id="circleProgressPH" cx="50" cy="50" r="40"
                                        stroke="#ea580c" stroke-dasharray="251.32" stroke-dashoffset="{{ $gaugeOffsetPh }}">
                                    </circle>
                                </svg>
                                <div class="gauge-inner-text">
                                    <span class="text-muted" style="font-size: 0.65rem;">Bar</span>
                                    <span class="fw-bold mono-font text-dark fs-5" id="valPH">{{ number_format($currentPh, 2) }}</span>
                                    <span class="text-muted" style="font-size: 0.65rem;">Bar</span>
                                </div>
                            </div>
                            <div class="mt-1">
                                <span class="badge {{ $statusPh === 'Normal' ? 'bg-success text-white' : ($statusPh === 'Warning' ? 'bg-warning text-dark' : 'bg-secondary text-white') }} fw-bold px-2 py-0" style="font-size: 0.75rem;" id="phStatusBadge">{{ $statusPh }}</span>
                            </div>
                            <div class="fw-bold mono-font mt-1 {{ $isPhOnline ? 'text-success' : 'text-danger' }}" style="font-size: 0.75rem; text-transform: lowercase; line-height: 1;" id="statusTextPH">
                                {{ $isPhOnline ? 'online' : 'offline' }}
                            </div>
                        </div>

                        <!-- Details (Right) -->
                        <div class="flex-grow-1 ps-2">
                            <!-- Pressure Value Detail -->
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <i class="bi bi-speedometer2 fs-5" style="color: #ea580c;"></i>
                                <div style="line-height: 1.2;">
                                    <div class="text-muted small" style="font-size: 0.72rem;">Pressure Value</div>
                                    <div class="fw-bold mono-font text-dark fs-6">
                                        <span id="detailValPH">{{ number_format($currentPh, 2) }}</span> <small class="fw-normal text-muted" style="font-size: 0.75rem;">Bar</small>
                                    </div>
                                    <div class="text-secondary" style="font-size: 0.68rem;" id="rangeTextPH">(Range 0 ~ {{ $maxPh }} bar)</div>
                                </div>
                            </div>

                            <!-- Status Detail -->
                            <div class="d-flex align-items-start gap-2">
                                <i class="bi {{ $statusPh === 'Normal' ? 'bi-check-circle-fill text-success' : ($statusPh === 'Warning' ? 'bi-exclamation-triangle-fill text-warning' : 'bi-pause-circle-fill text-secondary') }} fs-5" id="phStatusIcon"></i>
                                <div style="line-height: 1.2;">
                                    <div class="text-muted small" style="font-size: 0.72rem;">Status</div>
                                    <div class="fw-bold {{ $statusPh === 'Normal' ? 'text-success' : ($statusPh === 'Warning' ? 'text-warning' : 'text-secondary') }} fs-6" id="phStatusDetail">{{ $statusPh }}</div>
                                    <div class="text-secondary" style="font-size: 0.68rem;">(1 ~ 4 bar)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="scada-card-footer">
                    {{ $descPh }}
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Row 2: Trend Grafik (Left) & Flow Rate Overview (Right) -->
    <div class="row g-3 mb-3">
        <!-- Left: TREND GRAFIK (Last 24 Hours) -->
        <div class="col-12 col-xl-8">
            <div class="scada-card h-100">
                <div class="scada-card-header header-navy">
                    <i class="bi bi-graph-up fs-5"></i>
                    <span>TREND GRAFIK (Last 24 Hours)</span>
                </div>
                <div class="p-3 d-flex flex-column flex-grow-1">
                    <div class="scada-chart-darkbox flex-grow-1">
                        <div style="position: relative; height: 280px; width: 100%;">
                            <canvas id="scadaTrendChart"></canvas>
                        </div>
                    </div>
                    <!-- Custom Chart Legend -->
                    <div class="d-flex flex-wrap align-items-center justify-content-center gap-4 mt-2 pt-1 small">
                        <div class="d-flex align-items-center gap-2">
                            <span style="display:inline-block; width: 18px; height: 3px; background-color: #3b82f6; border-radius: 2px;"></span>
                            <span class="text-secondary mono-font" style="font-size: 0.78rem;">{{ $labelRms }} (m³/h)</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span style="display:inline-block; width: 18px; height: 3px; background-color: #10b981; border-radius: 2px;"></span>
                            <span class="text-secondary mono-font" style="font-size: 0.78rem;">{{ $labelMhs }} (m³/h)</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span style="display:inline-block; width: 18px; height: 3px; background-color: #a855f7; border-radius: 2px;"></span>
                            <span class="text-secondary mono-font" style="font-size: 0.78rem;">{{ $labelHrms }} (m³/h)</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span style="display:inline-block; width: 18px; height: 3px; background-color: #eab308; border-radius: 2px;"></span>
                            <span class="text-secondary mono-font" style="font-size: 0.78rem;">{{ $labelPh }} (Bar)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: FLOW RATE OVERVIEW (Current) -->
        <div class="col-12 col-xl-4">
            <div class="scada-card h-100">
                <div class="scada-card-header header-navy">
                    <i class="bi bi-pie-chart-fill fs-5"></i>
                    <span>FLOW RATE OVERVIEW <small class="fw-normal text-white-50" style="text-transform: none;">(Current)</small></span>
                </div>
                <div class="p-3 d-flex flex-column justify-content-between h-100">
                    <!-- Donut Chart & Distribution Table -->
                    <div class="row align-items-center g-2 mb-3">
                        <div class="col-6 text-center">
                            <div style="position: relative; width: 135px; height: 135px; margin: 0 auto;">
                                <canvas id="scadaDonutChart"></canvas>
                                <div style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none; line-height: 1.15;">
                                    <span class="text-muted" style="font-size: 0.65rem;">Total Flow</span>
                                    <span class="fw-bold mono-font text-dark fs-5" id="donutCenterTotal">{{ number_format($totalFlowCurrent, 1) }}</span>
                                    <span class="text-muted" style="font-size: 0.65rem;">m³/h</span>
                                    <span class="text-secondary" style="font-size: 0.60rem;">(3 Unit)</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <table class="table table-borderless table-sm mb-0 mono-font" style="font-size: 0.8rem;">
                                <tbody>
                                    <tr>
                                        <td><span class="badge rounded-circle p-1 me-1" style="background-color: #3b82f6;">&nbsp;</span><strong class="text-dark">{{ $labelRms }}</strong></td>
                                        <td class="text-end fw-bold text-dark" id="overviewRmsVal">{{ number_format($currentRmsFlow, 1) }} <small class="fw-normal text-muted">m³/h</small></td>
                                        <td class="text-end text-muted" id="overviewRmsPct">{{ $pctRms }}%</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge rounded-circle p-1 me-1" style="background-color: #10b981;">&nbsp;</span><strong class="text-dark">{{ $labelMhs }}</strong></td>
                                        <td class="text-end fw-bold text-dark" id="overviewMhsVal">{{ number_format($currentMhsFlow, 1) }} <small class="fw-normal text-muted">m³/h</small></td>
                                        <td class="text-end text-muted" id="overviewMhsPct">{{ $pctMhs }}%</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge rounded-circle p-1 me-1" style="background-color: #a855f7;">&nbsp;</span><strong class="text-dark">{{ $labelHrms }}</strong></td>
                                        <td class="text-end fw-bold text-dark" id="overviewHrmsVal">{{ number_format($currentHrmsFlow, 1) }} <small class="fw-normal text-muted">m³/h</small></td>
                                        <td class="text-end text-muted" id="overviewHrmsPct">{{ $pctHrms }}%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Totalizer (All Flowmeter) Box -->
                    <div class="p-2 mb-3 rounded-3 d-flex align-items-center gap-3" style="background: #f0f7ff; border: 1px solid #dbeafe;">
                        <span class="fs-2 fw-bold text-primary lh-1 ps-2" style="font-family: serif;">Σ</span>
                        <div style="line-height: 1.2;">
                            <div class="text-muted small fw-semibold" style="font-size: 0.72rem;">Totalizer (All Flowmeter)</div>
                            <div class="fw-bold mono-font text-dark fs-5" id="valTotalizerAll">
                                {{ number_format($totalizerAllCurrent, 1) }} <small class="fw-normal text-muted fs-6">m³</small>
                            </div>
                        </div>
                    </div>

                    <!-- Pressure Status Bar -->
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-1 text-muted small fw-semibold" style="font-size: 0.76rem;">
                                <i class="bi bi-speedometer2 text-warning"></i>
                                <span>Pressure Status</span>
                            </div>
                            <div class="small mono-font" style="font-size: 0.76rem;">
                                Current: <strong class="{{ $statusPh === 'Normal' ? 'text-success' : ($statusPh === 'Warning' ? 'text-warning' : 'text-secondary') }}" id="valPhStatusCurrent">{{ number_format($currentPh, 2) }} Bar</strong>
                            </div>
                        </div>
                        <div class="ph-gradient-bar mb-1">
                            <div class="ph-pointer-indicator" id="phPointer" style="left: {{ $gaugePctPh }}%;"></div>
                        </div>
                        <div class="d-flex justify-content-between text-muted mono-font" style="font-size: 0.65rem;">
                            <span>0</span>
                            <span>2</span>
                            <span>4</span>
                            <span>6</span>
                            <span>8</span>
                            <span>10 bar</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Row 3: Full-width Table: HISTORY DATA (Update Per Hour) -->
    <div class="scada-card mb-3">
        <div class="scada-card-header header-navy">
            <i class="bi bi-clock-history fs-5"></i>
            <span>HISTORY DATA <small class="fw-normal text-white-50" style="text-transform: none;">(Update Per Hour)</small></span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover scada-table align-middle">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 17%;"><i class="bi bi-clock me-1 text-primary"></i> Time</th>
                        <th colspan="2" style="background-color: #eff6ff; color: #1e40af;"><i class="bi bi-droplet-fill me-1 text-primary"></i> {{ $labelRms }} <small class="fw-normal text-muted">(Flowrate)</small></th>
                        <th colspan="2" style="background-color: #ecfdf5; color: #065f46;"><i class="bi bi-droplet-fill me-1 text-success"></i> {{ $labelMhs }} <small class="fw-normal text-muted">(Flowrate)</small></th>
                        <th colspan="2" style="background-color: #faf5ff; color: #6b21a8;"><i class="bi bi-droplet-fill me-1" style="color: #7c3aed;"></i> {{ $labelHrms }} <small class="fw-normal text-muted">(Flowrate)</small></th>
                        <th rowspan="1" style="width: 11%; background-color: #fffbeb; color: #92400e;"><i class="bi bi-speedometer2 me-1 text-warning"></i> {{ $labelPh }}</th>
                    </tr>
                    <tr>
                        <th style="background-color: #f8fafc; font-size: 0.75rem;">Flowrate</th>
                        <th style="background-color: #f8fafc; font-size: 0.75rem;">Totality (m³)</th>
                        <th style="background-color: #f8fafc; font-size: 0.75rem;">Flowrate</th>
                        <th style="background-color: #f8fafc; font-size: 0.75rem;">Totality (m³)</th>
                        <th style="background-color: #f8fafc; font-size: 0.75rem;">Flowrate</th>
                        <th style="background-color: #f8fafc; font-size: 0.75rem;">Totality (m³)</th>
                        <th style="background-color: #f8fafc; font-size: 0.75rem;">Bar</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody">
                    @forelse($hourlyRows ?? [] as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row['time'] }}</td>
                            <td class="text-primary fw-bold">{{ number_format($row['rms_flow'], 1) }}</td>
                            <td>{{ number_format($row['rms_tot'], 1) }}</td>
                            <td class="text-success fw-bold">{{ number_format($row['mhs_flow'], 1) }}</td>
                            <td>{{ number_format($row['mhs_tot'], 1) }}</td>
                            <td style="color: #7c3aed;" class="fw-bold">{{ number_format($row['hrms_flow'], 1) }}</td>
                            <td>{{ number_format($row['hrms_tot'], 1) }}</td>
                            <td class="text-warning fw-bold">{{ number_format($row['pressure'] ?? $row['ph'] ?? 0.0, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-4 d-block mb-1 text-secondary"></i>
                                Belum ada riwayat data log tersimpan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 5. Bottom Brand Banner -->
    <div class="scada-bottom-banner">
        <span>Develop by: <strong>PT. Arsy Bamun Sejahtera</strong></span>
        <span class="mx-2 text-secondary">|</span>
        <a href="mailto:sales@edmmeters.com">sales@edmmeters.com</a>
    </div>

</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/chart.umd.min.js') }}"></script>
<script>
    // 1. Digital Clock & Date Update for Top Banner
    function updateScadaClock() {
        const now = new Date();
        const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        const dateEl = document.getElementById('scadaLiveDate');
        const clockEl = document.getElementById('scadaLiveClock');
        const dayEl = document.getElementById('scadaLiveDay');

        if (dateEl) {
            dateEl.textContent = `${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
        }
        if (clockEl) {
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');
            clockEl.textContent = `${h}:${m}:${s}`;
        }
        if (dayEl) {
            dayEl.textContent = days[now.getDay()];
        }
    }
    updateScadaClock();
    setInterval(updateScadaClock, 1000);

    // 2. Trend Grafik Line Chart (Last 24 Hours)
    const trendCtx = document.getElementById('scadaTrendChart').getContext('2d');
    const xLabels = @json($chartLabels);

    const scadaTrendChart = new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: xLabels,
            datasets: [
                {
                    label: @json($labelRms . ' (m³/h)'),
                    data: @json($chartRms),
                    borderColor: '#3b82f6',
                    backgroundColor: '#3b82f6',
                    borderWidth: 2,
                    pointRadius: 3.5,
                    pointHoverRadius: 5,
                    tension: 0.25,
                    yAxisID: 'y'
                },
                {
                    label: @json($labelMhs . ' (m³/h)'),
                    data: @json($chartMhs),
                    borderColor: '#10b981',
                    backgroundColor: '#10b981',
                    borderWidth: 2,
                    pointRadius: 3.5,
                    pointHoverRadius: 5,
                    tension: 0.25,
                    yAxisID: 'y'
                },
                {
                    label: @json($labelHrms . ' (m³/h)'),
                    data: @json($chartHrms),
                    borderColor: '#a855f7',
                    backgroundColor: '#a855f7',
                    borderWidth: 2,
                    pointRadius: 3.5,
                    pointHoverRadius: 5,
                    tension: 0.25,
                    yAxisID: 'y'
                },
                {
                    label: @json($labelPh . ' (Bar)'),
                    data: @json($chartPh),
                    borderColor: '#eab308',
                    backgroundColor: '#eab308',
                    borderWidth: 2,
                    pointRadius: 3.5,
                    pointHoverRadius: 5,
                    tension: 0.25,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleColor: '#e2e8f0',
                    bodyColor: '#ffffff',
                    padding: 10,
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.06)'
                    },
                    ticks: {
                        color: '#94a3b8',
                        font: { family: 'JetBrains Mono', size: 10 }
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    min: 0,
                    suggestedMax: {{ max((float)$maxRms, (float)$maxMhs, (float)$maxHrms, 10.0) }},
                    ticks: {
                        color: '#67e8f9',
                        font: { family: 'JetBrains Mono', size: 10 }
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.08)'
                    },
                    title: {
                        display: true,
                        text: 'Flowrate (m³/h)',
                        color: '#67e8f9',
                        font: { size: 10 }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    min: 0.0,
                    max: 10.0,
                    ticks: {
                        stepSize: 2.0,
                        color: '#86efac',
                        font: { family: 'JetBrains Mono', size: 10 },
                        callback: function(v) { return v.toFixed(1); }
                    },
                    grid: {
                        drawOnChartArea: false
                    },
                    title: {
                        display: true,
                        text: 'Pressure (Bar)',
                        color: '#86efac',
                        font: { size: 10 }
                    }
                }
            }
        }
    });

    // 3. Donut Chart for Flow Rate Overview
    const donutCtx = document.getElementById('scadaDonutChart').getContext('2d');
    const scadaDonutChart = new Chart(donutCtx, {
        type: 'doughnut',
        data: {
            labels: [@json($labelRms), @json($labelMhs), @json($labelHrms)],
            datasets: [{
                data: [{{ $currentRmsFlow }}, {{ $currentMhsFlow }}, {{ $currentHrmsFlow }}],
                backgroundColor: ['#3b82f6', '#10b981', '#a855f7'],
                hoverBackgroundColor: ['#2563eb', '#059669', '#9333ea'],
                borderWidth: 0,
                spacing: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ` ${context.label}: ${context.raw} m³/h`;
                        }
                    }
                }
            }
        }
    });
    window.scadaDonutChart = scadaDonutChart;

    // 4. Helper Function to update Gauges dynamically
    function setGaugeProgress(circleId, pct) {
        const circle = document.getElementById(circleId);
        if (!circle) return;
        const radius = 40;
        const circumference = 2 * Math.PI * radius; // 251.32
        const offset = circumference - (pct / 100) * circumference;
        circle.style.strokeDashoffset = offset;
    }

    // Set initial gauge values from server-rendered Blade state
    setGaugeProgress('circleProgressRMS', {{ $gaugePctRms }});
    setGaugeProgress('circleProgressMHS', {{ $gaugePctMhs }});
    setGaugeProgress('circleProgressHRMS', {{ $gaugePctHrms }});
    setGaugeProgress('circleProgressPH', {{ $gaugePctPh }});

    // 5. Function to update all Dashboard UI elements with fresh or cached telemetry
    function updateDashboardUI(data) {
        if (!data || !data.scada) return;
        const scada = data.scada;
        let scaleRms = {{ (float) $maxRms }};
        let scaleMhs = {{ (float) $maxMhs }};
        let scaleHrms = {{ (float) $maxHrms }};
        let scalePh = {{ (float) $maxPh }};

        if (data.scales) {
            if (data.scales.rms_max) scaleRms = Number(data.scales.rms_max);
            if (data.scales.mhs_max) scaleMhs = Number(data.scales.mhs_max);
            if (data.scales.hrms_max) scaleHrms = Number(data.scales.hrms_max);
            if (data.scales.pressure_max) scalePh = Number(data.scales.pressure_max);
        }

        // RMS
        const rms = Number(scada.rms?.flow_rate ?? 0);
        const rmsTotal = Number(scada.rms?.totalizer ?? 0);
        const elValRMS = document.getElementById('valFlowrateRMS');
        if (elValRMS) elValRMS.textContent = rms.toFixed(1);
        const elDetailRMS = document.getElementById('detailFlowrateRMS');
        if (elDetailRMS) elDetailRMS.textContent = rms.toFixed(1);
        const elTotRMS = document.getElementById('detailTotalityRMS');
        if (elTotRMS) elTotRMS.textContent = rmsTotal.toLocaleString('en-US', {minimumFractionDigits: 1});
        const rmsPct = Math.min(100, Math.round((rms / Math.max(0.001, scaleRms)) * 100));
        const elPctRMS = document.getElementById('pctRMS');
        if (elPctRMS) elPctRMS.textContent = rmsPct + '%';
        setGaugeProgress('circleProgressRMS', rmsPct);

        // MHS
        const mhs = Number(scada.mhs?.flow_rate ?? 0);
        const mhsTotal = Number(scada.mhs?.totalizer ?? 0);
        const elValMHS = document.getElementById('valFlowrateMHS');
        if (elValMHS) elValMHS.textContent = mhs.toFixed(1);
        const elDetailMHS = document.getElementById('detailFlowrateMHS');
        if (elDetailMHS) elDetailMHS.textContent = mhs.toFixed(1);
        const elTotMHS = document.getElementById('detailTotalityMHS');
        if (elTotMHS) elTotMHS.textContent = mhsTotal.toLocaleString('en-US', {minimumFractionDigits: 1});
        const mhsPct = Math.min(100, Math.round((mhs / Math.max(0.001, scaleMhs)) * 100));
        const elPctMHS = document.getElementById('pctMHS');
        if (elPctMHS) elPctMHS.textContent = mhsPct + '%';
        setGaugeProgress('circleProgressMHS', mhsPct);

        // H-RMS
        const hrms = Number(scada.h_rms?.flow_rate ?? 0);
        const hrmsTotal = Number(scada.h_rms?.totalizer ?? 0);
        const elValHRMS = document.getElementById('valFlowrateHRMS');
        if (elValHRMS) elValHRMS.textContent = hrms.toFixed(1);
        const elDetailHRMS = document.getElementById('detailFlowrateHRMS');
        if (elDetailHRMS) elDetailHRMS.textContent = hrms.toFixed(1);
        const elTotHRMS = document.getElementById('detailTotalityHRMS');
        if (elTotHRMS) elTotHRMS.textContent = hrmsTotal.toLocaleString('en-US', {minimumFractionDigits: 1});
        const hrmsPct = Math.min(100, Math.round((hrms / Math.max(0.001, scaleHrms)) * 100));
        const elPctHRMS = document.getElementById('pctHRMS');
        if (elPctHRMS) elPctHRMS.textContent = hrmsPct + '%';
        setGaugeProgress('circleProgressHRMS', hrmsPct);

        // Pressure
        const pressVal = Number(scada.pressure?.value ?? scada.ph?.value ?? 0);
        const elValPH = document.getElementById('valPH');
        if (elValPH) elValPH.textContent = pressVal.toFixed(2);
        const elDetailPH = document.getElementById('detailValPH');
        if (elDetailPH) elDetailPH.textContent = pressVal.toFixed(2);

        const curEl = document.getElementById('valPhStatusCurrent');
        let statusText = 'Standby';
        let curClass = 'text-secondary';
        let badgeClass = 'badge bg-secondary text-white fw-bold px-2 py-0';
        let detailClass = 'fw-bold text-secondary fs-6';
        let iconClass = 'bi bi-pause-circle-fill fs-5 text-secondary';

        if (pressVal >= 1.0 && pressVal <= 4.0) {
            statusText = 'Normal';
            curClass = 'text-success';
            badgeClass = 'badge bg-success text-white fw-bold px-2 py-0';
            detailClass = 'fw-bold text-success fs-6';
            iconClass = 'bi bi-check-circle-fill fs-5 text-success';
        } else if (pressVal > 0) {
            statusText = 'Warning';
            curClass = 'text-warning';
            badgeClass = 'badge bg-warning text-dark fw-bold px-2 py-0';
            detailClass = 'fw-bold text-warning fs-6';
            iconClass = 'bi bi-exclamation-triangle-fill fs-5 text-warning';
        }

        if (curEl) {
            curEl.textContent = pressVal.toFixed(2) + ' Bar';
            curEl.className = curClass;
        }
        const statusBadge = document.getElementById('phStatusBadge');
        if (statusBadge) {
            statusBadge.className = badgeClass;
            statusBadge.textContent = statusText;
        }
        const statusDetail = document.getElementById('phStatusDetail');
        if (statusDetail) {
            statusDetail.className = detailClass;
            statusDetail.textContent = statusText;
        }
        const statusIcon = document.getElementById('phStatusIcon');
        if (statusIcon) {
            statusIcon.className = iconClass;
        }

        const pressPct = Math.min(100, Math.max(0, Math.round((pressVal / Math.max(0.001, scalePh)) * 100)));
        setGaugeProgress('circleProgressPH', pressPct);
        const pointer = document.getElementById('phPointer');
        if (pointer) pointer.style.left = pressPct + '%';

        // Total
        const totalFlow = (rms + mhs + hrms).toFixed(1);
        const elCenterTot = document.getElementById('donutCenterTotal');
        if (elCenterTot) elCenterTot.textContent = totalFlow;
        const totalTot = (rmsTotal + mhsTotal + hrmsTotal);
        const elValTotAll = document.getElementById('valTotalizerAll');
        if (elValTotAll) elValTotAll.innerHTML = `${totalTot.toLocaleString('en-US', {minimumFractionDigits: 1})} <small class="fw-normal text-muted fs-6">m³</small>`;
        const elOvRms = document.getElementById('overviewRmsVal');
        if (elOvRms) elOvRms.innerHTML = `${rms.toFixed(1)} <small class="fw-normal text-muted">m³/h</small>`;
        const elOvMhs = document.getElementById('overviewMhsVal');
        if (elOvMhs) elOvMhs.innerHTML = `${mhs.toFixed(1)} <small class="fw-normal text-muted">m³/h</small>`;
        const elOvHrms = document.getElementById('overviewHrmsVal');
        if (elOvHrms) elOvHrms.innerHTML = `${hrms.toFixed(1)} <small class="fw-normal text-muted">m³/h</small>`;
        
        const elOvRmsPct = document.getElementById('overviewRmsPct');
        const elOvMhsPct = document.getElementById('overviewMhsPct');
        const elOvHrmsPct = document.getElementById('overviewHrmsPct');
        if (rms + mhs + hrms > 0) {
            if (elOvRmsPct) elOvRmsPct.textContent = Math.round((rms / (rms + mhs + hrms)) * 100) + '%';
            if (elOvMhsPct) elOvMhsPct.textContent = Math.round((mhs / (rms + mhs + hrms)) * 100) + '%';
            if (elOvHrmsPct) elOvHrmsPct.textContent = Math.round((hrms / (rms + mhs + hrms)) * 100) + '%';
        } else {
            if (elOvRmsPct) elOvRmsPct.textContent = '0%';
            if (elOvMhsPct) elOvMhsPct.textContent = '0%';
            if (elOvHrmsPct) elOvHrmsPct.textContent = '0%';
        }

        const donutInstance = window.scadaDonutChart;
        if (donutInstance) {
            donutInstance.data.datasets[0].data = [rms, mhs, hrms];
            donutInstance.update('none');
        }

        // Update online / offline status per device
        updateDeviceOnlineStatus('headerBadgeRMS', 'statusTextRMS', Boolean(scada.rms?.is_online));
        updateDeviceOnlineStatus('headerBadgeMHS', 'statusTextMHS', Boolean(scada.mhs?.is_online));
        updateDeviceOnlineStatus('headerBadgeHRMS', 'statusTextHRMS', Boolean(scada.h_rms?.is_online));
        updateDeviceOnlineStatus('headerBadgePH', 'statusTextPH', Boolean(scada.pressure?.is_online ?? scada.ph?.is_online));
    }

    // 6. Simpan state telemetri ke browser localStorage agar tidak ada kedip/delay saat navigasi halaman
    function saveTelemetryToLocalStorage(data) {
        try {
            if (data && data.scada) {
                localStorage.setItem('scada_last_telemetry', JSON.stringify({
                    scada: data.scada,
                    scales: data.scales,
                    timestamp: Date.now()
                }));
            }
        } catch (e) {
            // Abaikan batasan quota storage
        }
    }

    // 7. Pulihkan state terakhir dari localStorage secara instan (0 milidetik)
    function restoreFromLocalStorage() {
        try {
            const raw = localStorage.getItem('scada_last_telemetry');
            if (!raw) return;
            const parsed = JSON.parse(raw);
            if (parsed && parsed.scada) {
                updateDashboardUI(parsed);
            }
        } catch (e) {
            // Abaikan kesalahan parsing JSON
        }
    }

    // 8. Polling Telemetry live di background
    let isPolling = false;
    async function checkTelemetryLive() {
        if (isPolling || document.hidden) return;
        isPolling = true;
        try {
            const resp = await fetch('{{ route('api.telemetry.live') }}', {
                headers: { 'Accept': 'application/json' }
            });
            if (!resp.ok) return;
            const data = await resp.json();
            if (!data.success) return;

            updateDashboardUI(data);
            saveTelemetryToLocalStorage(data);
        } catch (err) {
            // Offline or network error
        } finally {
            isPolling = false;
        }
    }

    // 9. Helper Function to update online/offline badge and status text
    function updateDeviceOnlineStatus(badgeId, textId, isOnline) {
        const badgeEl = document.getElementById(badgeId);
        if (badgeEl) {
            badgeEl.textContent = isOnline ? 'online' : 'offline';
            if (isOnline) {
                badgeEl.className = 'badge rounded-pill bg-white text-success border border-success fw-bold px-2 py-0';
            } else {
                badgeEl.className = 'badge rounded-pill bg-white text-danger border border-danger fw-bold px-2 py-0';
            }
        }

        const textEl = document.getElementById(textId);
        if (textEl) {
            textEl.textContent = isOnline ? 'online' : 'offline';
            textEl.className = 'fw-bold mono-font ' + (textId === 'statusTextPH' ? 'mt-1 ' : '') + (isOnline ? 'text-success' : 'text-danger');
        }
    }

    // Segera pulihkan nilai meter terakhir jika tersimpan di browser
    restoreFromLocalStorage();

    // Jalankan polling live berkala
    checkTelemetryLive();
    setInterval(checkTelemetryLive, 5000);
</script>
@endsection
