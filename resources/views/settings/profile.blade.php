@extends('layouts.app')

@section('title', 'Pengaturan Profil Pengguna')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill small fw-bold" style="font-size: 0.7rem;">
                <i class="bi bi-person-gear me-1"></i> OPERATOR PROFILE
            </span>
            <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 small mono-font" style="font-size: 0.7rem;">
                SECURITY & CREDENTIALS
            </span>
        </div>
        <h4 class="fw-bold mb-0 text-dark">Pengaturan Akun & Profil</h4>
        <small class="text-muted">Kelola identitas operator, username login sistem, dan kata sandi keamanan.</small>
    </div>
    <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Pengaturan
    </a>
</div>

<div class="row g-4">
    <!-- Form Informasi Akun -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100" style="border-radius: 16px;">
            <div class="card-header bg-white py-3 px-4 d-flex align-items-center gap-2 border-bottom">
                <div class="p-2 bg-primary-subtle text-primary rounded-3">
                    <i class="bi bi-person-badge"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-0">Informasi Akun Operator</h6>
                    <small class="text-muted">Nama dan identitas akun yang digunakan untuk login</small>
                </div>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('settings.profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label fw-bold small text-secondary">Nama Lengkap Operator</label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', $user->name) }}" 
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label fw-bold small text-secondary">Username Login</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted">@</span>
                            <input type="text" 
                                   class="form-control mono-font @error('username') is-invalid @enderror" 
                                   id="username" 
                                   name="username" 
                                   value="{{ old('username', $user->username) }}" 
                                   required>
                        </div>
                        @error('username')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="email" class="form-label fw-bold small text-secondary">Alamat Email (Opsional)</label>
                        <input type="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               id="email" 
                               name="email" 
                               value="{{ old('email', $user->email) }}">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="bi bi-floppy me-1"></i> Simpan Perubahan Profil
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Form Ganti Password -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100" style="border-radius: 16px;">
            <div class="card-header bg-white py-3 px-4 d-flex align-items-center gap-2 border-bottom">
                <div class="p-2 bg-warning-subtle text-warning rounded-3">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-0">Keamanan & Ganti Password</h6>
                    <small class="text-muted">Perbarui kata sandi akses sistem SCADA</small>
                </div>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('settings.password.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="current_password" class="form-label fw-bold small text-secondary">Password Saat Ini</label>
                        <input type="password" 
                               class="form-control @error('current_password') is-invalid @enderror" 
                               id="current_password" 
                               name="current_password" 
                               placeholder="Masukkan password saat ini" 
                               required>
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-bold small text-secondary">Password Baru</label>
                        <input type="password" 
                               class="form-control @error('password') is-invalid @enderror" 
                               id="password" 
                               name="password" 
                               placeholder="Minimal 6 karakter"
                               required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label fw-bold small text-secondary">Konfirmasi Password Baru</label>
                        <input type="password" 
                               class="form-control" 
                               id="password_confirmation" 
                               name="password_confirmation" 
                               placeholder="Ketik ulang password baru"
                               required>
                    </div>

                    <button type="submit" class="btn btn-warning text-dark fw-bold rounded-pill px-4 shadow-sm">
                        <i class="bi bi-key-fill me-1"></i> Perbarui Kata Sandi
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
