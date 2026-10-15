<?php

use App\Http\Controllers\Api\TelemetryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    // Dashboard Monitoring
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Endpoint API Live Telemetry Modbus
    Route::get('/api/telemetry/live', [TelemetryController::class, 'getLiveTelemetry'])->name('api.telemetry.live');
    Route::get('/api/devices/{device}/read-live', [TelemetryController::class, 'readDevice'])->name('api.devices.read');

    // Data History & Log
    Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
    Route::post('/history/log-now', [HistoryController::class, 'logNow'])->name('history.log-now');
    Route::delete('/history/purge', [HistoryController::class, 'purgeLogs'])->name('history.purge');
    Route::get('/history/export', [HistoryController::class, 'exportCsv'])->name('history.export');

    // Device Management Routes
    Route::post('/devices/{device}/test-connection', [DeviceController::class, 'testConnection'])->name('devices.test');
    Route::resource('devices', DeviceController::class);

    // Register Configuration Routes
    Route::resource('registers', RegisterController::class);

    // Pengaturan Sistem & Interval Data Logger
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/logger', [SettingsController::class, 'updateLogger'])->name('settings.logger.update');
    Route::get('/settings/backup-db', [SettingsController::class, 'downloadDatabase'])->name('settings.backup-db');

    // Pengaturan Akun & Profil
    Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('settings.profile');
    Route::put('/settings/profile', [ProfileController::class, 'updateProfile'])->name('settings.profile.update');
    Route::put('/settings/password', [ProfileController::class, 'updatePassword'])->name('settings.password.update');
});
