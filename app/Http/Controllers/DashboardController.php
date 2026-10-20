<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Device;
use App\Models\MeterLog;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
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
     * Tampilkan halaman utama monitoring SCADA Flow & Water Meter & Pressure.
     */
    public function index()
    {
        $devices = Device::with(['registers' => function ($query) {
            $query->active();
        }])->active()->orderBy('id')->get();

        // Ambil device aktif berdasarkan slave_id atau urutan index
        $deviceRms = $devices->where('slave_id', 1)->first() ?? $devices->get(0);
        $deviceMhs = $devices->where('slave_id', 2)->first() ?? $devices->get(1);
        $deviceHrms = $devices->where('slave_id', 3)->first() ?? $devices->get(2);
        $devicePh = $devices->where('slave_id', 4)->first()
            ?? $devices->first(fn ($d) => $d->isPressureSensor() || $d->isPhSensor())
            ?? $devices->get(3);

        // Label dan deskripsi langsung membaca dari konfigurasi database
        $labelRms = $deviceRms?->name ?? 'Flowmeter 2" Area Chiller';
        $labelMhs = $deviceMhs?->name ?? 'Water meter 3/4" Area Toilet';
        $labelHrms = $deviceHrms?->name ?? 'Water meter 1/2" Area Office';
        $labelPh = $devicePh?->name ?? 'Pressure Sensor';

        $descRms = $deviceRms?->description ?: 'Nominal Size : 1 inch | Range : 0 ~ 4 m³/h';
        $descMhs = $deviceMhs?->description ?: 'Nominal Size : 3/4 inch | Range : 0 ~ 10 m³/h';
        $descHrms = $deviceHrms?->description ?: 'Nominal Size : 1/2 inch | Range : 0 ~ 10 m³/h';
        $descPh = $devicePh?->description ?: 'Range : 0 ~ 10 bar';

        // Ambil konfigurasi batas maksimal (kondisi 100% skala gauge) dari AppSetting (default: RMS 4.0 m³/h, MHS 10.0 m³/h, HRMS 10.0 m³/h, Pressure 10.0 Bar)
        $maxRms = (float) AppSetting::getValue('max_flowrate_rms', 4.0);
        $maxMhs = (float) AppSetting::getValue('max_flowrate_mhs', 10.0);
        $maxHrms = (float) AppSetting::getValue('max_flowrate_hrms', 10.0);
        $maxPh = (float) AppSetting::getValue('max_pressure', 10.0);
        $flowWarningPct = (int) AppSetting::getValue('flow_warning_percent', 85);

        // Prioritas pembacaan data realtime instrumen:
        // 1. Cek Cache telemetri realtime terakhir (menjamin nilai langsung tampil instan tanpa jeda 0 saat navigasi menu)
        // 2. Fallback ke rekaman log terakhir di database (MeterLog)
        // 3. Fallback ke 0.0 jika sistem baru pertama kali berjalan
        $cachedScada = Cache::get('scada_latest_telemetry');
        $cachedDevRms = $deviceRms ? Cache::get("device_latest_telemetry_{$deviceRms->id}") : null;
        $cachedDevMhs = $deviceMhs ? Cache::get("device_latest_telemetry_{$deviceMhs->id}") : null;
        $cachedDevHrms = $deviceHrms ? Cache::get("device_latest_telemetry_{$deviceHrms->id}") : null;
        $cachedDevPh = $devicePh ? Cache::get("device_latest_telemetry_{$devicePh->id}") : null;

        $latestRms = $deviceRms ? MeterLog::where('device_id', $deviceRms->id)->latest('logged_at')->first() : null;
        $latestMhs = $deviceMhs ? MeterLog::where('device_id', $deviceMhs->id)->latest('logged_at')->first() : null;
        $latestHrms = $deviceHrms ? MeterLog::where('device_id', $deviceHrms->id)->latest('logged_at')->first() : null;
        $latestPh = $devicePh ? MeterLog::where('device_id', $devicePh->id)->latest('logged_at')->first() : null;

        $currentRmsFlow = ! empty($cachedDevRms['flow_rate']) && $cachedDevRms['flow_rate'] > 0
            ? (float) $cachedDevRms['flow_rate']
            : (! empty($cachedScada['rms']['flow_rate']) && $cachedScada['rms']['flow_rate'] > 0
                ? (float) $cachedScada['rms']['flow_rate']
                : ($latestRms && $latestRms->flow_rate > 0
                    ? (float) $latestRms->flow_rate
                    : (float) ($cachedDevRms['flow_rate'] ?? $cachedScada['rms']['flow_rate'] ?? $latestRms?->flow_rate ?? 0.0)));

        $currentRmsTot = ! empty($cachedDevRms['totalizer']) && $cachedDevRms['totalizer'] > 0
            ? (float) $cachedDevRms['totalizer']
            : (! empty($cachedScada['rms']['totalizer']) && $cachedScada['rms']['totalizer'] > 0
                ? (float) $cachedScada['rms']['totalizer']
                : ($latestRms && $latestRms->totalizer > 0
                    ? (float) $latestRms->totalizer
                    : (float) ($cachedDevRms['totalizer'] ?? $cachedScada['rms']['totalizer'] ?? $latestRms?->totalizer ?? 0.0)));

        $currentMhsFlow = ! empty($cachedDevMhs['flow_rate']) && $cachedDevMhs['flow_rate'] > 0
            ? (float) $cachedDevMhs['flow_rate']
            : (! empty($cachedScada['mhs']['flow_rate']) && $cachedScada['mhs']['flow_rate'] > 0
                ? (float) $cachedScada['mhs']['flow_rate']
                : ($latestMhs && $latestMhs->flow_rate > 0
                    ? (float) $latestMhs->flow_rate
                    : (float) ($cachedDevMhs['flow_rate'] ?? $cachedScada['mhs']['flow_rate'] ?? $latestMhs?->flow_rate ?? 0.0)));

        $currentMhsTot = ! empty($cachedDevMhs['totalizer']) && $cachedDevMhs['totalizer'] > 0
            ? (float) $cachedDevMhs['totalizer']
            : (! empty($cachedScada['mhs']['totalizer']) && $cachedScada['mhs']['totalizer'] > 0
                ? (float) $cachedScada['mhs']['totalizer']
                : ($latestMhs && $latestMhs->totalizer > 0
                    ? (float) $latestMhs->totalizer
                    : (float) ($cachedDevMhs['totalizer'] ?? $cachedScada['mhs']['totalizer'] ?? $latestMhs?->totalizer ?? 0.0)));

        $currentHrmsFlow = ! empty($cachedDevHrms['flow_rate']) && $cachedDevHrms['flow_rate'] > 0
            ? (float) $cachedDevHrms['flow_rate']
            : (! empty($cachedScada['h_rms']['flow_rate']) && $cachedScada['h_rms']['flow_rate'] > 0
                ? (float) $cachedScada['h_rms']['flow_rate']
                : ($latestHrms && $latestHrms->flow_rate > 0
                    ? (float) $latestHrms->flow_rate
                    : (float) ($cachedDevHrms['flow_rate'] ?? $cachedScada['h_rms']['flow_rate'] ?? $latestHrms?->flow_rate ?? 0.0)));

        $currentHrmsTot = ! empty($cachedDevHrms['totalizer']) && $cachedDevHrms['totalizer'] > 0
            ? (float) $cachedDevHrms['totalizer']
            : (! empty($cachedScada['h_rms']['totalizer']) && $cachedScada['h_rms']['totalizer'] > 0
                ? (float) $cachedScada['h_rms']['totalizer']
                : ($latestHrms && $latestHrms->totalizer > 0
                    ? (float) $latestHrms->totalizer
                    : (float) ($cachedDevHrms['totalizer'] ?? $cachedScada['h_rms']['totalizer'] ?? $latestHrms?->totalizer ?? 0.0)));

        $currentPh = ! empty($cachedDevPh['pressure']) && $cachedDevPh['pressure'] > 0
            ? (float) $cachedDevPh['pressure']
            : (! empty($cachedDevPh['ph']) && $cachedDevPh['ph'] > 0
                ? (float) $cachedDevPh['ph']
                : (! empty($cachedScada['pressure']['value']) && $cachedScada['pressure']['value'] > 0
                    ? (float) $cachedScada['pressure']['value']
                    : (! empty($cachedScada['ph']['value']) && $cachedScada['ph']['value'] > 0
                        ? (float) $cachedScada['ph']['value']
                        : ($latestPh && ($latestPh->ph > 0)
                            ? (float) $latestPh->ph
                            : (float) ($cachedDevPh['pressure'] ?? $cachedScada['pressure']['value'] ?? $latestPh?->ph ?? 0.0)))));

        // Ambil riwayat log untuk tabel HISTORY DATA dan grafik
        $recentLogs = MeterLog::with('device')
            ->latest('logged_at')
            ->take(200)
            ->get();

        // Kelompokkan per jam untuk tabel riwayat
        $hourlyRows = [];
        if ($recentLogs->isNotEmpty()) {
            $groupedByHour = $recentLogs->groupBy(fn ($log) => $log->logged_at->format('Y-m-d H:00'));

            foreach ($groupedByHour->take(8) as $timeStr => $logsInHour) {
                $rmsLog = $deviceRms ? $logsInHour->firstWhere('device_id', $deviceRms->id) : null;
                $mhsLog = $deviceMhs ? $logsInHour->firstWhere('device_id', $deviceMhs->id) : null;
                $hrmsLog = $deviceHrms ? $logsInHour->firstWhere('device_id', $deviceHrms->id) : null;
                $phLog = $devicePh ? $logsInHour->firstWhere('device_id', $devicePh->id) : null;

                $hourlyRows[] = [
                    'time' => $timeStr,
                    'rms_flow' => (float) ($rmsLog?->flow_rate ?? 0.0),
                    'rms_tot' => (float) ($rmsLog?->totalizer ?? 0.0),
                    'mhs_flow' => (float) ($mhsLog?->flow_rate ?? 0.0),
                    'mhs_tot' => (float) ($mhsLog?->totalizer ?? 0.0),
                    'hrms_flow' => (float) ($hrmsLog?->flow_rate ?? 0.0),
                    'hrms_tot' => (float) ($hrmsLog?->totalizer ?? 0.0),
                    'ph' => (float) ($phLog?->ph ?? 0.0),
                ];
            }
        }

        // Siapkan data grafik 24 jam terakhir (13 titik interval per 2 jam)
        $chartLabels = [];
        $chartRms = [];
        $chartMhs = [];
        $chartHrms = [];
        $chartPh = [];

        for ($i = 12; $i >= 0; $i--) {
            $timePoint = now()->subHours($i * 2);
            $chartLabels[] = $timePoint->format('H:00');

            if ($recentLogs->isEmpty()) {
                $chartRms[] = 0.0;
                $chartMhs[] = 0.0;
                $chartHrms[] = 0.0;
                $chartPh[] = 0.0;
            } else {
                $start = $timePoint->copy()->subHours(1);
                $end = $timePoint->copy()->addHours(1);

                $bucketLogs = $recentLogs->filter(fn ($l) => $l->logged_at >= $start && $l->logged_at <= $end);

                $l1 = $deviceRms ? $bucketLogs->firstWhere('device_id', $deviceRms->id) : null;
                $l2 = $deviceMhs ? $bucketLogs->firstWhere('device_id', $deviceMhs->id) : null;
                $l3 = $deviceHrms ? $bucketLogs->firstWhere('device_id', $deviceHrms->id) : null;
                $l4 = $devicePh ? $bucketLogs->firstWhere('device_id', $devicePh->id) : null;

                $chartRms[] = $l1 ? round((float) ($l1->flow_rate ?? 0), 1) : 0.0;
                $chartMhs[] = $l2 ? round((float) ($l2->flow_rate ?? 0), 1) : 0.0;
                $chartHrms[] = $l3 ? round((float) ($l3->flow_rate ?? 0), 1) : 0.0;
                $chartPh[] = $l4 ? round((float) ($l4->ph ?? 0), 2) : 0.0;
            }
        }

        $totalFlowCurrent = $currentRmsFlow + $currentMhsFlow + $currentHrmsFlow;
        $totalizerAllCurrent = $currentRmsTot + $currentMhsTot + $currentHrmsTot;

        $pctRms = $totalFlowCurrent > 0 ? (int) round(($currentRmsFlow / $totalFlowCurrent) * 100) : 0;
        $pctMhs = $totalFlowCurrent > 0 ? (int) round(($currentMhsFlow / $totalFlowCurrent) * 100) : 0;
        $pctHrms = $totalFlowCurrent > 0 ? (int) round(($currentHrmsFlow / $totalFlowCurrent) * 100) : 0;

        $gaugePctRms = (int) min(100, max(0, round(($currentRmsFlow / max(0.001, $maxRms)) * 100)));
        $gaugePctMhs = (int) min(100, max(0, round(($currentMhsFlow / max(0.001, $maxMhs)) * 100)));
        $gaugePctHrms = (int) min(100, max(0, round(($currentHrmsFlow / max(0.001, $maxHrms)) * 100)));
        $gaugePctPh = (int) min(100, max(0, round(($currentPh / max(0.001, $maxPh)) * 100)));

        $circumference = 251.32;
        $gaugeOffsetRms = round($circumference - ($gaugePctRms / 100) * $circumference, 1);
        $gaugeOffsetMhs = round($circumference - ($gaugePctMhs / 100) * $circumference, 1);
        $gaugeOffsetHrms = round($circumference - ($gaugePctHrms / 100) * $circumference, 1);
        $gaugeOffsetPh = round($circumference - ($gaugePctPh / 100) * $circumference, 1);

        $statusPh = ($currentPh >= 1.0 && $currentPh <= 4.0) ? 'Normal' : ($currentPh > 0 ? 'Warning' : 'Standby');

        $isRmsOnline = isset($cachedDevRms['is_online'])
            ? (bool) $cachedDevRms['is_online']
            : (isset($cachedScada['rms']['is_online'])
                ? (bool) $cachedScada['rms']['is_online']
                : ($deviceRms ? (bool) Cache::get("device_online_{$deviceRms->id}", false) : false));

        $isMhsOnline = isset($cachedDevMhs['is_online'])
            ? (bool) $cachedDevMhs['is_online']
            : (isset($cachedScada['mhs']['is_online'])
                ? (bool) $cachedScada['mhs']['is_online']
                : ($deviceMhs ? (bool) Cache::get("device_online_{$deviceMhs->id}", false) : false));

        $isHrmsOnline = isset($cachedDevHrms['is_online'])
            ? (bool) $cachedDevHrms['is_online']
            : (isset($cachedScada['h_rms']['is_online'])
                ? (bool) $cachedScada['h_rms']['is_online']
                : ($deviceHrms ? (bool) Cache::get("device_online_{$deviceHrms->id}", false) : false));

        $isPhOnline = isset($cachedDevPh['is_online'])
            ? (bool) $cachedDevPh['is_online']
            : (isset($cachedScada['pressure']['is_online'])
                ? (bool) $cachedScada['pressure']['is_online']
                : ($devicePh ? (bool) Cache::get("device_online_{$devicePh->id}", false) : false));

        return view('dashboard', compact(
            'devices',
            'hourlyRows',
            'currentRmsFlow',
            'currentRmsTot',
            'currentMhsFlow',
            'currentMhsTot',
            'currentHrmsFlow',
            'currentHrmsTot',
            'currentPh',
            'totalFlowCurrent',
            'totalizerAllCurrent',
            'pctRms',
            'pctMhs',
            'pctHrms',
            'gaugePctRms',
            'gaugePctMhs',
            'gaugePctHrms',
            'gaugePctPh',
            'gaugeOffsetRms',
            'gaugeOffsetMhs',
            'gaugeOffsetHrms',
            'gaugeOffsetPh',
            'statusPh',
            'isRmsOnline',
            'isMhsOnline',
            'isHrmsOnline',
            'isPhOnline',
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
            'devicePh',
            'chartLabels',
            'chartRms',
            'chartMhs',
            'chartHrms',
            'chartPh',
            'maxRms',
            'maxMhs',
            'maxHrms',
            'maxPh',
            'flowWarningPct'
        ));
    }
}
