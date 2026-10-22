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

        // Petakan instrumen SCADA secara konsisten berdasarkan kategori dan penamaan
        $scadaInstruments = Device::resolveScadaInstruments($devices);
        $deviceRms = $scadaInstruments['rms'];
        $deviceMhs = $scadaInstruments['mhs'];
        $deviceHrms = $scadaInstruments['hrms'];
        $devicePh = $scadaInstruments['pressure'];

        // Label dan deskripsi langsung membaca dari konfigurasi database
        $labelRms = $deviceRms?->name ?? 'Flowmeter 2" Area Chiller';
        $labelMhs = $deviceMhs?->name ?? 'Water meter 3/4" Area Toilet';
        $labelHrms = $deviceHrms?->name ?? 'Water meter 1/2" Area Office';
        $labelPh = $devicePh?->name ?? 'Pressure Sensor';

        $descRms = $deviceRms?->description ?: 'Nominal Size : 1 inch | Range : 0 ~ 4 m³/h';
        $descMhs = $deviceMhs?->description ?: 'Nominal Size : 3/4 inch | Range : 0 ~ 10 m³/h';
        $descHrms = $deviceHrms?->description ?: 'Nominal Size : 1/2 inch | Range : 0 ~ 10 m³/h';
        $rawDescPh = $devicePh?->description ?: 'Range : 0 ~ 10 bar';
        $descPh = trim(preg_replace('/\s*\|\s*Normal\s*:[^|]+/i', '', $rawDescPh));
        $descPh = trim(preg_replace('/\s*\(Normal\s*:[^)]+\)/i', '', $descPh));

        // Ambil konfigurasi batas maksimal (kondisi 100% skala gauge) dari AppSetting (default: RMS 4.0 m³/h, MHS 10.0 m³/h, HRMS 10.0 m³/h, Pressure 10.0 Bar)
        $maxRms = (float) AppSetting::getValue('max_flowrate_rms', 4.0);
        $maxMhs = (float) AppSetting::getValue('max_flowrate_mhs', 10.0);
        $maxHrms = (float) AppSetting::getValue('max_flowrate_hrms', 10.0);
        $maxPh = (float) AppSetting::getValue('max_pressure', 10.0);
        $pressureNormalMin = (float) AppSetting::getValue('pressure_normal_min', 1.0);
        $pressureNormalMax = (float) AppSetting::getValue('pressure_normal_max', 4.0);
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

        $latestRms = $deviceRms ? MeterLog::where('device_id', $deviceRms->id)->where('is_simulated', false)->latest('logged_at')->first() : null;
        $latestMhs = $deviceMhs ? MeterLog::where('device_id', $deviceMhs->id)->where('is_simulated', false)->latest('logged_at')->first() : null;
        $latestHrms = $deviceHrms ? MeterLog::where('device_id', $deviceHrms->id)->where('is_simulated', false)->latest('logged_at')->first() : null;
        $latestPh = $devicePh ? MeterLog::where('device_id', $devicePh->id)->where('is_simulated', false)->whereNotNull('ph')->latest('logged_at')->first() : null;

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

        if (! $isPhOnline && $devicePh && Cache::get("device_online_{$devicePh->id}", false)) {
            $isPhOnline = true;
        }

        $currentRmsFlow = $isRmsOnline ? (
            ! empty($cachedDevRms['flow_rate']) && $cachedDevRms['flow_rate'] > 0
                ? (float) $cachedDevRms['flow_rate']
                : (! empty($cachedScada['rms']['flow_rate']) && $cachedScada['rms']['flow_rate'] > 0
                    ? (float) $cachedScada['rms']['flow_rate']
                    : ($latestRms && $latestRms->flow_rate > 0
                        ? (float) $latestRms->flow_rate
                        : 0.0))
        ) : 0.0;

        $currentRmsTot = ! empty($cachedDevRms['totalizer']) && $cachedDevRms['totalizer'] > 0
            ? (float) $cachedDevRms['totalizer']
            : (! empty($cachedScada['rms']['totalizer']) && $cachedScada['rms']['totalizer'] > 0
                ? (float) $cachedScada['rms']['totalizer']
                : ($latestRms && $latestRms->totalizer > 0
                    ? (float) $latestRms->totalizer
                    : 0.0));

        $currentMhsFlow = $isMhsOnline ? (
            ! empty($cachedDevMhs['flow_rate']) && $cachedDevMhs['flow_rate'] > 0
                ? (float) $cachedDevMhs['flow_rate']
                : (! empty($cachedScada['mhs']['flow_rate']) && $cachedScada['mhs']['flow_rate'] > 0
                    ? (float) $cachedScada['mhs']['flow_rate']
                    : ($latestMhs && $latestMhs->flow_rate > 0
                        ? (float) $latestMhs->flow_rate
                        : 0.0))
        ) : 0.0;

        $currentMhsTot = $isMhsOnline ? (
            ! empty($cachedDevMhs['totalizer']) && $cachedDevMhs['totalizer'] > 0
                ? (float) $cachedDevMhs['totalizer']
                : (! empty($cachedScada['mhs']['totalizer']) && $cachedScada['mhs']['totalizer'] > 0
                    ? (float) $cachedScada['mhs']['totalizer']
                    : ($latestMhs && $latestMhs->totalizer > 0
                        ? (float) $latestMhs->totalizer
                        : 0.0))
        ) : 0.0;

        $currentHrmsFlow = $isHrmsOnline ? (
            ! empty($cachedDevHrms['flow_rate']) && $cachedDevHrms['flow_rate'] > 0
                ? (float) $cachedDevHrms['flow_rate']
                : (! empty($cachedScada['h_rms']['flow_rate']) && $cachedScada['h_rms']['flow_rate'] > 0
                    ? (float) $cachedScada['h_rms']['flow_rate']
                    : ($latestHrms && $latestHrms->flow_rate > 0
                        ? (float) $latestHrms->flow_rate
                        : 0.0))
        ) : 0.0;

        $currentHrmsTot = $isHrmsOnline ? (
            ! empty($cachedDevHrms['totalizer']) && $cachedDevHrms['totalizer'] > 0
                ? (float) $cachedDevHrms['totalizer']
                : (! empty($cachedScada['h_rms']['totalizer']) && $cachedScada['h_rms']['totalizer'] > 0
                    ? (float) $cachedScada['h_rms']['totalizer']
                    : ($latestHrms && $latestHrms->totalizer > 0
                        ? (float) $latestHrms->totalizer
                        : 0.0))
        ) : 0.0;

        if (isset($cachedDevPh['pressure']) && is_numeric($cachedDevPh['pressure'])) {
            $currentPh = (float) $cachedDevPh['pressure'];
        } elseif (isset($cachedDevPh['ph']) && is_numeric($cachedDevPh['ph'])) {
            $currentPh = (float) $cachedDevPh['ph'];
        } elseif (isset($cachedScada['pressure']['value']) && is_numeric($cachedScada['pressure']['value'])) {
            $currentPh = (float) $cachedScada['pressure']['value'];
        } elseif (isset($cachedScada['ph']['value']) && is_numeric($cachedScada['ph']['value'])) {
            $currentPh = (float) $cachedScada['ph']['value'];
        } elseif ($latestPh && is_numeric($latestPh->ph)) {
            $currentPh = (float) $latestPh->ph;
        } else {
            $currentPh = 0.0;
        }

        // Ambil riwayat log telemetri real (kecualikan data simulasi dummy)
        $recentLogs = MeterLog::with('device')
            ->where('is_simulated', false)
            ->latest('logged_at')
            ->take(200)
            ->get();

        // Kelompokkan per jam untuk tabel riwayat HISTORY DATA
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
                    'rms_flow' => ($isRmsOnline && $rmsLog) ? (float) $rmsLog->flow_rate : ($isRmsOnline && $currentRmsFlow > 0 ? $currentRmsFlow : null),
                    'rms_tot' => ($isRmsOnline && $rmsLog) ? (float) $rmsLog->totalizer : ($isRmsOnline && $currentRmsTot > 0 ? $currentRmsTot : null),
                    'mhs_flow' => ($isMhsOnline && $mhsLog) ? (float) $mhsLog->flow_rate : null,
                    'mhs_tot' => ($isMhsOnline && $mhsLog) ? (float) $mhsLog->totalizer : null,
                    'hrms_flow' => ($isHrmsOnline && $hrmsLog) ? (float) $hrmsLog->flow_rate : null,
                    'hrms_tot' => ($isHrmsOnline && $hrmsLog) ? (float) $hrmsLog->totalizer : null,
                    'ph' => ($isPhOnline && $phLog) ? (float) $phLog->ph : null,
                    'pressure' => ($isPhOnline && $phLog) ? (float) $phLog->ph : null,
                ];
            }
        } elseif ($isRmsOnline && ($currentRmsFlow > 0 || $currentRmsTot > 0)) {
            $hourlyRows[] = [
                'time' => now()->format('Y-m-d H:00'),
                'rms_flow' => $currentRmsFlow,
                'rms_tot' => $currentRmsTot,
                'mhs_flow' => null,
                'mhs_tot' => null,
                'hrms_flow' => null,
                'hrms_tot' => null,
                'ph' => null,
                'pressure' => null,
            ];
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

            $start = $timePoint->copy()->subHours(1);
            $end = $timePoint->copy()->addHours(1);

            $bucketLogs = $recentLogs->filter(fn ($l) => $l->logged_at >= $start && $l->logged_at <= $end);

            $l1 = $deviceRms ? $bucketLogs->firstWhere('device_id', $deviceRms->id) : null;
            $l2 = $deviceMhs ? $bucketLogs->firstWhere('device_id', $deviceMhs->id) : null;
            $l3 = $deviceHrms ? $bucketLogs->firstWhere('device_id', $deviceHrms->id) : null;
            $l4 = $devicePh ? $bucketLogs->firstWhere('device_id', $devicePh->id) : null;

            // Device yang sedang offline / belum terhubung nilainya mutlak 0.0 agar grafik flat dan tidak anomali
            $chartRms[] = $isRmsOnline
                ? ($l1 ? round((float) ($l1->flow_rate ?? 0), 1) : (($i === 0) ? round($currentRmsFlow, 1) : 0.0))
                : 0.0;

            $chartMhs[] = $isMhsOnline
                ? ($l2 ? round((float) ($l2->flow_rate ?? 0), 1) : (($i === 0) ? round($currentMhsFlow, 1) : 0.0))
                : 0.0;

            $chartHrms[] = $isHrmsOnline
                ? ($l3 ? round((float) ($l3->flow_rate ?? 0), 1) : (($i === 0) ? round($currentHrmsFlow, 1) : 0.0))
                : 0.0;

            $chartPh[] = $isPhOnline
                ? ($l4 ? round((float) ($l4->ph ?? 0), 2) : (($i === 0) ? round($currentPh, 2) : 0.0))
                : 0.0;
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

        $statusRms = ! $isRmsOnline ? 'Offline' : ($gaugePctRms >= $flowWarningPct ? 'Warning' : 'Normal');
        $statusMhs = ! $isMhsOnline ? 'Offline' : ($gaugePctMhs >= $flowWarningPct ? 'Warning' : 'Normal');
        $statusHrms = ! $isHrmsOnline ? 'Offline' : ($gaugePctHrms >= $flowWarningPct ? 'Warning' : 'Normal');
        $statusPh = ! $isPhOnline
            ? 'Offline'
            : ($currentPh <= 0
                ? 'Standby'
                : (($currentPh >= $pressureNormalMin && $currentPh <= $pressureNormalMax) ? 'Normal' : 'Warning'));

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
            'statusRms',
            'statusMhs',
            'statusHrms',
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
            'pressureNormalMin',
            'pressureNormalMax',
            'flowWarningPct'
        ));
    }
}
