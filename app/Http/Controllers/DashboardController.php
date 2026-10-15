<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\MeterLog;

class DashboardController extends Controller
{
    /**
     * Tampilkan halaman utama monitoring SCADA Flow Meter & pH.
     */
    public function index()
    {
        $devices = Device::with(['registers' => function ($query) {
            $query->active();
        }])->active()->get();

        // Ambil riwayat log per jam untuk tabel HISTORY DATA
        $recentLogs = MeterLog::with('device')
            ->latest('logged_at')
            ->take(100)
            ->get();

        // Kelompokkan per jam
        $hourlyRows = [];
        $groupedByHour = $recentLogs->groupBy(fn ($log) => $log->logged_at->format('Y-m-d H:00'));

        foreach ($groupedByHour->take(8) as $timeStr => $logsInHour) {
            $rmsLog = $logsInHour->first(fn ($l) => $l->device && ($l->device->slave_id === 1 || (str_contains(strtolower($l->device->name), 'rms') && ! str_contains(strtolower($l->device->name), 'h-rms'))));
            $mhsLog = $logsInHour->first(fn ($l) => $l->device && ($l->device->slave_id === 2 || str_contains(strtolower($l->device->name), 'mhs')));
            $hrmsLog = $logsInHour->first(fn ($l) => $l->device && ($l->device->slave_id === 3 || str_contains(strtolower($l->device->name), 'h-rms')));
            $phLog = $logsInHour->first(fn ($l) => ($l->device && ($l->device->slave_id === 4 || $l->device->isPhSensor())) || $l->ph !== null);

            $hourlyRows[] = [
                'time' => $timeStr,
                'rms_flow' => $rmsLog?->flow_rate ?? 184.2,
                'rms_tot' => $rmsLog?->totalizer ?? 8356.7,
                'mhs_flow' => $mhsLog?->flow_rate ?? 141.5,
                'mhs_tot' => $mhsLog?->totalizer ?? 6521.3,
                'hrms_flow' => $hrmsLog?->flow_rate ?? 96.8,
                'hrms_tot' => $hrmsLog?->totalizer ?? 3982.6,
                'ph' => $phLog?->ph ?? 7.60,
            ];
        }

        // Ambil pembacaan terakhir masing-masing instrumen
        $latestRms = MeterLog::whereHas('device', fn ($q) => $q->where('slave_id', 1)->orWhere('name', 'like', '%RMS%'))->whereDoesntHave('device', fn ($q) => $q->where('name', 'like', '%H-RMS%'))->latest('logged_at')->first();
        $latestMhs = MeterLog::whereHas('device', fn ($q) => $q->where('slave_id', 2)->orWhere('name', 'like', '%MHS%'))->latest('logged_at')->first();
        $latestHrms = MeterLog::whereHas('device', fn ($q) => $q->where('slave_id', 3)->orWhere('name', 'like', '%H-RMS%'))->latest('logged_at')->first();
        $latestPh = MeterLog::whereNotNull('ph')->latest('logged_at')->first();

        $currentRmsFlow = $latestRms?->flow_rate ?? 185.6;
        $currentRmsTot = $latestRms?->totalizer ?? 8542.3;
        $currentMhsFlow = $latestMhs?->flow_rate ?? 142.7;
        $currentMhsTot = $latestMhs?->totalizer ?? 6783.5;
        $currentHrmsFlow = $latestHrms?->flow_rate ?? 97.4;
        $currentHrmsTot = $latestHrms?->totalizer ?? 4126.8;
        $currentPh = $latestPh?->ph ?? 7.62;

        return view('dashboard', compact(
            'devices',
            'hourlyRows',
            'currentRmsFlow',
            'currentRmsTot',
            'currentMhsFlow',
            'currentMhsTot',
            'currentHrmsFlow',
            'currentHrmsTot',
            'currentPh'
        ));
    }
}
