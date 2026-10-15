<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Device;
use App\Models\MeterLog;
use App\Models\Register;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Tampilkan halaman pusat pengaturan sistem.
     */
    public function index()
    {
        $loggerEnabled = AppSetting::getValue('logger_enabled', '1') === '1';
        $loggerIntervalSeconds = (int) AppSetting::getValue('logger_interval_seconds', '60');

        $totalDevices = Device::count();
        $totalRegisters = Register::count();
        $totalLogs = MeterLog::count();

        $dbPath = database_path('database.sqlite');
        $dbSizeFormatted = file_exists($dbPath) ? round(filesize($dbPath) / 1024, 1).' KB' : '0 KB';
        $lastLog = MeterLog::latest('logged_at')->first();
        $lastLoggedAt = $lastLog ? $lastLog->logged_at->format('d M Y H:i:s') : 'Belum ada log';

        return view('settings.index', compact(
            'loggerEnabled',
            'loggerIntervalSeconds',
            'totalDevices',
            'totalRegisters',
            'totalLogs',
            'dbSizeFormatted',
            'lastLoggedAt'
        ));
    }

    /**
     * Simpan pengaturan interval data logger.
     */
    public function updateLogger(Request $request)
    {
        $validated = $request->validate([
            'logger_interval_seconds' => ['required', 'integer', 'min:1', 'max:31536000'],
        ], [
            'logger_interval_seconds.required' => 'Interval pencatatan data logger wajib ditentukan.',
            'logger_interval_seconds.min' => 'Interval minimal 1 detik.',
            'logger_interval_seconds.max' => 'Interval maksimal 1 tahun (31.536.000 detik).',
        ]);

        $isEnabled = $request->has('logger_enabled') ? '1' : '0';

        AppSetting::setValue('logger_enabled', $isEnabled, 'Status aktif/non-aktif data logger');
        AppSetting::setValue('logger_interval_seconds', (string) $validated['logger_interval_seconds'], 'Interval pencatatan log dalam detik');

        return back()->with('success', 'Pengaturan interval data logger berhasil diperbarui.');
    }

    /**
     * Download salinan database SQLite untuk backup.
     */
    public function downloadDatabase()
    {
        $dbPath = database_path('database.sqlite');

        if (! file_exists($dbPath)) {
            return back()->with('error', 'File database SQLite tidak ditemukan.');
        }

        $filename = 'backup_iotlistrik_'.date('Ymd_His').'.sqlite';

        return response()->download($dbPath, $filename, [
            'Content-Type' => 'application/x-sqlite3',
        ]);
    }
}
