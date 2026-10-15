<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - {{ config('app.name', 'IoT Meter Monitoring') }}</title>

    <!-- Local Bootstrap 5 & Icons -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap"
        rel="stylesheet" media="print" onload="this.media='all'">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #090d16 0%, #0f172a 50%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        /* Subtle Background Ambient Orbs */
        body::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.18) 0%, rgba(37, 99, 235, 0) 70%);
            top: -100px;
            right: -100px;
            border-radius: 50%;
            pointer-events: none;
        }

        body::after {
            content: '';
            position: absolute;
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(6, 182, 212, 0.15) 0%, rgba(6, 182, 212, 0) 70%);
            bottom: -80px;
            left: -80px;
            border-radius: 50%;
            pointer-events: none;
        }

        .mono-font {
            font-family: 'JetBrains Mono', monospace;
        }

        .login-card {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.45);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.15);
            position: relative;
            z-index: 10;
        }

        .login-header {
            background: linear-gradient(180deg, #0b1324 0%, #131d36 100%);
            padding: 2.25rem 2rem 1.75rem;
            text-align: center;
            color: #ffffff;
            border-bottom: 3.5px solid #2563eb;
            position: relative;
        }

        .brand-logo-container {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            padding: 8px 18px;
            border-radius: 14px;
            margin-bottom: 0.9rem;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .brand-logo-img {
            height: 48px;
            width: auto;
            max-width: 200px;
            object-fit: contain;
            display: block;
        }

        .form-control {
            border-radius: 10px;
            padding: 0.68rem 0.9rem;
            font-size: 0.92rem;
            border: 1px solid #cbd5e1;
        }

        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.18);
        }

        .input-group-text {
            border-radius: 10px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: none;
            padding: 0.75rem 1rem;
            font-weight: 600;
            border-radius: 10px;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.45);
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="login-header">
            <div class="brand-logo-container">
                <img src="{{ asset('assets/1.jpg') }}" alt="Logo EDM" class="brand-logo-img">
            </div>
        </div>

        <div class="p-4 p-sm-4">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show py-2 px-3 small d-flex align-items-center gap-2 mb-3 border-0"
                    role="alert" style="border-radius: 10px; background: #ecfdf5; color: #065f46;">
                    <i class="bi bi-check-circle-fill fs-5"></i>
                    <div>{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(isset($errors) && $errors->any())
                <div class="alert alert-danger alert-dismissible fade show py-2 px-3 small mb-3 border-0" role="alert"
                    style="border-radius: 10px; background: #fef2f2; color: #991b1b;">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="login" class="form-label fw-bold small text-secondary">Username / Email</label>
                    <div class="input-group">
                        <span class="input-group-text text-muted">
                            <i class="bi bi-person"></i>
                        </span>
                        <input type="text" class="form-control @error('login') is-invalid @enderror" id="login"
                            name="login" value="{{ old('login') }}" placeholder="Masukkan username atau email" required
                            autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-bold small text-secondary">Password</label>
                    <div class="input-group">
                        <span class="input-group-text text-muted">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                            id="password" name="password" placeholder="Masukkan password" required>
                        <button class="btn btn-outline-secondary bg-light text-muted" type="button" id="togglePassword">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4 d-flex justify-content-between align-items-center">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label small text-secondary" for="remember">Ingat Saya</label>
                    </div>
                    <span class="badge bg-light text-muted border mono-font" style="font-size: 0.72rem;">Port 502
                        Ready</span>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Dashboard
                </button>
            </form>
        </div>

        <div class="py-2 px-3 text-center bg-light border-top text-muted small" style="font-size: 0.75rem;">
            &copy; {{ date('Y') }} IoT Meter Monitoring &bull; Industrial Modbus System
        </div>
    </div>

    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        if (togglePassword && passwordInput && eyeIcon) {
            togglePassword.addEventListener('click', () => {
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                eyeIcon.classList.toggle('bi-eye');
                eyeIcon.classList.toggle('bi-eye-slash');
            });
        }
    </script>
</body>

</html>