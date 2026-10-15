<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\MeterLog;
use Illuminate\Database\Seeder;

class MeterLogHistorySeeder extends Seeder
{
    /**
     * Seed riwayat log meter SCADA untuk 24 jam terakhir.
     */
    public function run(): void
    {
        $devices = Device::active()->get();
        if ($devices->isEmpty()) {
            return;
        }

        $rmsDevice = $devices->first(fn ($d) => $d->slave_id === 1 || str_contains(strtolower($d->name), 'rms') && ! str_contains(strtolower($d->name), 'h-rms'));
        $mhsDevice = $devices->first(fn ($d) => $d->slave_id === 2 || str_contains(strtolower($d->name), 'mhs'));
        $hrmsDevice = $devices->first(fn ($d) => $d->slave_id === 3 || str_contains(strtolower($d->name), 'h-rms'));
        $phDevice = $devices->first(fn ($d) => $d->slave_id === 4 || $d->isPhSensor());

        $now = now();
        $records = [];

        // Data presisi 8 jam terakhir persis tabel di gambar dashboard
        $hourlyTableData = [
            ['hour_ago' => 0, 'rms_flow' => 185.6, 'rms_tot' => 8542.3, 'mhs_flow' => 142.7, 'mhs_tot' => 6783.5, 'hrms_flow' => 97.4, 'hrms_tot' => 4126.8, 'ph' => 7.62],
            ['hour_ago' => 1, 'rms_flow' => 184.2, 'rms_tot' => 8356.7, 'mhs_flow' => 141.5, 'mhs_tot' => 6521.3, 'hrms_flow' => 96.8, 'hrms_tot' => 3982.6, 'ph' => 7.60],
            ['hour_ago' => 2, 'rms_flow' => 182.7, 'rms_tot' => 8337.8, 'mhs_flow' => 139.8, 'mhs_tot' => 6379.6, 'hrms_flow' => 94.6, 'hrms_tot' => 3885.2, 'ph' => 7.58],
            ['hour_ago' => 3, 'rms_flow' => 176.3, 'rms_tot' => 8319.1, 'mhs_flow' => 135.2, 'mhs_tot' => 6239.8, 'hrms_flow' => 90.7, 'hrms_tot' => 3790.5, 'ph' => 7.55],
            ['hour_ago' => 4, 'rms_flow' => 169.8, 'rms_tot' => 8142.7, 'mhs_flow' => 131.6, 'mhs_tot' => 6104.6, 'hrms_flow' => 87.3, 'hrms_tot' => 3699.4, 'ph' => 7.52],
            ['hour_ago' => 5, 'rms_flow' => 160.4, 'rms_tot' => 7986.2, 'mhs_flow' => 125.7, 'mhs_tot' => 5973.1, 'hrms_flow' => 82.9, 'hrms_tot' => 3612.0, 'ph' => 7.48],
            ['hour_ago' => 6, 'rms_flow' => 152.7, 'rms_tot' => 7829.5, 'mhs_flow' => 118.9, 'mhs_tot' => 5842.7, 'hrms_flow' => 79.6, 'hrms_tot' => 3529.3, 'ph' => 7.45],
            ['hour_ago' => 7, 'rms_flow' => 148.1, 'rms_tot' => 7674.8, 'mhs_flow' => 114.2, 'mhs_tot' => 5718.9, 'hrms_flow' => 76.8, 'hrms_tot' => 3451.7, 'ph' => 7.42],
            ['hour_ago' => 8, 'rms_flow' => 142.5, 'rms_tot' => 7521.3, 'mhs_flow' => 109.7, 'mhs_tot' => 5602.6, 'hrms_flow' => 73.5, 'hrms_tot' => 3378.2, 'ph' => 7.39],
        ];

        foreach ($hourlyTableData as $row) {
            $timestamp = $now->copy()->subHours($row['hour_ago'])->setMinute(0)->setSecond(0);

            if ($rmsDevice) {
                $records[] = [
                    'device_id' => $rmsDevice->id,
                    'voltage' => null,
                    'current' => null,
                    'power' => null,
                    'frequency' => null,
                    'power_factor' => null,
                    'energy' => null,
                    'totalizer' => $row['rms_tot'],
                    'flow_rate' => $row['rms_flow'],
                    'ph' => null,
                    'raw_parameters' => json_encode([
                        'flow_rate' => ['name' => 'Laju Aliran (Flowrate)', 'value' => $row['rms_flow'], 'unit' => 'm³/h'],
                        'totalizer' => ['name' => 'Totality (Totalizer)', 'value' => $row['rms_tot'], 'unit' => 'm³'],
                    ]),
                    'is_simulated' => true,
                    'logged_at' => $timestamp,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            if ($mhsDevice) {
                $records[] = [
                    'device_id' => $mhsDevice->id,
                    'voltage' => null,
                    'current' => null,
                    'power' => null,
                    'frequency' => null,
                    'power_factor' => null,
                    'energy' => null,
                    'totalizer' => $row['mhs_tot'],
                    'flow_rate' => $row['mhs_flow'],
                    'ph' => null,
                    'raw_parameters' => json_encode([
                        'flow_rate' => ['name' => 'Laju Aliran (Flowrate)', 'value' => $row['mhs_flow'], 'unit' => 'm³/h'],
                        'totalizer' => ['name' => 'Totality (Totalizer)', 'value' => $row['mhs_tot'], 'unit' => 'm³'],
                    ]),
                    'is_simulated' => true,
                    'logged_at' => $timestamp,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            if ($hrmsDevice) {
                $records[] = [
                    'device_id' => $hrmsDevice->id,
                    'voltage' => null,
                    'current' => null,
                    'power' => null,
                    'frequency' => null,
                    'power_factor' => null,
                    'energy' => null,
                    'totalizer' => $row['hrms_tot'],
                    'flow_rate' => $row['hrms_flow'],
                    'ph' => null,
                    'raw_parameters' => json_encode([
                        'flow_rate' => ['name' => 'Laju Aliran (Flowrate)', 'value' => $row['hrms_flow'], 'unit' => 'm³/h'],
                        'totalizer' => ['name' => 'Totality (Totalizer)', 'value' => $row['hrms_tot'], 'unit' => 'm³'],
                    ]),
                    'is_simulated' => true,
                    'logged_at' => $timestamp,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            if ($phDevice) {
                $records[] = [
                    'device_id' => $phDevice->id,
                    'voltage' => null,
                    'current' => null,
                    'power' => null,
                    'frequency' => null,
                    'power_factor' => null,
                    'energy' => null,
                    'totalizer' => null,
                    'flow_rate' => null,
                    'ph' => $row['ph'],
                    'raw_parameters' => json_encode([
                        'ph' => ['name' => 'Nilai pH', 'value' => $row['ph'], 'unit' => 'pH'],
                    ]),
                    'is_simulated' => true,
                    'logged_at' => $timestamp,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        // Tambahkan sisa 16 jam sebelumnya (total 24 jam)
        for ($h = 9; $h <= 24; $h++) {
            $timestamp = $now->copy()->subHours($h)->setMinute(0)->setSecond(0);
            $factor = 1.0 - ($h * 0.015);
            $rmsF = round(max(110.0, 140.0 * $factor + (sin($h) * 10)), 1);
            $rmsT = round(7521.3 - (($h - 8) * 80), 1);
            $mhsF = round(max(90.0, 105.0 * $factor + (cos($h) * 8)), 1);
            $mhsT = round(5602.6 - (($h - 8) * 60), 1);
            $hrmsF = round(max(60.0, 72.0 * $factor + (sin($h) * 6)), 1);
            $hrmsT = round(3378.2 - (($h - 8) * 45), 1);
            $phVal = round(7.38 + (sin($h / 4) * 0.08), 2);

            if ($rmsDevice) {
                $records[] = [
                    'device_id' => $rmsDevice->id,
                    'voltage' => null, 'current' => null, 'power' => null, 'frequency' => null, 'power_factor' => null, 'energy' => null,
                    'totalizer' => $rmsT, 'flow_rate' => $rmsF, 'ph' => null,
                    'raw_parameters' => json_encode(['flow_rate' => ['value' => $rmsF, 'unit' => 'm³/h'], 'totalizer' => ['value' => $rmsT, 'unit' => 'm³']]),
                    'is_simulated' => true, 'logged_at' => $timestamp, 'created_at' => $timestamp, 'updated_at' => $timestamp,
                ];
            }
            if ($mhsDevice) {
                $records[] = [
                    'device_id' => $mhsDevice->id,
                    'voltage' => null, 'current' => null, 'power' => null, 'frequency' => null, 'power_factor' => null, 'energy' => null,
                    'totalizer' => $mhsT, 'flow_rate' => $mhsF, 'ph' => null,
                    'raw_parameters' => json_encode(['flow_rate' => ['value' => $mhsF, 'unit' => 'm³/h'], 'totalizer' => ['value' => $mhsT, 'unit' => 'm³']]),
                    'is_simulated' => true, 'logged_at' => $timestamp, 'created_at' => $timestamp, 'updated_at' => $timestamp,
                ];
            }
            if ($hrmsDevice) {
                $records[] = [
                    'device_id' => $hrmsDevice->id,
                    'voltage' => null, 'current' => null, 'power' => null, 'frequency' => null, 'power_factor' => null, 'energy' => null,
                    'totalizer' => $hrmsT, 'flow_rate' => $hrmsF, 'ph' => null,
                    'raw_parameters' => json_encode(['flow_rate' => ['value' => $hrmsF, 'unit' => 'm³/h'], 'totalizer' => ['value' => $hrmsT, 'unit' => 'm³']]),
                    'is_simulated' => true, 'logged_at' => $timestamp, 'created_at' => $timestamp, 'updated_at' => $timestamp,
                ];
            }
            if ($phDevice) {
                $records[] = [
                    'device_id' => $phDevice->id,
                    'voltage' => null, 'current' => null, 'power' => null, 'frequency' => null, 'power_factor' => null, 'energy' => null,
                    'totalizer' => null, 'flow_rate' => null, 'ph' => $phVal,
                    'raw_parameters' => json_encode(['ph' => ['value' => $phVal, 'unit' => 'pH']]),
                    'is_simulated' => true, 'logged_at' => $timestamp, 'created_at' => $timestamp, 'updated_at' => $timestamp,
                ];
            }
        }

        MeterLog::insert($records);
    }
}
