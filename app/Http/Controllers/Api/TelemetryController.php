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
        $currentPh = 0.0;
        $voltages = [];
        $pfs = [];
        $freqs = [];
        $waterCount = 0;
        $electricCount = 0;
        $phCount = 0;

        $scadaMeters = [
            'rms' => ['flow_rate' => 0.0, 'totalizer' => 0.0],
            'mhs' => ['flow_rate' => 0.0, 'totalizer' => 0.0],
            'h_rms' => ['flow_rate' => 0.0, 'totalizer' => 0.0],
            'ph' => ['value' => 0.0, 'status' => 'Standby'],
            'pressure' => ['value' => 0.0, 'status' => 'Standby'],
        ];

        $sortedDevices = $devices->sortBy('id')->values();
        $device1 = $sortedDevices->where('slave_id', 1)->first() ?? $sortedDevices->get(0);
        $device2 = $sortedDevices->where('slave_id', 2)->first() ?? $sortedDevices->get(1);
        $device3 = $sortedDevices->where('slave_id', 3)->first() ?? $sortedDevices->get(2);
        $device4 = $sortedDevices->where('slave_id', 4)->first()
            ?? $sortedDevices->first(fn ($d) => $d->isPhSensor() || $d->isPressureSensor())
            ?? $sortedDevices->get(3);

        foreach ($devices as $dev) {
            $readData = $modbusService->readDeviceRegisters($dev);
            $readData['category'] = $dev->category;
            $readData['is_water'] = $dev->isWater();
            $readData['is_flow_meter'] = $dev->isFlowMeter();
            $readData['is_ph'] = $dev->isPhSensor();
            $readData['is_pressure'] = $dev->isPressureSensor();
            $params = $readData['parameters'];

            $isDev1 = $device1 && $dev->id === $device1->id;
            $isDev2 = $device2 && $dev->id === $device2->id;
            $isDev3 = $device3 && $dev->id === $device3->id;
            $isDev4 = $device4 && $dev->id === $device4->id;

            if ($dev->isPressureSensor() || $dev->isPhSensor() || $isDev4) {
                $phCount++;
                $val = (float) ($params['pressure']['value'] ?? $params['ph']['value'] ?? 0);
                $status = ($val >= 1.0 && $val <= 4.0) ? 'Normal' : ($val > 0 ? 'Warning' : 'Standby');
                $scadaMeters['pressure'] = ['value' => $val, 'status' => $status];
                $scadaMeters['ph'] = ['value' => $val, 'status' => $status];
                $currentPh = $val;
            } elseif ($dev->isWater() || $dev->isFlowMeter() || $isDev1 || $isDev2 || $isDev3) {
                $waterCount++;
                $fr = (float) ($params['flow_rate']['value'] ?? 0);
                $tot = (float) ($params['totalizer']['value'] ?? 0);
                $totalWater += $tot;
                $totalFlowRate += $fr;

                if ($isDev1 || $dev->slave_id === 1) {
                    $scadaMeters['rms'] = ['flow_rate' => $fr, 'totalizer' => $tot];
                } elseif ($isDev2 || $dev->slave_id === 2) {
                    $scadaMeters['mhs'] = ['flow_rate' => $fr, 'totalizer' => $tot];
                } elseif ($isDev3 || $dev->slave_id === 3) {
                    $scadaMeters['h_rms'] = ['flow_rate' => $fr, 'totalizer' => $tot];
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
