<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\MeterLog;

class DashboardController extends Controller
{
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

        $descRms = $deviceRms?->description ?: 'Nominal Size : 2 inch | Range : 0 ~ 300 m³/h';
        $descMhs = $deviceMhs?->description ?: 'Nominal Size : 3/4 inch | Range : 0 ~ 300 m³/h';
        $descHrms = $deviceHrms?->description ?: 'Nominal Size : 1/2 inch | Range : 0 ~ 300 m³/h';
        $descPh = $devicePh?->description ?: 'Range : 0 ~ 10 bar';

        // Ambil pembacaan terakhir masing-masing instrumen (default 0 jika belum ada log)
        $latestRms = $deviceRms ? MeterLog::where('device_id', $deviceRms->id)->latest('logged_at')->first() : null;
        $latestMhs = $deviceMhs ? MeterLog::where('device_id', $deviceMhs->id)->latest('logged_at')->first() : null;
        $latestHrms = $deviceHrms ? MeterLog::where('device_id', $deviceHrms->id)->latest('logged_at')->first() : null;
        $latestPh = $devicePh ? MeterLog::where('device_id', $devicePh->id)->latest('logged_at')->first() : null;

        $currentRmsFlow = $latestRms ? (float) ($latestRms->flow_rate ?? 0.0) : 0.0;
        $currentRmsTot = $latestRms ? (float) ($latestRms->totalizer ?? 0.0) : 0.0;
        $currentMhsFlow = $latestMhs ? (float) ($latestMhs->flow_rate ?? 0.0) : 0.0;
        $currentMhsTot = $latestMhs ? (float) ($latestMhs->totalizer ?? 0.0) : 0.0;
        $currentHrmsFlow = $latestHrms ? (float) ($latestHrms->flow_rate ?? 0.0) : 0.0;
        $currentHrmsTot = $latestHrms ? (float) ($latestHrms->totalizer ?? 0.0) : 0.0;
        $currentPh = $latestPh ? (float) ($latestPh->ph ?? 0.0) : 0.0;

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

        $gaugePctRms = (int) min(100, max(0, round(($currentRmsFlow / 300) * 100)));
        $gaugePctMhs = (int) min(100, max(0, round(($currentMhsFlow / 300) * 100)));
        $gaugePctHrms = (int) min(100, max(0, round(($currentHrmsFlow / 300) * 100)));
        $gaugePctPh = (int) min(100, max(0, round(($currentPh / 10) * 100)));

        $circumference = 251.32;
        $gaugeOffsetRms = round($circumference - ($gaugePctRms / 100) * $circumference, 1);
        $gaugeOffsetMhs = round($circumference - ($gaugePctMhs / 100) * $circumference, 1);
        $gaugeOffsetHrms = round($circumference - ($gaugePctHrms / 100) * $circumference, 1);
        $gaugeOffsetPh = round($circumference - ($gaugePctPh / 100) * $circumference, 1);

        $statusPh = ($currentPh >= 1.0 && $currentPh <= 4.0) ? 'Normal' : ($currentPh > 0 ? 'Warning' : 'Standby');

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
            'chartPh'
        ));
    }
}
