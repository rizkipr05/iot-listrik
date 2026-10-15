<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\MeterLog;
use Illuminate\Database\Seeder;

class MeterLogHistorySeeder extends Seeder
{
    /**
     * Seed riwayat log meter untuk 24 jam terakhir (setiap 30 menit).
     */
    public function run(): void
    {
        $devices = Device::active()->get();
        if ($devices->isEmpty()) {
            return;
        }

        $now = now();
        $records = [];

        // Hasilkan log untuk 24 jam terakhir (48 titik data per meter)
        for ($i = 48; $i >= 0; $i--) {
            $timestamp = $now->copy()->subMinutes($i * 30);
            $hour = (int) $timestamp->format('H');

            // Simulasi variasi beban berdasarkan jam kerja (pukul 08:00 - 18:00 lebih tinggi)
            $isPeak = ($hour >= 8 && $hour <= 18);
            $peakMultiplier = $isPeak ? 1.4 : 0.8;

            foreach ($devices as $device) {
                if ($device->isWater()) {
                    $flowRate = $device->category === 'water_meter' ? null : round(max(0.2, (2.8 + (sin($i / 3) * 1.5)) * $peakMultiplier), 2);
                    $totalizer = round(120.0 + ($device->slave_id * 45) + ((48 - $i) * 0.125), 3);

                    $raw = [
                        'totalizer' => ['value' => $totalizer, 'unit' => 'm³'],
                    ];
                    if ($flowRate !== null) {
                        $raw['flow_rate'] = ['value' => $flowRate, 'unit' => 'm³/h'];
                    }

                    $records[] = [
                        'device_id' => $device->id,
                        'voltage' => null,
                        'current' => null,
                        'power' => null,
                        'frequency' => null,
                        'power_factor' => null,
                        'energy' => null,
                        'totalizer' => $totalizer,
                        'flow_rate' => $flowRate,
                        'raw_parameters' => json_encode($raw),
                        'is_simulated' => true,
                        'logged_at' => $timestamp,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                } else {
                    $basePower = 4.5 + ($device->slave_id * 2.2);
                    $power = round(($basePower + (sin($i / 3) * 1.5)) * $peakMultiplier, 2);
                    $voltage = round(221.0 + (cos($i / 4) * 2.0), 1);
                    $current = round(($power * 1000) / ($voltage * 0.96 * sqrt(3)), 1);
                    $energy = round(1200.0 + ($device->slave_id * 250) + ((48 - $i) * ($power * 0.5)), 1);
                    $pf = round(0.95 + (sin($i) * 0.03), 2);
                    $freq = round(50.00 + (cos($i) * 0.02), 2);

                    $records[] = [
                        'device_id' => $device->id,
                        'voltage' => $voltage,
                        'current' => $current,
                        'power' => $power,
                        'frequency' => $freq,
                        'power_factor' => $pf,
                        'energy' => $energy,
                        'totalizer' => null,
                        'flow_rate' => null,
                        'raw_parameters' => json_encode([
                            'voltage' => ['value' => $voltage, 'unit' => 'V'],
                            'current' => ['value' => $current, 'unit' => 'A'],
                            'power' => ['value' => $power, 'unit' => 'kW'],
                            'energy' => ['value' => $energy, 'unit' => 'kWh'],
                            'frequency' => ['value' => $freq, 'unit' => 'Hz'],
                            'power_factor' => ['value' => $pf, 'unit' => 'PF'],
                        ]),
                        'is_simulated' => true,
                        'logged_at' => $timestamp,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }
            }
        }

        MeterLog::insert($records);
    }
}
