<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\MeterLog;
use App\Services\ModbusTcpService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HistoryController extends Controller
{
    /**
     * Tampilkan halaman riwayat data logger meter dengan filter.
     */
    public function index(Request $request)
    {
        $devices = Device::orderBy('name')->get();

        $selectedDeviceId = $request->query('device_id');
        $dateStart = $request->query('date_start', now()->subDay()->format('Y-m-d\TH:i'));
        $dateEnd = $request->query('date_end', now()->format('Y-m-d\TH:i'));

        $query = MeterLog::with('device')->latest('logged_at');

        if ($selectedDeviceId) {
            $query->where('device_id', $selectedDeviceId);
        }

        if ($dateStart) {
            $query->where('logged_at', '>=', $dateStart);
        }

        if ($dateEnd) {
            $query->where('logged_at', '<=', $dateEnd);
        }

        // Hitung ringkasan statistik untuk periode yang difilter
        $statsQuery = clone $query;
        $totalLogs = $statsQuery->count();
        $avgPower = round($statsQuery->avg('power') ?? 0, 2);
        $maxPower = round($statsQuery->max('power') ?? 0, 2);
        $minEnergy = $statsQuery->min('energy') ?? 0;
        $maxEnergy = $statsQuery->max('energy') ?? 0;
        $energyConsumed = round(max(0, $maxEnergy - $minEnergy), 2);
        $totalWaterVolume = round(max(0, ($statsQuery->max('totalizer') ?? 0) - ($statsQuery->min('totalizer') ?? 0)), 2);
        $avgFlowRate = round($statsQuery->avg('flow_rate') ?? 0, 2);

        $logs = $query->paginate(20)->withQueryString();

        // Ambil data tren historis untuk grafik (maks 40 titik data berurutan waktu)
        $chartQuery = (clone $query)->reorder()->oldest('logged_at')->limit(40);
        $chartLogs = $chartQuery->get(['logged_at', 'power', 'current', 'voltage', 'flow_rate', 'totalizer']);
        $chartLabels = $chartLogs->map(fn ($item) => $item->logged_at->format('H:i'))->values();
        $chartPowers = $chartLogs->map(fn ($item) => (float) ($item->power ?? 0))->values();
        $chartCurrents = $chartLogs->map(fn ($item) => (float) ($item->current ?? 0))->values();
        $chartFlowRates = $chartLogs->map(fn ($item) => (float) ($item->flow_rate ?? 0))->values();

        return view('history.index', compact(
            'logs',
            'devices',
            'selectedDeviceId',
            'dateStart',
            'dateEnd',
            'totalLogs',
            'avgPower',
            'maxPower',
            'energyConsumed',
            'totalWaterVolume',
            'avgFlowRate',
            'chartLabels',
            'chartPowers',
            'chartCurrents',
            'chartFlowRates'
        ));
    }

    /**
     * Pemicu pencatatan telemetri secara manual via antarmuka web.
     */
    public function logNow(ModbusTcpService $modbusService)
    {
        $devices = Device::with(['registers' => function ($q) {
            $q->active();
        }])->active()->get();

        if ($devices->isEmpty()) {
            return back()->with('error', 'Tidak ada unit meter aktif untuk dicatat.');
        }

        $now = now();
        $count = 0;
        foreach ($devices as $dev) {
            $readData = $modbusService->readDeviceRegisters($dev, true);
            $params = $readData['parameters'];

            MeterLog::create([
                'device_id' => $dev->id,
                'voltage' => $params['voltage']['value'] ?? null,
                'current' => $params['current']['value'] ?? null,
                'power' => $params['power']['value'] ?? null,
                'frequency' => $params['frequency']['value'] ?? null,
                'power_factor' => $params['power_factor']['value'] ?? null,
                'energy' => $params['energy']['value'] ?? null,
                'totalizer' => $params['totalizer']['value'] ?? null,
                'flow_rate' => $params['flow_rate']['value'] ?? null,
                'raw_parameters' => $params,
                'is_simulated' => $readData['is_simulated'] ?? false,
                'logged_at' => $now,
            ]);
            $count++;
        }

        return back()->with('success', "Berhasil mencatat data telemetri untuk {$count} unit meter ke database.");
    }

    /**
     * Pembersihan log riwayat lama (retensi data).
     */
    public function purgeLogs(Request $request)
    {
        $range = $request->input('range', 'older_30_days');
        $query = MeterLog::query();

        if ($range === 'older_30_days') {
            $query->where('logged_at', '<', now()->subDays(30));
            $label = 'lebih dari 30 hari yang lalu';
        } elseif ($range === 'older_7_days') {
            $query->where('logged_at', '<', now()->subDays(7));
            $label = 'lebih dari 7 hari yang lalu';
        } elseif ($range === 'older_24_hours') {
            $query->where('logged_at', '<', now()->subHours(24));
            $label = 'lebih dari 24 jam yang lalu';
        } elseif ($range === 'all') {
            $label = 'seluruhnya';
        } else {
            return back()->with('error', 'Pilihan rentang waktu tidak valid.');
        }

        $deleted = $query->delete();

        return back()->with('success', "Berhasil menghapus {$deleted} baris data log ({$label}).");
    }

    /**
     * Ekspor data log terpilih ke format file CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $selectedDeviceId = $request->query('device_id');
        $dateStart = $request->query('date_start');
        $dateEnd = $request->query('date_end');

        $query = MeterLog::with('device')->orderBy('logged_at', 'asc');

        if ($selectedDeviceId) {
            $query->where('device_id', $selectedDeviceId);
        }
        if ($dateStart) {
            $query->where('logged_at', '>=', $dateStart);
        }
        if ($dateEnd) {
            $query->where('logged_at', '<=', $dateEnd);
        }

        $filename = 'iot_meter_logs_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // Tambahkan BOM agar karakter UTF-8 terbaca rapi di Microsoft Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header kolom CSV
            fputcsv($handle, [
                'Waktu Log (WIB)',
                'Nama Device Meter',
                'Kategori',
                'Slave ID',
                'IP Gateway',
                'Totalizer Air (m³)',
                'Flow Rate (m³/h)',
                'Tegangan L-N (V)',
                'Arus (A)',
                'Daya Aktif (kW)',
                'Frekuensi (Hz)',
                'Power Factor (PF)',
                'Energi Akumulasi (kWh)',
                'Status Sumber Data',
            ]);

            // Stream data chunking 200 record untuk hemat memory
            $query->chunk(200, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->logged_at->format('Y-m-d H:i:s'),
                        $log->device->name ?? 'Meter Tidak Diketahui',
                        $log->device->category ?? 'meter',
                        $log->device->slave_id ?? '-',
                        $log->device ? "{$log->device->ip_address}:{$log->device->port}" : '-',
                        $log->totalizer !== null ? $log->totalizer : '-',
                        $log->flow_rate !== null ? $log->flow_rate : '-',
                        $log->voltage !== null ? $log->voltage : '-',
                        $log->current !== null ? $log->current : '-',
                        $log->power !== null ? $log->power : '-',
                        $log->frequency !== null ? $log->frequency : '-',
                        $log->power_factor !== null ? $log->power_factor : '-',
                        $log->energy !== null ? $log->energy : '-',
                        $log->is_simulated ? 'SIMULATOR' : 'LIVE MODBUS',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
