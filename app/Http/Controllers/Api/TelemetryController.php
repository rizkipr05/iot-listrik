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

        $devices = Device::with(['registers' => fn ($q) => $q->where('is_active', true)])->active()->get();

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

        $scadaInstruments = Device::resolveScadaInstruments($devices);
        $deviceRms = $scadaInstruments['rms'];
        $deviceMhs = $scadaInstruments['mhs'];
        $deviceHrms = $scadaInstruments['hrms'];
        $devicePh = $scadaInstruments['pressure'];

        $onlineDevicesCount = 0;

        $isLoggerEnabled = AppSetting::getValue('logger_enabled', '1') === '1';
        $loggerInterval = (int) AppSetting::getValue('logger_interval_seconds', '60');
        $loggerInterval = max(5, $loggerInterval);
        $pressureNormalMin = (float) AppSetting::getValue('pressure_normal_min', 1.0);
        $pressureNormalMax = (float) AppSetting::getValue('pressure_normal_max', 4.0);
        $maxRms = (float) AppSetting::getValue('max_flowrate_rms', 4.0);
        $maxMhs = (float) AppSetting::getValue('max_flowrate_mhs', 10.0);
        $maxHrms = (float) AppSetting::getValue('max_flowrate_hrms', 10.0);
        $maxPressure = (float) AppSetting::getValue('max_pressure', 10.0);
        $flowWarningPct = (int) AppSetting::getValue('flow_warning_percent', 85);
        $nowTimestamp = time();

        foreach ($devices as $dev) {
            $readData = $modbusService->readDeviceRegisters($dev);
            $isDevOnline = (bool) ($readData['is_online'] ?? false);
            if (! $isDevOnline && Cache::get("device_online_{$dev->id}", false)) {
                $isDevOnline = true;
            }
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

            $isPhDev = $devicePh ? ($dev->id === $devicePh->id) : ($dev->isPressureSensor() || $dev->isPhSensor());
            $isRmsDev = $deviceRms && $dev->id === $deviceRms->id;
            $isMhsDev = $deviceMhs && $dev->id === $deviceMhs->id;
            $isHrmsDev = $deviceHrms && $dev->id === $deviceHrms->id;

            $frVal = (float) ($params['flow_rate']['value'] ?? 0.0);
            $totVal = (float) ($params['totalizer']['value'] ?? 0.0);

            // Ekstraksi nilai sensor tekanan / pH secara mendalam
            $pressVal = 0.0;
            if ($isPhDev) {
                if ($isDevOnline) {
                    if (isset($params['pressure']['value']) && is_numeric($params['pressure']['value'])) {
                        $pressVal = (float) $params['pressure']['value'];
                    } elseif (isset($params['ph']['value']) && is_numeric($params['ph']['value'])) {
                        $pressVal = (float) $params['ph']['value'];
                    } else {
                        $found = false;
                        foreach ($params as $pKey => $pData) {
                            $unit = strtolower($pData['unit'] ?? '');
                            $k = strtolower((string) $pKey);
                            $pName = strtolower($pData['name'] ?? '');
                            if (in_array($unit, ['bar', 'psi', 'kpa', 'mpa'])
                                || in_array($k, ['pressure', 'press', 'tekanan', 'ph', 'other'])
                                || str_contains($pName, 'tekanan')
                                || str_contains($pName, 'pressure')
                                || str_contains($pName, 'bar')) {
                                if (isset($pData['value']) && is_numeric($pData['value'])) {
                                    $pressVal = (float) $pData['value'];
                                    $found = true;
                                    break;
                                }
                            }
                        }

                        if (! $found) {
                            foreach ($params as $pData) {
                                if (isset($pData['value']) && is_numeric($pData['value'])) {
                                    $pressVal = (float) $pData['value'];
                                    break;
                                }
                            }
                        }
                    }
                } else {
                    // Fallback ke cache sebelumnya jika device sedang offline
                    $prevCached = Cache::get("device_latest_telemetry_{$dev->id}");
                    if (isset($prevCached['pressure']) && is_numeric($prevCached['pressure'])) {
                        $pressVal = (float) $prevCached['pressure'];
                    } elseif (isset($prevCached['ph']) && is_numeric($prevCached['ph'])) {
                        $pressVal = (float) $prevCached['ph'];
                    } else {
                        // Fallback ke rekaman log terakhir database (non-simulated)
                        $lastLogVal = MeterLog::where('device_id', $dev->id)
                            ->where('is_simulated', false)
                            ->whereNotNull('ph')
                            ->latest('logged_at')
                            ->value('ph');
                        if ($lastLogVal !== null && is_numeric($lastLogVal)) {
                            $pressVal = (float) $lastLogVal;
                        }
                    }
                }
            }

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
                        'ph' => $pressVal > 0 ? $pressVal : ($params['pressure']['value'] ?? $params['ph']['value'] ?? null),
                        'raw_parameters' => $params,
                        'is_simulated' => false,
                        'logged_at' => now(),
                    ]);
                    Cache::put($cacheKey, $nowTimestamp, $loggerInterval * 3);
                }
            }

            // Simpan telemetri realtime terakhir ke Cache agar dashboard instan tanpa delay 0 saat navigasi menu
            if ($isDevOnline) {
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
                    $prevCached['flow_rate'] = 0.0;
                    // JANGAN me-reset totalizer, pressure, dan ph ke 0 agar nilai telemetri terakhir tidak hilang saat offline
                    Cache::put("device_latest_telemetry_{$dev->id}", $prevCached, 86400);
                }
            }

            if ($isPhDev) {
                $phCount++;
                $val = $pressVal;
                $status = ! $isDevOnline
                    ? 'Offline'
                    : ($val <= 0
                        ? 'Standby'
                        : (($val >= $pressureNormalMin && $val <= $pressureNormalMax) ? 'Normal' : 'Warning'));
                $scadaMeters['pressure'] = ['value' => $val, 'status' => $status, 'is_online' => $isDevOnline];
                $scadaMeters['ph'] = ['value' => $val, 'status' => $status, 'is_online' => $isDevOnline];
                $currentPh = $val;
            } elseif ($dev->isWater() || $dev->isFlowMeter() || $isRmsDev || $isMhsDev || $isHrmsDev) {
                $waterCount++;
                $fr = $isDevOnline ? (float) ($params['flow_rate']['value'] ?? 0) : 0.0;
                $tot = (float) ($params['totalizer']['value'] ?? 0);

                if (! $isDevOnline) {
                    $prevDevCached = Cache::get("device_latest_telemetry_{$dev->id}");
                    if ($tot <= 0 && ! empty($prevDevCached['totalizer'])) {
                        $tot = (float) $prevDevCached['totalizer'];
                    }
                    $fr = 0.0;
                }

                $totalWater += $tot;
                if ($isDevOnline) {
                    $totalFlowRate += $fr;
                }

                if ($isRmsDev) {
                    $pct = (int) min(100, max(0, round(($fr / max(0.001, $maxRms)) * 100)));
                    $st = ! $isDevOnline ? 'Offline' : ($pct >= $flowWarningPct ? 'Warning' : 'Normal');
                    $scadaMeters['rms'] = ['flow_rate' => $isDevOnline ? $fr : 0.0, 'totalizer' => $tot, 'status' => $st, 'is_online' => $isDevOnline];
                } elseif ($isMhsDev) {
                    $pct = (int) min(100, max(0, round(($fr / max(0.001, $maxMhs)) * 100)));
                    $st = ! $isDevOnline ? 'Offline' : ($pct >= $flowWarningPct ? 'Warning' : 'Normal');
                    $scadaMeters['mhs'] = ['flow_rate' => $isDevOnline ? $fr : 0.0, 'totalizer' => $tot, 'status' => $st, 'is_online' => $isDevOnline];
                } elseif ($isHrmsDev) {
                    $pct = (int) min(100, max(0, round(($fr / max(0.001, $maxHrms)) * 100)));
                    $st = ! $isDevOnline ? 'Offline' : ($pct >= $flowWarningPct ? 'Warning' : 'Normal');
                    $scadaMeters['h_rms'] = ['flow_rate' => $isDevOnline ? $fr : 0.0, 'totalizer' => $tot, 'status' => $st, 'is_online' => $isDevOnline];
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
            usleep(30000);
        }

        $avgVoltage = count($voltages) > 0 ? round(array_sum($voltages) / count($voltages), 1) : 220.0;
        $avgPf = count($pfs) > 0 ? round(array_sum($pfs) / count($pfs), 2) : 0.96;
        $avgFreq = count($freqs) > 0 ? round(array_sum($freqs) / count($freqs), 2) : 50.0;

        if ($devicePh && Cache::get("device_online_{$devicePh->id}", false)) {
            $scadaMeters['pressure']['is_online'] = true;
            $scadaMeters['ph']['is_online'] = true;
        }

        // Fallback untuk Card 4 (Pressure) HANYA jika device sedang OFFLINE
        if (! $scadaMeters['pressure']['is_online']) {
            if ($scadaMeters['pressure']['value'] <= 0) {
                $prevScada = Cache::get('scada_latest_telemetry');
                if (isset($prevScada['pressure']['value']) && is_numeric($prevScada['pressure']['value']) && (float) $prevScada['pressure']['value'] > 0) {
                    $pVal = (float) $prevScada['pressure']['value'];
                    $scadaMeters['pressure']['value'] = $pVal;
                    $scadaMeters['ph']['value'] = $pVal;
                    $currentPh = $pVal;
                } elseif ($devicePh) {
                    $prevDevCache = Cache::get("device_latest_telemetry_{$devicePh->id}");
                    if (isset($prevDevCache['pressure']) && is_numeric($prevDevCache['pressure']) && (float) $prevDevCache['pressure'] > 0) {
                        $pVal = (float) $prevDevCache['pressure'];
                        $scadaMeters['pressure']['value'] = $pVal;
                        $scadaMeters['ph']['value'] = $pVal;
                        $currentPh = $pVal;
                    }
                }

                if ($scadaMeters['pressure']['value'] <= 0 && $devicePh) {
                    $lastLogVal = MeterLog::where('device_id', $devicePh->id)
                        ->where('is_simulated', false)
                        ->whereNotNull('ph')
                        ->latest('logged_at')
                        ->value('ph');
                    if ($lastLogVal !== null && is_numeric($lastLogVal)) {
                        $pVal = (float) $lastLogVal;
                        $scadaMeters['pressure']['value'] = $pVal;
                        $scadaMeters['ph']['value'] = $pVal;
                        $currentPh = $pVal;
                    }
                }
            }
        }

        // Hitung status alarm tekanan (Standby / Normal / Warning) berdasarkan nilai terkini dan rentang yang dikonfigurasi
        $val = (float) $scadaMeters['pressure']['value'];
        $st = ($val <= 0)
            ? 'Standby'
            : (($val >= $pressureNormalMin && $val <= $pressureNormalMax) ? 'Normal' : 'Warning');
        $scadaMeters['pressure']['status'] = $st;
        $scadaMeters['ph']['status'] = $st;

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
                'pressure_normal_min' => $pressureNormalMin,
                'pressure_normal_max' => $pressureNormalMax,
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
