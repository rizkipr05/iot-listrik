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

        $devices = Device::active()->orderBy('id')->get();
        $deviceRms = $devices->where('slave_id', 1)->first() ?? $devices->get(0);
        $deviceMhs = $devices->where('slave_id', 2)->first() ?? $devices->get(1);
        $deviceHrms = $devices->where('slave_id', 3)->first() ?? $devices->get(2);
        $devicePh = $devices->where('slave_id', 4)->first()
            ?? $devices->first(fn ($d) => $d->isPressureSensor() || $d->isPhSensor())
            ?? $devices->get(3);

        $labelRms = $deviceRms?->name ?? 'Flowmeter 1 - Area RMS';
        $labelMhs = $deviceMhs?->name ?? 'Flowmeter 2 - Area Toilet';
        $labelHrms = $deviceHrms?->name ?? 'Flowmeter 3 - Area Office';
        $labelPh = $devicePh?->name ?? 'Pressure Sensor';

        // Nilai batas maksimal flowrate & pressure (kondisi 100% full scale, default: RMS 4.0 m³/h, MHS 10.0 m³/h, HRMS 10.0 m³/h, Pressure 10.0 Bar)
        $maxFlowRms = (float) AppSetting::getValue('max_flowrate_rms', 4.0);
        $maxFlowMhs = (float) AppSetting::getValue('max_flowrate_mhs', 10.0);
        $maxFlowHrms = (float) AppSetting::getValue('max_flowrate_hrms', 10.0);
        $maxPressure = (float) AppSetting::getValue('max_pressure', 10.0);
        $flowWarningPct = (int) AppSetting::getValue('flow_warning_percent', 85);

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
            'maxFlowRms',
            'maxFlowMhs',
            'maxFlowHrms',
            'maxPressure',
            'flowWarningPct',
            'labelRms',
            'labelMhs',
            'labelHrms',
            'labelPh',
            'deviceRms',
            'deviceMhs',
            'deviceHrms',
            'devicePh',
            'totalDevices',
            'totalRegisters',
            'totalLogs',
            'dbSizeFormatted',
            'lastLoggedAt'
        ));
    }

    /**
     * Tampilkan halaman khusus pengaturan skala maksimal gauge.
     */
    public function scales()
    {
        $devices = Device::active()->orderBy('id')->get();
        $deviceRms = $devices->where('slave_id', 1)->first() ?? $devices->get(0);
        $deviceMhs = $devices->where('slave_id', 2)->first() ?? $devices->get(1);
        $deviceHrms = $devices->where('slave_id', 3)->first() ?? $devices->get(2);
        $devicePh = $devices->where('slave_id', 4)->first()
            ?? $devices->first(fn ($d) => $d->isPressureSensor() || $d->isPhSensor())
            ?? $devices->get(3);

        $labelRms = $deviceRms?->name ?? 'Flowmeter 1 - Area RMS';
        $labelMhs = $deviceMhs?->name ?? 'Flowmeter 2 - Area Toilet';
        $labelHrms = $deviceHrms?->name ?? 'Flowmeter 3 - Area Office';
        $labelPh = $devicePh?->name ?? 'Pressure Sensor';

        $descRms = $deviceRms?->description ?: 'Nominal Size : 1 inch | Range : 0 ~ 4 m³/h';
        $descMhs = $deviceMhs?->description ?: 'Nominal Size : 3/4 inch | Range : 0 ~ 10 m³/h';
        $descHrms = $deviceHrms?->description ?: 'Nominal Size : 1/2 inch | Range : 0 ~ 10 m³/h';
        $descPh = $devicePh?->description ?: 'Range : 0 ~ 10 bar';

        $maxFlowRms = (float) AppSetting::getValue('max_flowrate_rms', 4.0);
        $maxFlowMhs = (float) AppSetting::getValue('max_flowrate_mhs', 10.0);
        $maxFlowHrms = (float) AppSetting::getValue('max_flowrate_hrms', 10.0);
        $maxPressure = (float) AppSetting::getValue('max_pressure', 10.0);
        $flowWarningPct = (int) AppSetting::getValue('flow_warning_percent', 85);

        return view('settings.scales', compact(
            'maxFlowRms',
            'maxFlowMhs',
            'maxFlowHrms',
            'maxPressure',
            'flowWarningPct',
            'labelRms',
            'labelMhs',
            'labelHrms',
            'labelPh',
            'descRms',
            'descMhs',
            'descHrms',
            'descPh',
            'deviceRms',
            'deviceMhs',
            'deviceHrms',
            'devicePh'
        ));
    }

    /**
     * Simpan batas kapasitas maksimal flowrate (kondisi 100%) dan pressure.
     */
    public function updateScales(Request $request)
    {
        $validated = $request->validate([
            'max_flowrate_rms' => ['required', 'numeric', 'min:0.1', 'max:100000'],
            'max_flowrate_mhs' => ['required', 'numeric', 'min:0.1', 'max:100000'],
            'max_flowrate_hrms' => ['required', 'numeric', 'min:0.1', 'max:100000'],
            'max_pressure' => ['required', 'numeric', 'min:0.1', 'max:1000'],
            'flow_warning_percent' => ['nullable', 'integer', 'min:1', 'max:100'],
        ], [
            'max_flowrate_rms.required' => 'Batas maksimal flowrate meter RMS wajib ditentukan.',
            'max_flowrate_mhs.required' => 'Batas maksimal flowrate meter MHS wajib ditentukan.',
            'max_flowrate_hrms.required' => 'Batas maksimal flowrate meter H-RMS wajib ditentukan.',
            'max_pressure.required' => 'Batas maksimal tekanan / pressure wajib ditentukan.',
        ]);

        AppSetting::setValue('max_flowrate_rms', (string) $validated['max_flowrate_rms'], 'Kapasitas maksimal flowrate Meter 1 (RMS) untuk 100%');
        AppSetting::setValue('max_flowrate_mhs', (string) $validated['max_flowrate_mhs'], 'Kapasitas maksimal flowrate Meter 2 (MHS) untuk 100%');
        AppSetting::setValue('max_flowrate_hrms', (string) $validated['max_flowrate_hrms'], 'Kapasitas maksimal flowrate Meter 3 (H-RMS) untuk 100%');
        AppSetting::setValue('max_pressure', (string) $validated['max_pressure'], 'Kapasitas maksimal pressure sensor untuk 100%');

        if (isset($validated['flow_warning_percent'])) {
            AppSetting::setValue('flow_warning_percent', (string) $validated['flow_warning_percent'], 'Ambang batas peringatan aliran tinggi (%)');
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengaturan batas maksimal (kondisi 100% gauge) flowrate & pressure berhasil disimpan.',
                'scales' => [
                    'rms_max' => (float) $validated['max_flowrate_rms'],
                    'mhs_max' => (float) $validated['max_flowrate_mhs'],
                    'hrms_max' => (float) $validated['max_flowrate_hrms'],
                    'pressure_max' => (float) $validated['max_pressure'],
                    'warning_percent' => (int) ($validated['flow_warning_percent'] ?? 85),
                ],
            ]);
        }

        return back()->with('success', 'Pengaturan batas maksimal (kondisi 100% gauge) flowrate & pressure berhasil disimpan.');
    }

    /**
     * Ekstrak batas nilai maksimal dari deskripsi jika ada pola 'Range: 0 - X' atau 'Range : 0 ~ X'
     */
    private function extractMaxRange(?string $description, float $fallback): float
    {
        if ($description && preg_match('/Range\s*:\s*0\s*[-~]\s*(\d+(?:\.\d+)?)/i', $description, $matches)) {
            $val = (float) $matches[1];
            if ($val > 0) {
                return $val;
            }
        }

        return $fallback;
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
