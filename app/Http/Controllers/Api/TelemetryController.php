<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Device;
use App\Models\MeterLog;
use App\Services\ModbusTcpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class TelemetryController extends Controller
{
    /**
     * Endpoint API untuk data telemetri real-time seluruh device.
     */
    public function getLiveTelemetry(ModbusTcpService $modbusService): JsonResponse
    {
        // Segera simpan dan lepas lock sesi pengguna agar navigasi halaman web lainnya tidak tertahan
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        if (request()->hasSession()) {
            request()->session()->save();
        }

        $devices = Device::active()->get();

        $deviceResults = [];
        $totalPower = 0.0;
        $totalCurrent = 0.0;
        $totalEnergy = 0.0;
        $totalWater = 0.0;
        $totalFlowRate = 0.0;
        $currentPh = 0.0;
        $voltages = [];
        $pfs = [];
        $freqs = [];
        $waterCount = 0;
        $electricCount = 0;
        $phCount = 0;

        $scadaMeters = [
            'rms' => ['flow_rate' => 0.0, 'totalizer' => 0.0, 'is_online' => false],
            'mhs' => ['flow_rate' => 0.0, 'totalizer' => 0.0, 'is_online' => false],
            'h_rms' => ['flow_rate' => 0.0, 'totalizer' => 0.0, 'is_online' => false],
            'ph' => ['value' => 0.0, 'status' => 'Standby', 'is_online' => false],
            'pressure' => ['value' => 0.0, 'status' => 'Standby', 'is_online' => false],
        ];

        $sortedDevices = $devices->sortBy('id')->values();
        $device1 = $sortedDevices->where('slave_id', 1)->first() ?? $sortedDevices->get(0);
        $device2 = $sortedDevices->where('slave_id', 2)->first() ?? $sortedDevices->get(1);
        $device3 = $sortedDevices->where('slave_id', 3)->first() ?? $sortedDevices->get(2);
        $device4 = $sortedDevices->where('slave_id', 4)->first()
            ?? $sortedDevices->first(fn ($d) => $d->isPhSensor() || $d->isPressureSensor())
            ?? $sortedDevices->get(3);

        $onlineDevicesCount = 0;

        $isLoggerEnabled = AppSetting::getValue('logger_enabled', '1') === '1';
        $loggerInterval = (int) AppSetting::getValue('logger_interval_seconds', '60');
        $loggerInterval = max(5, $loggerInterval);
        $nowTimestamp = time();

        foreach ($devices as $dev) {
            $readData = $modbusService->readDeviceRegisters($dev);
            $isDevOnline = (bool) ($readData['is_online'] ?? false);
            $isSimulated = (bool) ($readData['is_simulated'] ?? false);
            if ($isDevOnline) {
                $onlineDevicesCount++;
            }

            $readData['category'] = $dev->category;
            $readData['is_water'] = $dev->isWater();
            $readData['is_flow_meter'] = $dev->isFlowMeter();
            $readData['is_ph'] = $dev->isPhSensor();
            $readData['is_pressure'] = $dev->isPressureSensor();
            $params = $readData['parameters'];

            // Pencatatan otomatis ke meter_logs saat device online terhubung fisik (bukan simulasi)
            if ($isLoggerEnabled && $isDevOnline && ! $isSimulated) {
                $cacheKey = "auto_logger_last_time_{$dev->id}";
                $lastLogTime = (int) Cache::get($cacheKey, 0);

                if (($nowTimestamp - $lastLogTime) >= $loggerInterval) {
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
                        'ph' => $params['pressure']['value'] ?? $params['ph']['value'] ?? null,
                        'raw_parameters' => $params,
                        'is_simulated' => false,
                        'logged_at' => now(),
                    ]);
                    Cache::put($cacheKey, $nowTimestamp, $loggerInterval * 3);
                }
            }

            // Simpan telemetri realtime terakhir ke Cache agar dashboard instan tanpa delay 0 saat navigasi menu
            if ($isDevOnline) {
                $frVal = (float) ($params['flow_rate']['value'] ?? 0.0);
                $totVal = (float) ($params['totalizer']['value'] ?? 0.0);
                $pressVal = (float) ($params['pressure']['value'] ?? $params['ph']['value'] ?? 0.0);

                $prevCached = Cache::get("device_latest_telemetry_{$dev->id}");
                if ($frVal <= 0 && ! empty($prevCached['flow_rate'])) {
                    $frVal = (float) $prevCached['flow_rate'];
                }
                if ($totVal <= 0 && ! empty($prevCached['totalizer'])) {
                    $totVal = (float) $prevCached['totalizer'];
                }
                if ($pressVal <= 0 && ! empty($prevCached['pressure'])) {
                    $pressVal = (float) $prevCached['pressure'];
                }

                Cache::put("device_latest_telemetry_{$dev->id}", [
                    'is_online' => true,
                    'flow_rate' => $frVal,
                    'totalizer' => $totVal,
                    'pressure' => $pressVal,
                    'ph' => $pressVal,
                    'voltage' => (float) ($params['voltage']['value'] ?? 0.0),
                    'current' => (float) ($params['current']['value'] ?? 0.0),
                    'power' => (float) ($params['power']['value'] ?? 0.0),
                    'energy' => (float) ($params['energy']['value'] ?? 0.0),
                    'power_factor' => (float) ($params['power_factor']['value'] ?? 0.0),
                    'frequency' => (float) ($params['frequency']['value'] ?? 0.0),
                    'updated_at' => now()->toIso8601String(),
                ], 86400);
            } else {
                $prevCached = Cache::get("device_latest_telemetry_{$dev->id}");
                if ($prevCached) {
                    $prevCached['is_online'] = false;
                    Cache::put("device_latest_telemetry_{$dev->id}", $prevCached, 86400);
                }
            }

            $isDev1 = $device1 && $dev->id === $device1->id;
            $isDev2 = $device2 && $dev->id === $device2->id;
            $isDev3 = $device3 && $dev->id === $device3->id;
            $isDev4 = $device4 && $dev->id === $device4->id;

            if ($dev->isPressureSensor() || $dev->isPhSensor() || $isDev4) {
                $phCount++;
                $val = (float) ($params['pressure']['value'] ?? $params['ph']['value'] ?? 0);
                if (! $isDevOnline) {
                    $prevDevCached = Cache::get("device_latest_telemetry_{$dev->id}");
                    if ($val <= 0 && ! empty($prevDevCached['pressure'])) {
                        $val = (float) $prevDevCached['pressure'];
                    }
                }
                $status = ($val >= 1.0 && $val <= 4.0) ? 'Normal' : ($val > 0 ? 'Warning' : 'Standby');
                $scadaMeters['pressure'] = ['value' => $val, 'status' => $status, 'is_online' => $isDevOnline];
                $scadaMeters['ph'] = ['value' => $val, 'status' => $status, 'is_online' => $isDevOnline];
                $currentPh = $val;
            } elseif ($dev->isWater() || $dev->isFlowMeter() || $isDev1 || $isDev2 || $isDev3) {
                $waterCount++;
                $fr = (float) ($params['flow_rate']['value'] ?? 0);
                $tot = (float) ($params['totalizer']['value'] ?? 0);

                if (! $isDevOnline) {
                    $prevDevCached = Cache::get("device_latest_telemetry_{$dev->id}");
                    if ($tot <= 0 && ! empty($prevDevCached['totalizer'])) {
                        $tot = (float) $prevDevCached['totalizer'];
                    }
                    if ($fr <= 0 && ! empty($prevDevCached['flow_rate'])) {
                        $fr = (float) $prevDevCached['flow_rate'];
                    }
                }

                $totalWater += $tot;
                $totalFlowRate += $fr;

                if ($isDev1 || $dev->slave_id === 1) {
                    $scadaMeters['rms'] = ['flow_rate' => $fr, 'totalizer' => $tot, 'is_online' => $isDevOnline];
                } elseif ($isDev2 || $dev->slave_id === 2) {
                    $scadaMeters['mhs'] = ['flow_rate' => $fr, 'totalizer' => $tot, 'is_online' => $isDevOnline];
                } elseif ($isDev3 || $dev->slave_id === 3) {
                    $scadaMeters['h_rms'] = ['flow_rate' => $fr, 'totalizer' => $tot, 'is_online' => $isDevOnline];
                }
            } else {
                $electricCount++;
                $powerVal = $params['power']['value'] ?? 0;
                $currentVal = $params['current']['value'] ?? 0;
                $voltageVal = $params['voltage']['value'] ?? 0;
                $energyVal = $params['energy']['value'] ?? 0;
                $pfVal = $params['power_factor']['value'] ?? 0.96;
                $freqVal = $params['frequency']['value'] ?? 50.0;

                $totalPower += $powerVal;
                $totalCurrent += $currentVal;
                $totalEnergy += $energyVal;

                if ($voltageVal > 0) {
                    $voltages[] = $voltageVal;
                }
                if ($pfVal > 0) {
                    $pfs[] = $pfVal;
                }
                if ($freqVal > 0) {
                    $freqs[] = $freqVal;
                }
            }

            $deviceResults[] = $readData;
        }

        $avgVoltage = count($voltages) > 0 ? round(array_sum($voltages) / count($voltages), 1) : 220.0;
        $avgPf = count($pfs) > 0 ? round(array_sum($pfs) / count($pfs), 2) : 0.96;
        $avgFreq = count($freqs) > 0 ? round(array_sum($freqs) / count($freqs), 2) : 50.0;

        // Simpan scada telemetri & ringkasan ke Cache agar dashboard instan saat navigasi menu
        Cache::put('scada_latest_telemetry', $scadaMeters, 86400);
        Cache::put('scada_latest_summary', [
            'total_power_kw' => round($totalPower, 2),
            'avg_voltage_v' => $avgVoltage,
            'total_current_a' => round($totalCurrent, 1),
            'total_energy_kwh' => round($totalEnergy, 1),
            'avg_power_factor' => $avgPf,
            'grid_frequency_hz' => $avgFreq,
            'total_water_m3' => round($totalWater, 3),
            'total_flow_rate_m3h' => round($totalFlowRate, 2),
            'current_ph' => round($currentPh, 2),
            'online_devices_count' => $onlineDevicesCount,
            'water_devices_count' => $waterCount,
            'ph_devices_count' => $phCount,
            'electric_devices_count' => $electricCount,
        ], 86400);

        $primaryDevice = $devices->first() ?? Device::first();
        $primaryGatewayIp = $primaryDevice?->ip_address ?? '192.168.1.118';
        $primaryGatewayPort = $primaryDevice?->port ?? 502;
        $isGatewayOnline = $primaryDevice ? $modbusService->isGatewayReachable($primaryGatewayIp, $primaryGatewayPort) : false;

        $maxRms = (float) AppSetting::getValue('max_flowrate_rms', 4.0);
        $maxMhs = (float) AppSetting::getValue('max_flowrate_mhs', 10.0);
        $maxHrms = (float) AppSetting::getValue('max_flowrate_hrms', 10.0);
        $maxPressure = (float) AppSetting::getValue('max_pressure', 10.0);
        $flowWarningPct = (int) AppSetting::getValue('flow_warning_percent', 85);

        return response()->json([
            'success' => true,
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'time_label' => now()->format('H:i:s'),
            'gateway' => [
                'ip_address' => $primaryGatewayIp,
                'port' => $primaryGatewayPort,
                'is_online' => $isGatewayOnline,
                'status_label' => $isGatewayOnline ? 'ONLINE' : 'OFFLINE',
            ],
            'scales' => [
                'rms_max' => $maxRms,
                'mhs_max' => $maxMhs,
                'hrms_max' => $maxHrms,
                'pressure_max' => $maxPressure,
                'warning_percent' => $flowWarningPct,
            ],
            'summary' => [
                'total_power_kw' => round($totalPower, 2),
                'avg_voltage_v' => $avgVoltage,
                'total_current_a' => round($totalCurrent, 1),
                'total_energy_kwh' => round($totalEnergy, 1),
                'avg_power_factor' => $avgPf,
                'grid_frequency_hz' => $avgFreq,
                'total_water_m3' => round($totalWater, 3),
                'total_flow_rate_m3h' => round($totalFlowRate, 2),
                'current_ph' => round($currentPh, 2),
                'online_devices_count' => $onlineDevicesCount,
                'water_devices_count' => $waterCount,
                'ph_devices_count' => $phCount,
                'electric_devices_count' => $electricCount,
            ],
            'scada' => $scadaMeters,
            'devices' => $deviceResults,
        ]);
    }

    /**
     * Endpoint API status koneksi Gateway Modbus RS485-to-TCP.
     */
    public function getGatewayStatus(ModbusTcpService $modbusService): JsonResponse
    {
        $primaryDevice = Device::active()->first() ?? Device::first();
        $ip = $primaryDevice?->ip_address ?? '192.168.1.118';
        $port = $primaryDevice?->port ?? 502;
        $isOnline = $primaryDevice ? $modbusService->isGatewayReachable($ip, $port) : false;

        return response()->json([
            'success' => true,
            'gateway' => [
                'ip_address' => $ip,
                'port' => $port,
                'is_online' => $isOnline,
                'status_label' => $isOnline ? 'ONLINE' : 'OFFLINE',
            ],
        ]);
    }

    /**
     * Endpoint API membaca satu device tertentu.
     */
    public function readDevice(Device $device, ModbusTcpService $modbusService): JsonResponse
    {
        $result = $modbusService->readDeviceRegisters($device);

        return response()->json([
            'success' => true,
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'data' => $result,
        ]);
    }
}
