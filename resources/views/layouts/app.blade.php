<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'IoT Meter Monitoring') }}</title>

    <!-- Local Bootstrap 5 & Icons (Offline Fast Load) -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap"
        rel="stylesheet" media="print" onload="this.media='all'">

    <style>
        :root {
            --sidebar-width: 270px;
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --bg-body: #f4f6f9;
            --sidebar-bg: #0b1324;
            --sidebar-hover: #152238;
            --card-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.05);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 7px;
            height: 7px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: #334155;
            min-height: 100vh;
        }

        .mono-font {
            font-family: 'JetBrains Mono', monospace;
        }

        /* Sidebar Styling */
        #sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: linear-gradient(180deg, #0b1324 0%, #0d172c 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.06);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }

        #sidebar .brand-wrapper {
            padding: 1.25rem 1.4rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(15, 23, 42, 0.4);
        }

        .sidebar-brand-box {
            background: #ffffff;
            border-radius: 9px;
            padding: 5px 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            flex-shrink: 0;
        }

        .sidebar-brand-logo {
            height: 26px;
            width: auto;
            max-width: 90px;
            object-fit: contain;
            display: block;
        }


        #sidebar .nav-link {
            color: #94a3b8;
            padding: 0.72rem 1.15rem;
            margin: 0.2rem 0.85rem;
            border-radius: 10px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.85rem;
            transition: all 0.2s ease;
            position: relative;
        }

        #sidebar .nav-link i {
            font-size: 1.15rem;
            transition: transform 0.2s ease;
        }

        #sidebar .nav-link:hover {
            color: #ffffff;
            background-color: var(--sidebar-hover);
        }

        #sidebar .nav-link:hover i {
            transform: scale(1.1);
        }

        #sidebar .nav-link.active {
            color: #ffffff;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
            font-weight: 600;
        }

        #sidebar .sidebar-heading {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            color: #64748b;
            padding: 1rem 1.4rem 0.35rem;
            font-weight: 700;
        }

        /* Sidebar User Profile Footer */
        .sidebar-footer {
            margin-top: auto;
            padding: 1rem 1.25rem;
            border-top: 1px solid rgba(255, 255, 255, 0.07);
            background: rgba(11, 19, 36, 0.7);
        }

        .avatar-circle {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            color: #ffffff;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
        }

        /* Main Content */
        #main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }

        /* Top Navbar */
        .top-navbar {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 1.75rem;
            position: sticky;
            top: 0;
            z-index: 900;
            box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.03);
        }

        /* Card custom */
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: var(--card-shadow);
            background: #ffffff;
        }

        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            font-weight: 600;
            padding: 1rem 1.25rem;
        }

        /* Telemetry badge / status indicator */
        .status-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-dot.online {
            background-color: #10b981;
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.7);
        }

        .status-dot.offline {
            background-color: #ef4444;
            box-shadow: 0 0 8px rgba(239, 68, 68, 0.7);
        }

        /* Radar pulse dot */
        .pulse-indicator {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 10px;
            height: 10px;
        }

        .pulse-indicator::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid #10b981;
            animation: radarPulse 2s cubic-bezier(0, 0, 0.2, 1) infinite;
            opacity: 0.8;
        }

        .pulse-indicator.offline::after {
            border-color: #ef4444;
            animation: none;
            opacity: 0;
        }

        @keyframes radarPulse {
            0% {
                transform: scale(0.6);
                opacity: 1;
            }

            100% {
                transform: scale(2.2);
                opacity: 0;
            }
        }

        @media (max-width: 991.98px) {
            #sidebar {
                margin-left: calc(-1 * var(--sidebar-width));
            }

            #sidebar.active {
                margin-left: 0;
            }

            #main-content {
                margin-left: 0;
            }
        }
    </style>
    @yield('styles')
</head>

<body class="@hasSection('scada-fullscreen') scada-fullscreen @endif">
    <!-- Sidebar -->
    <nav id="sidebar">
        <!-- Brand Header -->
        <div class="brand-wrapper d-flex align-items-center justify-content-center position-relative">
            <a href="{{ url('/dashboard') }}" class="d-inline-flex align-items-center text-decoration-none">
                <div class="sidebar-brand-box">
                    <img src="{{ asset('assets/1.jpg') }}" alt="Logo EDM" class="sidebar-brand-logo">
                </div>
            </a>
            <button class="btn btn-sm btn-link text-secondary d-lg-none position-absolute end-0 top-50 translate-middle-y me-3" id="sidebarClose">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <!-- Navigation Menu -->
        <div class="py-3 flex-grow-1 overflow-y-auto">
            <div class="sidebar-heading">Monitoring Utama</div>
            <ul class="nav flex-column mb-2">
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('dashboard*') || request()->is('/') ? 'active' : '' }}"
                        href="{{ url('/dashboard') }}">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard SCADA</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('history*') ? 'active' : '' }}" href="{{ url('/history') }}">
                        <i class="bi bi-clock-history"></i>
                        <span>Data History & Log</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-heading">Konfigurasi Modbus</div>
            <ul class="nav flex-column mb-2">
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('devices*') ? 'active' : '' }}" href="{{ url('/devices') }}">
                        <i class="bi bi-hdd-network"></i>
                        <span>Device Meter (5 Unit)</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('registers*') ? 'active' : '' }}"
                        href="{{ url('/registers') }}">
                        <i class="bi bi-list-columns-reverse"></i>
                        <span>Register Mapping</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-heading">Sistem & Profil</div>
            <ul class="nav flex-column mb-2">
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('settings') ? 'active' : '' }}" href="{{ url('/settings') }}">
                        <i class="bi bi-sliders"></i>
                        <span>Pengaturan Sistem</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('settings.scales') ? 'active' : '' }}" href="{{ route('settings.scales') }}">
                        <i class="bi bi-speedometer2 text-warning"></i>
                        <span>Skala Maksimal Gauge</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Sidebar Footer / User Profile Card -->
        @auth
            <div class="sidebar-footer">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-circle">
                            {{ strtoupper(substr(Auth::user()->name ?? 'AD', 0, 2)) }}
                        </div>
                        <div style="line-height: 1.2;">
                            <div class="text-white small fw-bold text-truncate" style="max-width: 120px;">
                                {{ Auth::user()->name ?? 'Operator' }}
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-1 py-0"
                                style="font-size: 0.65rem;">
                                Admin SCADA
                            </span>
                        </div>
                    </div>
                    <form action="{{ url('/logout') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger border-0 p-1" title="Keluar Akun">
                            <i class="bi bi-box-arrow-right fs-5"></i>
                        </button>
                    </form>
                </div>
            </div>
        @endauth
    </nav>
    @if(View::hasSection('scada-fullscreen'))
        <div class="scada-sidebar-backdrop" id="scadaSidebarBackdrop"></div>
    @endif

    <!-- Main Content Wrapper -->
    <div id="main-content">
        <!-- Top Navbar -->
        @unless(View::hasSection('scada-fullscreen'))
        <header class="top-navbar d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle">
                    <i class="bi bi-list fs-5"></i>
                </button>

                <!-- Gateway Status Pill -->
                <div class="d-none d-sm-flex align-items-center gap-2 bg-light border px-3 py-1 rounded-pill" id="topNavGatewayPill">
                    <div class="pulse-indicator {{ ($isActiveGatewayOnline ?? false) ? '' : 'offline' }}" id="topNavGatewayPulse">
                        <span class="status-dot {{ ($isActiveGatewayOnline ?? false) ? 'online' : 'offline' }}" id="topNavGatewayDot"></span>
                    </div>
                    <span class="small fw-semibold text-dark">Gateway RS485-TCP: <span
                            class="mono-font text-primary" id="topNavGatewayAddress">{{ $activeGatewayIp ?? '192.168.1.118' }}:{{ $activeGatewayPort ?? 502 }}</span></span>
                    <span
                        class="badge {{ ($isActiveGatewayOnline ?? false) ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' }} rounded-pill py-0 px-2"
                        style="font-size: 0.68rem;" id="topNavGatewayBadge">{{ ($isActiveGatewayOnline ?? false) ? 'ONLINE' : 'OFFLINE' }}</span>
                </div>
            </div>

            <!-- Jam Digital Real-time & User Nav -->
            <div class="d-flex align-items-center gap-3">
                <!-- Live Digital Clock (WIB) -->
                <div
                    class="d-none d-md-flex align-items-center gap-2 bg-light border px-3 py-1 rounded-pill text-muted small mono-font">
                    <i class="bi bi-clock text-primary"></i>
                    <span id="topNavLiveClock">--:--:-- WIB</span>
                </div>

                @auth
                    <div class="dropdown">
                        <button
                            class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-2 rounded-pill px-3 py-1 shadow-sm"
                            type="button" data-bs-toggle="dropdown">
                            <span class="status-dot online"></span>
                            <span class="fw-semibold text-dark small">{{ Auth::user()->name ?? 'Operator' }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2"
                            style="min-width: 200px; border-radius: 12px;">
                            <li class="px-3 py-2 border-bottom">
                                <small class="text-muted d-block">Masuk sebagai:</small>
                                <span class="fw-bold text-dark">{{ Auth::user()->username ?? 'admin' }}</span>
                            </li>
                            <li><a class="dropdown-item py-2" href="{{ url('/settings/profile') }}"><i
                                        class="bi bi-person-gear text-primary me-2"></i> Profil & Password</a></li>
                            <li><a class="dropdown-item py-2" href="{{ url('/settings') }}"><i
                                        class="bi bi-sliders text-secondary me-2"></i> Pengaturan Sistem</a></li>
                            <li>
                                <hr class="dropdown-divider my-1">
                            </li>
                            <li>
                                <form action="{{ url('/logout') }}" method="POST">
                                    @csrf
                                    <button class="dropdown-item text-danger py-2" type="submit">
                                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a href="{{ url('/login') }}" class="btn btn-sm btn-primary rounded-pill px-3">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Login
                    </a>
                @endauth
            </div>
        </header>
        @endunless

        <!-- Page Body Content -->
        <main class="flex-grow-1 {{ View::hasSection('scada-fullscreen') ? 'p-1' : 'p-3 p-md-4' }}">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 border-0 shadow-sm"
                    role="alert" style="border-radius: 12px; background: #ecfdf5; color: #065f46;">
                    <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                    <div class="fw-medium">{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 border-0 shadow-sm"
                    role="alert" style="border-radius: 12px; background: #fef2f2; color: #991b1b;">
                    <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
                    <div class="fw-medium">{{ session('error') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(isset($errors) && $errors->any())
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert"
                    style="border-radius: 12px; background: #fef2f2; color: #991b1b;">
                    <div class="fw-bold mb-1 d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-octagon-fill"></i> Terjadi Kesalahan Validasi:
                    </div>
                    <ul class="mb-0 ps-3 small">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </main>

        <!-- Footer -->
        @unless(View::hasSection('scada-fullscreen'))
        <footer
            class="bg-white border-top py-3 px-4 text-center text-muted small d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            <span>&copy; {{ date('Y') }} <strong>IoT Meter Monitoring</strong> &bull; Industrial Modbus TCP
                Engine</span>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-secondary border">v1.2.0 SCADA</span>
                <span class="badge bg-success-subtle text-success border border-success-subtle">Localhost Ready</span>
            </div>
        </footer>
        @endunless
    </div>

    <!-- Scripts -->
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        // Sidebar toggle for mobile
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarClose = document.getElementById('sidebarClose');
        const scadaSidebarToggle = document.getElementById('scadaSidebarToggle');
        const scadaSidebarBackdrop = document.getElementById('scadaSidebarBackdrop');

        function setScadaSidebarOpen(isOpen) {
            if (!sidebar || !scadaSidebarBackdrop) return;
            sidebar.classList.toggle('active', isOpen);
            scadaSidebarBackdrop.classList.toggle('active', isOpen);
            document.body.classList.toggle('scada-sidebar-open', isOpen);
            scadaSidebarToggle?.setAttribute('aria-expanded', String(isOpen));
            scadaSidebarToggle?.setAttribute('aria-label', isOpen ? 'Tutup menu' : 'Buka menu');
            const scadaSidebarToggleLabel = document.getElementById('scadaSidebarToggleLabel');
            if (scadaSidebarToggleLabel) {
                scadaSidebarToggleLabel.textContent = isOpen ? 'TUTUP MENU' : 'BUKA MENU';
            }
        }

        scadaSidebarToggle?.addEventListener('click', () => {
            setScadaSidebarOpen(!sidebar?.classList.contains('active'));
        });
        scadaSidebarBackdrop?.addEventListener('click', () => setScadaSidebarOpen(false));
        sidebarClose?.addEventListener('click', () => setScadaSidebarOpen(false));

        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
            });
        }

        // Live Digital Clock in Top Navbar
        function updateTopNavClock() {
            const clockEl = document.getElementById('topNavLiveClock');
            if (!clockEl) return;
            const now = new Date();
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const dayName = days[now.getDay()];
            const date = now.getDate();
            const monthName = months[now.getMonth()];
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            clockEl.textContent = `${dayName}, ${date} ${monthName} • ${hours}:${minutes}:${seconds} WIB`;
        }
        updateTopNavClock();
        setInterval(updateTopNavClock, 1000);

        // Polling status gateway untuk top navbar
        async function checkTopNavGatewayStatus() {
            try {
                const res = await fetch('{{ route('api.gateway.status') }}', {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) return;
                const data = await res.json();
                if (data.success && data.gateway) {
                    const gw = data.gateway;
                    const addrEl = document.getElementById('topNavGatewayAddress');
                    const badgeEl = document.getElementById('topNavGatewayBadge');
                    const dotEl = document.getElementById('topNavGatewayDot');
                    const pulseEl = document.getElementById('topNavGatewayPulse');

                    if (addrEl) addrEl.textContent = `${gw.ip_address}:${gw.port}`;
                    if (dotEl) dotEl.className = 'status-dot ' + (gw.is_online ? 'online' : 'offline');
                    if (pulseEl) pulseEl.className = 'pulse-indicator ' + (gw.is_online ? '' : 'offline');
                    if (badgeEl) {
                        badgeEl.textContent = gw.status_label;
                        badgeEl.className = 'badge rounded-pill py-0 px-2 ' + 
                            (gw.is_online ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle');
                    }
                }
            } catch (e) {
                // Ignore network error
            }
        }
        setInterval(checkTopNavGatewayStatus, 10000);
    </script>
    @yield('scripts')
</body>

</html>
