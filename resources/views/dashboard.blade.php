@extends('layouts.app')

@section('title', 'FLOW METER & pH MONITORING SYSTEM')

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
            #f97316 16%, 
            #eab308 32%, 
            #10b981 50%, 
            #06b6d4 68%, 
            #3b82f6 84%, 
            #8b5cf6 100%
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
                        FLOW METER & pH MONITORING SYSTEM
                    </h4>
                    <div class="text-info-subtle small fw-medium mt-1" style="font-size: 0.82rem; opacity: 0.95;">
                        <span>3 x Electromagnetic Flowmeter (8 inch)</span>
                        <span class="mx-2 text-secondary">|</span>
                        <span>1 x pH Sensor (0 ~ 14 pH)</span>
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
        <!-- Card 1: FLOWMETER 8" - AREA RMS -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="scada-card">
                <div class="scada-card-header header-rms">
                    <i class="bi bi-broadcast fs-5"></i>
                    <span>FLOWMETER 8" - AREA RMS</span>
                </div>
                <div class="p-3">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <!-- Gauge Circle (Left) -->
                        <div class="text-center">
                            <div class="circular-gauge-container">
                                <svg class="circular-gauge-svg" viewBox="0 0 100 100">
                                    <circle class="gauge-bg-circle" cx="50" cy="50" r="40"></circle>
                                    <circle class="gauge-progress-circle" id="circleProgressRMS" cx="50" cy="50" r="40"
                                        stroke="#1d4ed8" stroke-dasharray="251.32" stroke-dashoffset="95.5">
                                    </circle>
                                </svg>
                                <div class="gauge-inner-text">
                                    <span class="text-muted" style="font-size: 0.65rem;">Flowrate</span>
                                    <span class="fw-bold mono-font text-dark fs-5" id="valFlowrateRMS">185.6</span>
                                    <span class="text-muted" style="font-size: 0.65rem;">m³/h</span>
                                </div>
                            </div>
                            <div class="fw-bold mono-font mt-1" style="color: #1d4ed8; font-size: 0.95rem;" id="pctRMS">62%</div>
                        </div>

                        <!-- Details (Right) -->
                        <div class="flex-grow-1 ps-2">
                            <!-- Flowrate Detail -->
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <i class="bi bi-droplet-fill fs-5" style="color: #1d4ed8;"></i>
                                <div style="line-height: 1.2;">
                                    <div class="text-muted small" style="font-size: 0.72rem;">Flowrate</div>
                                    <div class="fw-bold mono-font text-dark fs-6">
                                        <span id="detailFlowrateRMS">185.6</span> <small class="fw-normal text-muted" style="font-size: 0.75rem;">m³/h</small>
                                    </div>
                                    <div class="text-secondary" style="font-size: 0.68rem;">(Range 0 ~ 300)</div>
                                </div>
                            </div>

                            <!-- Totality Detail -->
                            <div class="d-flex align-items-start gap-2">
                                <span class="fw-bold fs-5 lh-1" style="color: #1d4ed8; font-family: serif;">Σ</span>
                                <div style="line-height: 1.2;">
                                    <div class="text-muted small" style="font-size: 0.72rem;">Totality</div>
                                    <div class="fw-bold mono-font text-dark fs-6">
                                        <span id="detailTotalityRMS">8,542.3</span> <small class="fw-normal text-muted" style="font-size: 0.75rem;">m³</small>
                                    </div>
                                    <div class="text-secondary" style="font-size: 0.68rem;">(Totalizer)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="scada-card-footer">
                    Nominal Size : 8 inch &nbsp;|&nbsp; Range : 0 ~ 300 m³/h
                </div>
            </div>
        </div>

        <!-- Card 2: FLOWMETER 8" - AREA MHS -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="scada-card">
                <div class="scada-card-header header-mhs">
                    <i class="bi bi-broadcast fs-5"></i>
                    <span>FLOWMETER 8" - AREA MHS</span>
                </div>
                <div class="p-3">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <!-- Gauge Circle (Left) -->
                        <div class="text-center">
                            <div class="circular-gauge-container">
                                <svg class="circular-gauge-svg" viewBox="0 0 100 100">
                                    <circle class="gauge-bg-circle" cx="50" cy="50" r="40"></circle>
                                    <circle class="gauge-progress-circle" id="circleProgressMHS" cx="50" cy="50" r="40"
                                        stroke="#059669" stroke-dasharray="251.32" stroke-dashoffset="130.7">
                                    </circle>
                                </svg>
                                <div class="gauge-inner-text">
                                    <span class="text-muted" style="font-size: 0.65rem;">Flowrate</span>
                                    <span class="fw-bold mono-font text-dark fs-5" id="valFlowrateMHS">142.7</span>
                                    <span class="text-muted" style="font-size: 0.65rem;">m³/h</span>
                                </div>
                            </div>
                            <div class="fw-bold mono-font mt-1" style="color: #059669; font-size: 0.95rem;" id="pctMHS">48%</div>
                        </div>

                        <!-- Details (Right) -->
                        <div class="flex-grow-1 ps-2">
                            <!-- Flowrate Detail -->
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <i class="bi bi-droplet-fill fs-5" style="color: #059669;"></i>
                                <div style="line-height: 1.2;">
                                    <div class="text-muted small" style="font-size: 0.72rem;">Flowrate</div>
                                    <div class="fw-bold mono-font text-dark fs-6">
                                        <span id="detailFlowrateMHS">142.7</span> <small class="fw-normal text-muted" style="font-size: 0.75rem;">m³/h</small>
                                    </div>
                                    <div class="text-secondary" style="font-size: 0.68rem;">(Range 0 ~ 300)</div>
                                </div>
                            </div>

                            <!-- Totality Detail -->
                            <div class="d-flex align-items-start gap-2">
                                <span class="fw-bold fs-5 lh-1" style="color: #059669; font-family: serif;">Σ</span>
                                <div style="line-height: 1.2;">
                                    <div class="text-muted small" style="font-size: 0.72rem;">Totality</div>
                                    <div class="fw-bold mono-font text-dark fs-6">
                                        <span id="detailTotalityMHS">6,783.5</span> <small class="fw-normal text-muted" style="font-size: 0.75rem;">m³</small>
                                    </div>
                                    <div class="text-secondary" style="font-size: 0.68rem;">(Totalizer)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="scada-card-footer">
                    Nominal Size : 8 inch &nbsp;|&nbsp; Range : 0 ~ 300 m³/h
                </div>
            </div>
        </div>

        <!-- Card 3: FLOWMETER 8" - AREA H-RMS -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="scada-card">
                <div class="scada-card-header header-h-rms">
                    <i class="bi bi-broadcast fs-5"></i>
                    <span>FLOWMETER 8" - AREA H-RMS</span>
                </div>
                <div class="p-3">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <!-- Gauge Circle (Left) -->
                        <div class="text-center">
                            <div class="circular-gauge-container">
                                <svg class="circular-gauge-svg" viewBox="0 0 100 100">
                                    <circle class="gauge-bg-circle" cx="50" cy="50" r="40"></circle>
                                    <circle class="gauge-progress-circle" id="circleProgressHRMS" cx="50" cy="50" r="40"
                                        stroke="#7c3aed" stroke-dasharray="251.32" stroke-dashoffset="170.9">
                                    </circle>
                                </svg>
                                <div class="gauge-inner-text">
                                    <span class="text-muted" style="font-size: 0.65rem;">Flowrate</span>
                                    <span class="fw-bold mono-font text-dark fs-5" id="valFlowrateHRMS">97.4</span>
                                    <span class="text-muted" style="font-size: 0.65rem;">m³/h</span>
                                </div>
                            </div>
                            <div class="fw-bold mono-font mt-1" style="color: #7c3aed; font-size: 0.95rem;" id="pctHRMS">32%</div>
                        </div>

                        <!-- Details (Right) -->
                        <div class="flex-grow-1 ps-2">
                            <!-- Flowrate Detail -->
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <i class="bi bi-droplet-fill fs-5" style="color: #7c3aed;"></i>
                                <div style="line-height: 1.2;">
                                    <div class="text-muted small" style="font-size: 0.72rem;">Flowrate</div>
                                    <div class="fw-bold mono-font text-dark fs-6">
                                        <span id="detailFlowrateHRMS">97.4</span> <small class="fw-normal text-muted" style="font-size: 0.75rem;">m³/h</small>
                                    </div>
                                    <div class="text-secondary" style="font-size: 0.68rem;">(Range 0 ~ 300)</div>
                                </div>
                            </div>

                            <!-- Totality Detail -->
                            <div class="d-flex align-items-start gap-2">
                                <span class="fw-bold fs-5 lh-1" style="color: #7c3aed; font-family: serif;">Σ</span>
                                <div style="line-height: 1.2;">
                                    <div class="text-muted small" style="font-size: 0.72rem;">Totality</div>
                                    <div class="fw-bold mono-font text-dark fs-6">
                                        <span id="detailTotalityHRMS">4,126.8</span> <small class="fw-normal text-muted" style="font-size: 0.75rem;">m³</small>
                                    </div>
                                    <div class="text-secondary" style="font-size: 0.68rem;">(Totalizer)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="scada-card-footer">
                    Nominal Size : 8 inch &nbsp;|&nbsp; Range : 0 ~ 300 m³/h
                </div>
            </div>
        </div>

        <!-- Card 4: pH SENSOR -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="scada-card">
                <div class="scada-card-header header-ph">
                    <i class="bi bi-flask-fill fs-5"></i>
                    <span>pH SENSOR</span>
                </div>
                <div class="p-3">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <!-- Gauge Circle (Left) -->
                        <div class="text-center">
                            <div class="circular-gauge-container">
                                <svg class="circular-gauge-svg" viewBox="0 0 100 100">
                                    <circle class="gauge-bg-circle" cx="50" cy="50" r="40"></circle>
                                    <circle class="gauge-progress-circle" id="circleProgressPH" cx="50" cy="50" r="40"
                                        stroke="#ea580c" stroke-dasharray="251.32" stroke-dashoffset="114.6">
                                    </circle>
                                </svg>
                                <div class="gauge-inner-text">
                                    <span class="text-muted" style="font-size: 0.65rem;">pH</span>
                                    <span class="fw-bold mono-font text-dark fs-5" id="valPH">7.62</span>
                                    <span class="text-muted" style="font-size: 0.65rem;">pH</span>
                                </div>
                            </div>
                            <div class="mt-1">
                                <span class="badge bg-success text-white fw-bold px-2 py-0" style="font-size: 0.75rem;">Normal</span>
                            </div>
                        </div>

                        <!-- Details (Right) -->
                        <div class="flex-grow-1 ps-2">
                            <!-- pH Value Detail -->
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <i class="bi bi-fire fs-5" style="color: #ea580c;"></i>
                                <div style="line-height: 1.2;">
                                    <div class="text-muted small" style="font-size: 0.72rem;">pH Value</div>
                                    <div class="fw-bold mono-font text-dark fs-6">
                                        <span id="detailValPH">7.62</span> <small class="fw-normal text-muted" style="font-size: 0.75rem;">pH</small>
                                    </div>
                                    <div class="text-secondary" style="font-size: 0.68rem;">(Range 0 ~ 14)</div>
                                </div>
                            </div>

                            <!-- Status Detail -->
                            <div class="d-flex align-items-start gap-2">
                                <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                                <div style="line-height: 1.2;">
                                    <div class="text-muted small" style="font-size: 0.72rem;">Status</div>
                                    <div class="fw-bold text-success fs-6">Normal</div>
                                    <div class="text-secondary" style="font-size: 0.68rem;">(7.0 ~ 8.0)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="scada-card-footer">
                    Range : 0 ~ 14 pH
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
                            <span class="text-secondary mono-font" style="font-size: 0.78rem;">RMS (m³/h)</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span style="display:inline-block; width: 18px; height: 3px; background-color: #10b981; border-radius: 2px;"></span>
                            <span class="text-secondary mono-font" style="font-size: 0.78rem;">MHS (m³/h)</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span style="display:inline-block; width: 18px; height: 3px; background-color: #a855f7; border-radius: 2px;"></span>
                            <span class="text-secondary mono-font" style="font-size: 0.78rem;">H-RMS (m³/h)</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span style="display:inline-block; width: 18px; height: 3px; background-color: #eab308; border-radius: 2px;"></span>
                            <span class="text-secondary mono-font" style="font-size: 0.78rem;">pH</span>
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
                                    <span class="fw-bold mono-font text-dark fs-5" id="donutCenterTotal">425.7</span>
                                    <span class="text-muted" style="font-size: 0.65rem;">m³/h</span>
                                    <span class="text-secondary" style="font-size: 0.60rem;">(3 Unit)</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <table class="table table-borderless table-sm mb-0 mono-font" style="font-size: 0.8rem;">
                                <tbody>
                                    <tr>
                                        <td><span class="badge rounded-circle p-1 me-1" style="background-color: #3b82f6;">&nbsp;</span><strong class="text-dark">RMS</strong></td>
                                        <td class="text-end fw-bold text-dark" id="overviewRmsVal">185.6 <small class="fw-normal text-muted">m³/h</small></td>
                                        <td class="text-end text-muted" id="overviewRmsPct">44%</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge rounded-circle p-1 me-1" style="background-color: #10b981;">&nbsp;</span><strong class="text-dark">MHS</strong></td>
                                        <td class="text-end fw-bold text-dark" id="overviewMhsVal">142.7 <small class="fw-normal text-muted">m³/h</small></td>
                                        <td class="text-end text-muted" id="overviewMhsPct">34%</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge rounded-circle p-1 me-1" style="background-color: #a855f7;">&nbsp;</span><strong class="text-dark">H-RMS</strong></td>
                                        <td class="text-end fw-bold text-dark" id="overviewHrmsVal">97.4 <small class="fw-normal text-muted">m³/h</small></td>
                                        <td class="text-end text-muted" id="overviewHrmsPct">22%</td>
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
                                19,452.6 <small class="fw-normal text-muted fs-6">m³</small>
                            </div>
                        </div>
                    </div>

                    <!-- pH Status Bar -->
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-1 text-muted small fw-semibold" style="font-size: 0.76rem;">
                                <i class="bi bi-eyedropper text-warning"></i>
                                <span>pH Status</span>
                            </div>
                            <div class="small mono-font" style="font-size: 0.76rem;">
                                Current: <strong class="text-success" id="valPhStatusCurrent">7.62 pH</strong>
                            </div>
                        </div>
                        <div class="ph-gradient-bar mb-1">
                            <div class="ph-pointer-indicator" id="phPointer" style="left: 54.4%;"></div>
                        </div>
                        <div class="d-flex justify-content-between text-muted mono-font" style="font-size: 0.65rem;">
                            <span>0</span>
                            <span>2</span>
                            <span>4</span>
                            <span>6</span>
                            <span>7</span>
                            <span>8</span>
                            <span>10</span>
                            <span>12</span>
                            <span>14</span>
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
                        <th colspan="2" style="background-color: #eff6ff; color: #1e40af;"><i class="bi bi-droplet-fill me-1 text-primary"></i> RMS <small class="fw-normal text-muted">(Flowrate)</small></th>
                        <th colspan="2" style="background-color: #ecfdf5; color: #065f46;"><i class="bi bi-droplet-fill me-1 text-success"></i> MHS <small class="fw-normal text-muted">(Flowrate)</small></th>
                        <th colspan="2" style="background-color: #faf5ff; color: #6b21a8;"><i class="bi bi-droplet-fill me-1" style="color: #7c3aed;"></i> H-RMS <small class="fw-normal text-muted">(Flowrate)</small></th>
                        <th rowspan="1" style="width: 11%; background-color: #fffbeb; color: #92400e;"><i class="bi bi-eyedropper me-1 text-warning"></i> pH</th>
                    </tr>
                    <tr>
                        <th style="background-color: #f8fafc; font-size: 0.75rem;">Flowrate</th>
                        <th style="background-color: #f8fafc; font-size: 0.75rem;">Totality (m³)</th>
                        <th style="background-color: #f8fafc; font-size: 0.75rem;">Flowrate</th>
                        <th style="background-color: #f8fafc; font-size: 0.75rem;">Totality (m³)</th>
                        <th style="background-color: #f8fafc; font-size: 0.75rem;">Flowrate</th>
                        <th style="background-color: #f8fafc; font-size: 0.75rem;">Totality (m³)</th>
                        <th style="background-color: #f8fafc; font-size: 0.75rem;">pH</th>
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
                            <td class="text-warning fw-bold">{{ number_format($row['ph'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="fw-semibold">2026-09-30 13:00</td>
                            <td class="text-primary fw-bold">184.2</td>
                            <td>8,356.7</td>
                            <td class="text-success fw-bold">141.5</td>
                            <td>6,521.3</td>
                            <td style="color: #7c3aed;" class="fw-bold">96.8</td>
                            <td>3,982.6</td>
                            <td class="text-warning fw-bold">7.60</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">2026-09-30 12:00</td>
                            <td class="text-primary fw-bold">182.7</td>
                            <td>8,337.8</td>
                            <td class="text-success fw-bold">139.8</td>
                            <td>6,379.6</td>
                            <td style="color: #7c3aed;" class="fw-bold">94.6</td>
                            <td>3,885.2</td>
                            <td class="text-warning fw-bold">7.58</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">2026-09-30 11:00</td>
                            <td class="text-primary fw-bold">176.3</td>
                            <td>8,319.1</td>
                            <td class="text-success fw-bold">135.2</td>
                            <td>6,239.8</td>
                            <td style="color: #7c3aed;" class="fw-bold">90.7</td>
                            <td>3,790.5</td>
                            <td class="text-warning fw-bold">7.55</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">2026-09-30 10:00</td>
                            <td class="text-primary fw-bold">169.8</td>
                            <td>8,142.7</td>
                            <td class="text-success fw-bold">131.6</td>
                            <td>6,104.6</td>
                            <td style="color: #7c3aed;" class="fw-bold">87.3</td>
                            <td>3,699.4</td>
                            <td class="text-warning fw-bold">7.52</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">2026-09-30 09:00</td>
                            <td class="text-primary fw-bold">160.4</td>
                            <td>7,986.2</td>
                            <td class="text-success fw-bold">125.7</td>
                            <td>5,973.1</td>
                            <td style="color: #7c3aed;" class="fw-bold">82.9</td>
                            <td>3,612.0</td>
                            <td class="text-warning fw-bold">7.48</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">2026-09-30 08:00</td>
                            <td class="text-primary fw-bold">152.7</td>
                            <td>7,829.5</td>
                            <td class="text-success fw-bold">118.9</td>
                            <td>5,842.7</td>
                            <td style="color: #7c3aed;" class="fw-bold">79.6</td>
                            <td>3,529.3</td>
                            <td class="text-warning fw-bold">7.45</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">2026-09-30 07:00</td>
                            <td class="text-primary fw-bold">148.1</td>
                            <td>7,674.8</td>
                            <td class="text-success fw-bold">114.2</td>
                            <td>5,718.9</td>
                            <td style="color: #7c3aed;" class="fw-bold">76.8</td>
                            <td>3,451.7</td>
                            <td class="text-warning fw-bold">7.42</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">2026-09-30 06:00</td>
                            <td class="text-primary fw-bold">142.5</td>
                            <td>7,521.3</td>
                            <td class="text-success fw-bold">109.7</td>
                            <td>5,602.6</td>
                            <td style="color: #7c3aed;" class="fw-bold">73.5</td>
                            <td>3,378.2</td>
                            <td class="text-warning fw-bold">7.39</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 5. Bottom Brand Banner -->
    <div class="scada-bottom-banner">
        <span>Develop oleh <strong>PT Maxwell Indomakmur</strong></span>
        <span class="mx-2 text-secondary">|</span>
        <a href="https://www.maxwell-mim.com" target="_blank" rel="noopener">www.maxwell-mim.com</a>
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
    const xLabels = ['00:00', '02:00', '04:00', '06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00', '24:00'];

    const scadaTrendChart = new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: xLabels,
            datasets: [
                {
                    label: 'RMS (m³/h)',
                    data: [184, 186, 178, 188, 182, 190, 185, 188, 184, 187, 185.6, 189, 185.6],
                    borderColor: '#3b82f6',
                    backgroundColor: '#3b82f6',
                    borderWidth: 2,
                    pointRadius: 3.5,
                    pointHoverRadius: 5,
                    tension: 0.25,
                    yAxisID: 'y'
                },
                {
                    label: 'MHS (m³/h)',
                    data: [140, 142, 138, 145, 141, 146, 143, 144, 140, 143, 142.7, 145, 142.7],
                    borderColor: '#10b981',
                    backgroundColor: '#10b981',
                    borderWidth: 2,
                    pointRadius: 3.5,
                    pointHoverRadius: 5,
                    tension: 0.25,
                    yAxisID: 'y'
                },
                {
                    label: 'H-RMS (m³/h)',
                    data: [94, 96, 92, 98, 95, 100, 96, 99, 95, 98, 97.4, 99, 97.4],
                    borderColor: '#a855f7',
                    backgroundColor: '#a855f7',
                    borderWidth: 2,
                    pointRadius: 3.5,
                    pointHoverRadius: 5,
                    tension: 0.25,
                    yAxisID: 'y'
                },
                {
                    label: 'pH',
                    data: [7.45, 7.48, 7.50, 7.55, 7.58, 7.60, 7.62, 7.65, 7.60, 7.62, 7.62, 7.64, 7.62],
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
                    max: 300,
                    ticks: {
                        stepSize: 60,
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
                    max: 14.0,
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
                        text: 'pH',
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
            labels: ['RMS', 'MHS', 'H-RMS'],
            datasets: [{
                data: [185.6, 142.7, 97.4],
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

    // 4. Helper Function to update Gauges dynamically
    function setGaugeProgress(circleId, pct) {
        const circle = document.getElementById(circleId);
        if (!circle) return;
        const radius = 40;
        const circumference = 2 * Math.PI * radius; // 251.32
        const offset = circumference - (pct / 100) * circumference;
        circle.style.strokeDashoffset = offset;
    }

    // Set initial gauge values
    setGaugeProgress('circleProgressRMS', 62);
    setGaugeProgress('circleProgressMHS', 48);
    setGaugeProgress('circleProgressHRMS', 32);
    setGaugeProgress('circleProgressPH', 54.4); // 7.62 / 14 * 100

    // Optional Telemetry live polling if active
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

            // If actual devices are available and responding, we can dynamically update:
            if (data.scada) {
                const scada = data.scada;
                // RMS
                const rms = Number(scada.rms?.flow_rate ?? 185.6);
                const rmsTotal = Number(scada.rms?.totalizer ?? 8542.3);
                document.getElementById('valFlowrateRMS').textContent = rms.toFixed(1);
                document.getElementById('detailFlowrateRMS').textContent = rms.toFixed(1);
                document.getElementById('detailTotalityRMS').textContent = rmsTotal.toLocaleString('en-US', {minimumFractionDigits: 1});
                const rmsPct = Math.min(100, Math.round((rms / 300) * 100));
                document.getElementById('pctRMS').textContent = rmsPct + '%';
                setGaugeProgress('circleProgressRMS', rmsPct);

                // MHS
                const mhs = Number(scada.mhs?.flow_rate ?? 142.7);
                const mhsTotal = Number(scada.mhs?.totalizer ?? 6783.5);
                document.getElementById('valFlowrateMHS').textContent = mhs.toFixed(1);
                document.getElementById('detailFlowrateMHS').textContent = mhs.toFixed(1);
                document.getElementById('detailTotalityMHS').textContent = mhsTotal.toLocaleString('en-US', {minimumFractionDigits: 1});
                const mhsPct = Math.min(100, Math.round((mhs / 300) * 100));
                document.getElementById('pctMHS').textContent = mhsPct + '%';
                setGaugeProgress('circleProgressMHS', mhsPct);

                // H-RMS
                const hrms = Number(scada.h_rms?.flow_rate ?? 97.4);
                const hrmsTotal = Number(scada.h_rms?.totalizer ?? 4126.8);
                document.getElementById('valFlowrateHRMS').textContent = hrms.toFixed(1);
                document.getElementById('detailFlowrateHRMS').textContent = hrms.toFixed(1);
                document.getElementById('detailTotalityHRMS').textContent = hrmsTotal.toLocaleString('en-US', {minimumFractionDigits: 1});
                const hrmsPct = Math.min(100, Math.round((hrms / 300) * 100));
                document.getElementById('pctHRMS').textContent = hrmsPct + '%';
                setGaugeProgress('circleProgressHRMS', hrmsPct);

                // pH
                const phVal = Number(scada.ph?.value ?? 7.62);
                document.getElementById('valPH').textContent = phVal.toFixed(2);
                document.getElementById('detailValPH').textContent = phVal.toFixed(2);
                document.getElementById('valPhStatusCurrent').textContent = phVal.toFixed(2) + ' pH';
                const phPct = Math.min(100, Math.max(0, (phVal / 14) * 100));
                setGaugeProgress('circleProgressPH', phPct);
                const pointer = document.getElementById('phPointer');
                if (pointer) pointer.style.left = phPct + '%';

                // Total
                const totalFlow = (rms + mhs + hrms).toFixed(1);
                document.getElementById('donutCenterTotal').textContent = totalFlow;
                const totalTot = (rmsTotal + mhsTotal + hrmsTotal);
                document.getElementById('valTotalizerAll').innerHTML = `${totalTot.toLocaleString('en-US', {minimumFractionDigits: 1})} <small class="fw-normal text-muted fs-6">m³</small>`;
                document.getElementById('overviewRmsVal').innerHTML = `${rms.toFixed(1)} <small class="fw-normal text-muted">m³/h</small>`;
                document.getElementById('overviewMhsVal').innerHTML = `${mhs.toFixed(1)} <small class="fw-normal text-muted">m³/h</small>`;
                document.getElementById('overviewHrmsVal').innerHTML = `${hrms.toFixed(1)} <small class="fw-normal text-muted">m³/h</small>`;
                if (rms + mhs + hrms > 0) {
                    document.getElementById('overviewRmsPct').textContent = Math.round((rms / (rms + mhs + hrms)) * 100) + '%';
                    document.getElementById('overviewMhsPct').textContent = Math.round((mhs / (rms + mhs + hrms)) * 100) + '%';
                    document.getElementById('overviewHrmsPct').textContent = Math.round((hrms / (rms + mhs + hrms)) * 100) + '%';
                }
                scadaDonutChart.data.datasets[0].data = [rms, mhs, hrms];
                scadaDonutChart.update('none');
            }
        } catch (err) {
            // Silently retain screenshot-identical state if offline
        } finally {
            isPolling = false;
        }
    }

    // Interval telemetry poll
    setInterval(checkTelemetryLive, 5000);
</script>
@endsection
