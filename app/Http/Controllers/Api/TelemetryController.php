<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\ModbusTcpService;
use Illuminate\Http\JsonResponse;

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
        $currentPh = 7.62;
        $voltages = [];
        $pfs = [];
        $freqs = [];
        $waterCount = 0;
        $electricCount = 0;
        $phCount = 0;

        $scadaMeters = [
            'rms' => ['flow_rate' => 185.6, 'totalizer' => 8542.3],
            'mhs' => ['flow_rate' => 142.7, 'totalizer' => 6783.5],
            'h_rms' => ['flow_rate' => 97.4, 'totalizer' => 4126.8],
            'ph' => ['value' => 7.62, 'status' => 'Normal'],
        ];

        foreach ($devices as $dev) {
            $readData = $modbusService->readDeviceRegisters($dev);
            $readData['category'] = $dev->category;
            $readData['is_water'] = $dev->isWater();
            $readData['is_flow_meter'] = $dev->isFlowMeter();
            $readData['is_ph'] = $dev->isPhSensor();
            $params = $readData['parameters'];

            $nameLower = strtolower($dev->name);

            if ($dev->isPhSensor()) {
                $phCount++;
                if (isset($params['ph']['value'])) {
                    $currentPh = (float) $params['ph']['value'];
                    $scadaMeters['ph']['value'] = $currentPh;
                    $scadaMeters['ph']['status'] = ($currentPh >= 7.0 && $currentPh <= 8.0) ? 'Normal' : 'Warning';
                }
            } elseif ($dev->isWater()) {
                $waterCount++;
                $fr = (float) ($params['flow_rate']['value'] ?? 0);
                $tot = (float) ($params['totalizer']['value'] ?? 0);
                $totalWater += $tot;
                $totalFlowRate += $fr;

                if ($dev->slave_id === 1 || (str_contains($nameLower, 'rms') && ! str_contains($nameLower, 'h-rms'))) {
                    $scadaMeters['rms'] = ['flow_rate' => $fr ?: 185.6, 'totalizer' => $tot ?: 8542.3];
                } elseif ($dev->slave_id === 2 || str_contains($nameLower, 'mhs')) {
                    $scadaMeters['mhs'] = ['flow_rate' => $fr ?: 142.7, 'totalizer' => $tot ?: 6783.5];
                } elseif ($dev->slave_id === 3 || str_contains($nameLower, 'h-rms')) {
                    $scadaMeters['h_rms'] = ['flow_rate' => $fr ?: 97.4, 'totalizer' => $tot ?: 4126.8];
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

        return response()->json([
            'success' => true,
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'time_label' => now()->format('H:i:s'),
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
                'online_devices_count' => count($devices),
                'water_devices_count' => $waterCount,
                'ph_devices_count' => $phCount,
                'electric_devices_count' => $electricCount,
            ],
            'scada' => $scadaMeters,
            'devices' => $deviceResults,
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
