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

        // Ambil device dari database berdasarkan slave_id/kategori untuk label dan data dinamis
        $deviceRms = Device::where('slave_id', 1)->active()->first()
            ?? Device::where('name', 'like', '%RMS%')->where('name', 'not like', '%H-RMS%')->active()->first();
        $deviceMhs = Device::where('slave_id', 2)->active()->first()
            ?? Device::where('name', 'like', '%MHS%')->active()->first();
        $deviceHrms = Device::where('slave_id', 3)->active()->first()
            ?? Device::where('name', 'like', '%H-RMS%')->active()->first();
        $devicePh = Device::where('slave_id', 4)->active()->first()
            ?? Device::whereNotNull('category')->where('category', 'ph_sensor')->active()->first();

        $labelRms = $deviceRms?->name ?? 'Flowmeter 2" Area Chiller';
        $labelMhs = $deviceMhs?->name ?? 'Water meter 3/4" Area Toilet';
        $labelHrms = $deviceHrms?->name ?? 'Water meter 1/2" Area Office';
        $labelPh = $devicePh?->name ?? 'Pressure Sensor';

        $descRms = $deviceRms?->description ?: 'Nominal Size : 2 inch | Range : 0 ~ 300 m³/h';
        $descMhs = $deviceMhs?->description ?: 'Nominal Size : 3/4 inch | Range : 0 ~ 300 m³/h';
        $descHrms = $deviceHrms?->description ?: 'Nominal Size : 1/2 inch | Range : 0 ~ 300 m³/h';
        $descPh = $devicePh?->description ?: 'Range : 0 ~ 10 bar';

        // Ambil riwayat log per jam untuk tabel HISTORY DATA
        $recentLogs = MeterLog::with('device')
            ->latest('logged_at')
            ->take(100)
            ->get();

        // Kelompokkan per jam
        $hourlyRows = [];
        $groupedByHour = $recentLogs->groupBy(fn ($log) => $log->logged_at->format('Y-m-d H:00'));

        foreach ($groupedByHour->take(8) as $timeStr => $logsInHour) {
            $rmsLog = $logsInHour->first(fn ($l) => $l->device && (
                ($deviceRms && $l->device_id === $deviceRms->id)
                || $l->device->slave_id === 1
                || (str_contains(strtolower($l->device->name), 'rms') && ! str_contains(strtolower($l->device->name), 'h-rms'))
            ));

            $mhsLog = $logsInHour->first(fn ($l) => $l->device && (
                ($deviceMhs && $l->device_id === $deviceMhs->id)
                || $l->device->slave_id === 2
                || str_contains(strtolower($l->device->name), 'mhs')
            ));

            $hrmsLog = $logsInHour->first(fn ($l) => $l->device && (
                ($deviceHrms && $l->device_id === $deviceHrms->id)
                || $l->device->slave_id === 3
                || str_contains(strtolower($l->device->name), 'h-rms')
            ));

            $phLog = $logsInHour->first(fn ($l) => (
                ($devicePh && $l->device_id === $devicePh->id)
                || ($l->device && ($l->device->slave_id === 4 || $l->device->isPhSensor()))
                || $l->ph !== null
            ));

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
        $latestRms = $deviceRms
            ? MeterLog::where('device_id', $deviceRms->id)->latest('logged_at')->first()
            : MeterLog::whereHas('device', fn ($q) => $q->where('slave_id', 1)->orWhere('name', 'like', '%RMS%'))->whereDoesntHave('device', fn ($q) => $q->where('name', 'like', '%H-RMS%'))->latest('logged_at')->first();

        $latestMhs = $deviceMhs
            ? MeterLog::where('device_id', $deviceMhs->id)->latest('logged_at')->first()
            : MeterLog::whereHas('device', fn ($q) => $q->where('slave_id', 2)->orWhere('name', 'like', '%MHS%'))->latest('logged_at')->first();

        $latestHrms = $deviceHrms
            ? MeterLog::where('device_id', $deviceHrms->id)->latest('logged_at')->first()
            : MeterLog::whereHas('device', fn ($q) => $q->where('slave_id', 3)->orWhere('name', 'like', '%H-RMS%'))->latest('logged_at')->first();

        $latestPh = $devicePh
            ? MeterLog::where('device_id', $devicePh->id)->latest('logged_at')->first()
            : MeterLog::whereNotNull('ph')->latest('logged_at')->first();

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
            'currentPh',
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
}
