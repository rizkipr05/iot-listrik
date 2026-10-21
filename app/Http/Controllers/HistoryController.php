<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\MeterLog;
use App\Services\ModbusTcpService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HistoryController extends Controller
{
    /**
     * Tampilkan halaman riwayat data logger meter dengan filter.
     */
    public function index(Request $request)
    {
        $devices = Device::orderBy('slave_id')->get();
        foreach ($devices as $dev) {
            $cached = Cache::get("device_latest_telemetry_{$dev->id}");
            $dev->is_online_now = ! empty($cached['is_online']);
        }
        $activeOnlineDevice = $devices->first(fn ($d) => ! empty($d->is_online_now));
        if (! $activeOnlineDevice) {
            $lastRealLog = MeterLog::where('is_simulated', false)->latest('logged_at')->first();
            if ($lastRealLog) {
                $activeOnlineDevice = $devices->firstWhere('id', $lastRealLog->device_id);
            }
        }

        $selectedDeviceId = $request->query('device_id');
        $selectedSource = $request->query('source', 'all'); // 'all', 'real', 'simulated'
        $rawDateStart = $request->query('date_start');
        $rawDateEnd = $request->query('date_end');

        // Untuk input form HTML (datetime-local format: Y-m-d\TH:i)
        $dateStart = $rawDateStart ?: now()->subDay()->format('Y-m-d\TH:i');
        $dateEnd = $rawDateEnd ?: now()->addMinutes(5)->format('Y-m-d\TH:i');

        $query = MeterLog::with('device')->latest('logged_at');

        if ($selectedDeviceId) {
            $query->where('device_id', $selectedDeviceId);
        }

        if ($selectedSource === 'real') {
            $query->where('is_simulated', false);
        } elseif ($selectedSource === 'simulated') {
            $query->where('is_simulated', true);
        }

        if ($rawDateStart) {
            $query->where('logged_at', '>=', Carbon::parse($rawDateStart)->startOfMinute());
        } else {
            $query->where('logged_at', '>=', now()->subDay());
        }

        if ($rawDateEnd) {
            $query->where('logged_at', '<=', Carbon::parse($rawDateEnd)->endOfMinute());
        }

        // Hitung ringkasan statistik untuk periode yang difilter
        $statsQuery = clone $query;
        $totalLogs = $statsQuery->count();

        $selectedDevice = $selectedDeviceId ? $devices->firstWhere('id', (int) $selectedDeviceId) : null;
        $hasPressureSensor = $devices->contains(fn ($d) => $d->isPressureSensor());
        $hasPhSensor = $devices->contains(fn ($d) => $d->isPhSensor());

        // 1. Perhitungan Volume Air Kumulatif (Tanpa mengurangkan antar-device yang berbeda)
        if ($selectedDevice) {
            if ($selectedDevice->isFlowMeter() || $selectedDevice->isWater()) {
                $maxTot = (clone $statsQuery)->max('totalizer');
                $minTot = (clone $statsQuery)->min('totalizer');
                if ($maxTot !== null && $minTot !== null && ($maxTot - $minTot) > 0) {
                    $totalWaterVolume = round($maxTot - $minTot, 2);
                    $volumeLabel = 'Delta Pemakaian Air';
                    $volumeDesc = 'Selisih totalizer '.$selectedDevice->name;
                } elseif ($maxTot !== null) {
                    $totalWaterVolume = round((float) $maxTot, 1);
                    $volumeLabel = 'Totalizer Terkini';
                    $volumeDesc = 'Akumulasi totalizer '.$selectedDevice->name;
                } else {
                    $totalWaterVolume = 0.0;
                    $volumeLabel = 'Volume Totalizer';
                    $volumeDesc = 'Belum ada data tercatat';
                }
            } else {
                $totalWaterVolume = 0.0;
                $volumeLabel = 'Volume Air';
                $volumeDesc = 'Device bertipe sensor tekanan (bukan water meter)';
            }
        } else {
            // Semua meter dipilih: hitung per device agar tidak terjadi anomali selisih antar meter berbeda
            $waterDeviceIds = $devices->filter(fn ($d) => $d->isFlowMeter() || $d->isWater())->pluck('id');
            $deltaSum = 0.0;
            $latestSum = 0.0;
            $hasAnyDelta = false;

            foreach ($waterDeviceIds as $wId) {
                $wQuery = (clone $statsQuery)->where('device_id', $wId);
                $wMax = $wQuery->max('totalizer');
                $wMin = $wQuery->min('totalizer');
                if ($wMax !== null) {
                    $latestSum += (float) $wMax;
                    if ($wMin !== null && ($wMax - $wMin) > 0) {
                        $deltaSum += (float) ($wMax - $wMin);
                        $hasAnyDelta = true;
                    }
                }
            }

            if ($hasAnyDelta && $deltaSum > 0) {
                $totalWaterVolume = round($deltaSum, 2);
                $volumeLabel = 'Delta Volume Kumulatif';
                $volumeDesc = 'Total selisih volume seluruh flowmeter';
            } else {
                $totalWaterVolume = round($latestSum, 1);
                $volumeLabel = 'Volume Air Kumulatif';
                $volumeDesc = 'Akumulasi totalizer seluruh flowmeter';
            }
        }

        // 2. Perhitungan Rata-rata Flowrate
        $flowQuery = (clone $statsQuery)->whereNotNull('flow_rate')->where('flow_rate', '>', 0);
        $avgFlowRate = round($flowQuery->avg('flow_rate') ?? ((clone $statsQuery)->whereNotNull('flow_rate')->avg('flow_rate') ?? 0.0), 2);

        // 3. Perhitungan Tekanan Pipa / Sensor (Kolom ph pada database)
        $pressureQuery = (clone $statsQuery)->whereNotNull('ph');
        if (! $selectedDevice) {
            $pressureDev = $devices->first(fn ($d) => $d->isPressureSensor());
            if ($pressureDev) {
                $pressureQuery->where('device_id', $pressureDev->id);
            }
        }
        $avgPressure = round($pressureQuery->avg('ph') ?? 0.0, 2);
        $minPressure = round($pressureQuery->min('ph') ?? 0.0, 2);
        $maxPressure = round($pressureQuery->max('ph') ?? 0.0, 2);
        $avgPh = $avgPressure; // alias kompatibilitas

        // Listrik (jika ada instrumen power meter)
        $avgPower = round($statsQuery->avg('power') ?? 0, 2);
        $maxPower = round($statsQuery->max('power') ?? 0, 2);
        $energyConsumed = round(max(0, ($statsQuery->max('energy') ?? 0) - ($statsQuery->min('energy') ?? 0)), 2);

        $logs = $query->paginate(20)->withQueryString();

        // Ambil data tren historis untuk grafik (maks 40 titik data berurutan waktu)
        $chartQuery = (clone $query)->reorder()->oldest('logged_at')->limit(40);
        $chartLogs = $chartQuery->get(['logged_at', 'power', 'current', 'voltage', 'flow_rate', 'totalizer', 'ph']);
        $chartLabels = $chartLogs->map(fn ($item) => $item->logged_at->timezone(config('app.timezone', 'Asia/Jakarta'))->format('H:i'))->values();
        $chartPowers = $chartLogs->map(fn ($item) => (float) ($item->power ?? 0))->values();
        $chartCurrents = $chartLogs->map(fn ($item) => (float) ($item->current ?? 0))->values();
        $chartFlowRates = $chartLogs->map(fn ($item) => (float) ($item->flow_rate ?? 0))->values();
        $chartPressures = $chartLogs->map(fn ($item) => (float) ($item->ph ?? 0))->values();
        $chartPhs = $chartPressures;

        return view('history.index', compact(
            'logs',
            'devices',
            'selectedDeviceId',
            'selectedDevice',
            'selectedSource',
            'activeOnlineDevice',
            'dateStart',
            'dateEnd',
            'totalLogs',
            'avgPower',
            'maxPower',
            'energyConsumed',
            'totalWaterVolume',
            'volumeLabel',
            'volumeDesc',
            'avgFlowRate',
            'avgPressure',
            'minPressure',
            'maxPressure',
            'avgPh',
            'hasPressureSensor',
            'hasPhSensor',
            'chartLabels',
            'chartPowers',
            'chartCurrents',
            'chartFlowRates',
            'chartPressures',
            'chartPhs'
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
        $offlineCount = 0;

        foreach ($devices as $dev) {
            // Coba baca langsung dari instrumen fisik via socket Modbus
            $readData = $modbusService->readDeviceRegisters($dev, false);
            $isLiveModbus = ! empty($readData['is_online']) && empty($readData['is_simulated']);

            // Jika socket Modbus sedang sibuk namun device online dan memiliki data realtime di cache dashboard
            $cached = Cache::get("device_latest_telemetry_{$dev->id}");
            $hasFreshCache = $cached && ! empty($cached['is_online']);

            if (! $isLiveModbus && ! $hasFreshCache) {
                $offlineCount++;

                continue;
            }

            if ($isLiveModbus) {
                $params = $readData['parameters'];
                $flowRate = $params['flow_rate']['value'] ?? null;
                $totalizer = $params['totalizer']['value'] ?? null;
                $pressureVal = $params['pressure']['value'] ?? $params['ph']['value'] ?? null;
                $rawParams = $params;
            } else {
                $flowRate = ! empty($cached['flow_rate']) ? (float) $cached['flow_rate'] : null;
                $totalizer = ! empty($cached['totalizer']) ? (float) $cached['totalizer'] : null;
                $pressureVal = ! empty($cached['pressure']) ? (float) $cached['pressure'] : (! empty($cached['ph']) ? (float) $cached['ph'] : null);
                $rawParams = $cached;
            }

            MeterLog::create([
                'device_id' => $dev->id,
                'voltage' => $rawParams['voltage']['value'] ?? $rawParams['voltage'] ?? null,
                'current' => $rawParams['current']['value'] ?? $rawParams['current'] ?? null,
                'power' => $rawParams['power']['value'] ?? $rawParams['power'] ?? null,
                'frequency' => $rawParams['frequency']['value'] ?? $rawParams['frequency'] ?? null,
                'power_factor' => $rawParams['power_factor']['value'] ?? $rawParams['power_factor'] ?? null,
                'energy' => $rawParams['energy']['value'] ?? $rawParams['energy'] ?? null,
                'totalizer' => $totalizer,
                'flow_rate' => $flowRate,
                'ph' => $pressureVal,
                'raw_parameters' => $rawParams,
                'is_simulated' => false,
                'logged_at' => $now,
            ]);
            $count++;
        }

        if ($count === 0) {
            return back()->with('warning', 'Tidak ada data log yang dicatat karena tidak ada meter yang online / merespons saat ini. Sistem tidak mencatat data simulasi dummy.');
        }

        $message = "Berhasil mencatat data telemetri real untuk {$count} meter online ke database.";
        if ($offlineCount > 0) {
            $message .= " ({$offlineCount} meter offline dilewati).";
        }

        return back()->with('success', $message);
    }

    /**
     * Pembersihan log riwayat lama (retensi data).
     */
    public function purgeLogs(Request $request)
    {
        $range = $request->input('range', 'older_30_days');
        $query = MeterLog::query();

        if ($range === 'today') {
            $query->whereDate('logged_at', now()->toDateString());
            $label = 'hari ini';
        } elseif ($range === 'older_30_days') {
            $query->where('logged_at', '<', now()->subDays(30));
            $label = 'lebih dari 30 hari yang lalu';
        } elseif ($range === 'older_7_days') {
            $query->where('logged_at', '<', now()->subDays(7));
            $label = 'lebih dari 7 hari yang lalu';
        } elseif ($range === 'older_24_hours') {
            $query->where('logged_at', '<', now()->subHours(24));
            $label = 'lebih dari 24 jam yang lalu';
        } elseif ($range === 'simulated_only') {
            $query->where('is_simulated', true);
            $label = 'data simulasi (SIM)';
        } elseif ($range === 'all') {
            $label = 'seluruhnya';
        } else {
            return back()->with('error', 'Pilihan rentang waktu tidak valid.');
        }

        $deleted = $query->delete();

        return back()->with('success', "Berhasil menghapus {$deleted} baris data log ({$label}).");
    }

    /**
     * Hapus satu baris log riwayat secara spesifik.
     */
    public function destroy(MeterLog $log)
    {
        $log->delete();

        return back()->with('success', 'Baris data log berhasil dihapus.');
    }

    /**
     * Ekspor data log terpilih ke format file CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $selectedDeviceId = $request->query('device_id');
        $selectedSource = $request->query('source', 'all');
        $dateStart = $request->query('date_start');
        $dateEnd = $request->query('date_end');

        $query = MeterLog::with('device')->orderBy('logged_at', 'asc');

        if ($selectedDeviceId) {
            $query->where('device_id', $selectedDeviceId);
        }
        if ($selectedSource === 'real') {
            $query->where('is_simulated', false);
        } elseif ($selectedSource === 'simulated') {
            $query->where('is_simulated', true);
        }
        if ($dateStart) {
            $query->where('logged_at', '>=', Carbon::parse($dateStart)->startOfMinute());
        }
        if ($dateEnd) {
            $query->where('logged_at', '<=', Carbon::parse($dateEnd)->endOfMinute());
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
                'Nilai pH / Pressure (Bar)',
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
                    $isPressure = $log->device && $log->device->isPressureSensor();
                    fputcsv($handle, [
                        $log->logged_at->timezone(config('app.timezone', 'Asia/Jakarta'))->format('Y-m-d H:i:s'),
                        $log->device->name ?? 'Meter Tidak Diketahui',
                        $log->device->category_badge ?? $log->device->category ?? 'meter',
                        $log->device->slave_id ?? '-',
                        $log->device ? "{$log->device->ip_address}:{$log->device->port}" : '-',
                        $log->totalizer !== null ? $log->totalizer : '-',
                        $log->flow_rate !== null ? $log->flow_rate : '-',
                        $log->ph !== null ? ($log->ph.($isPressure ? ' Bar' : ' pH')) : '-',
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
