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
        $pressureDevice = $devices->first(fn ($d) => $d->slave_id === 4 || $d->isPressureSensor() || $d->isPhSensor());

        $now = now();
        $records = [];

        // Data realistis 8 jam terakhir sesuai skala operasional meter air & sensor tekanan (Bar)
        $hourlyTableData = [
            ['hour_ago' => 0, 'rms_flow' => 1.55, 'rms_tot' => 8542.3, 'mhs_flow' => 0.82, 'mhs_tot' => 6783.5, 'hrms_flow' => 0.52, 'hrms_tot' => 4126.8, 'pressure' => 2.38],
            ['hour_ago' => 1, 'rms_flow' => 1.52, 'rms_tot' => 8540.8, 'mhs_flow' => 0.80, 'mhs_tot' => 6782.7, 'hrms_flow' => 0.50, 'hrms_tot' => 4126.3, 'pressure' => 2.35],
            ['hour_ago' => 2, 'rms_flow' => 1.48, 'rms_tot' => 8539.3, 'mhs_flow' => 0.78, 'mhs_tot' => 6781.9, 'hrms_flow' => 0.49, 'hrms_tot' => 4125.8, 'pressure' => 2.36],
            ['hour_ago' => 3, 'rms_flow' => 1.45, 'rms_tot' => 8537.8, 'mhs_flow' => 0.76, 'mhs_tot' => 6781.1, 'hrms_flow' => 0.47, 'hrms_tot' => 4125.3, 'pressure' => 2.32],
            ['hour_ago' => 4, 'rms_flow' => 1.50, 'rms_tot' => 8536.3, 'mhs_flow' => 0.79, 'mhs_tot' => 6780.3, 'hrms_flow' => 0.51, 'hrms_tot' => 4124.8, 'pressure' => 2.34],
            ['hour_ago' => 5, 'rms_flow' => 1.42, 'rms_tot' => 8534.8, 'mhs_flow' => 0.75, 'mhs_tot' => 6779.5, 'hrms_flow' => 0.48, 'hrms_tot' => 4124.3, 'pressure' => 2.31],
            ['hour_ago' => 6, 'rms_flow' => 1.40, 'rms_tot' => 8533.4, 'mhs_flow' => 0.73, 'mhs_tot' => 6778.8, 'hrms_flow' => 0.46, 'hrms_tot' => 4123.8, 'pressure' => 2.30],
            ['hour_ago' => 7, 'rms_flow' => 1.38, 'rms_tot' => 8532.0, 'mhs_flow' => 0.71, 'mhs_tot' => 6778.1, 'hrms_flow' => 0.45, 'hrms_tot' => 4123.3, 'pressure' => 2.29],
            ['hour_ago' => 8, 'rms_flow' => 1.35, 'rms_tot' => 8530.6, 'mhs_flow' => 0.70, 'mhs_tot' => 6777.4, 'hrms_flow' => 0.44, 'hrms_tot' => 4122.9, 'pressure' => 2.28],
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

            if ($pressureDevice) {
                $isPressure = $pressureDevice->isPressureSensor() || ! $pressureDevice->isPhSensor();
                $paramKey = $isPressure ? 'pressure' : 'ph';
                $paramName = $isPressure ? 'Nilai Tekanan (Pressure)' : 'Nilai pH';
                $paramUnit = $isPressure ? 'Bar' : 'pH';

                $records[] = [
                    'device_id' => $pressureDevice->id,
                    'voltage' => null,
                    'current' => null,
                    'power' => null,
                    'frequency' => null,
                    'power_factor' => null,
                    'energy' => null,
                    'totalizer' => null,
                    'flow_rate' => null,
                    'ph' => $row['pressure'],
                    'raw_parameters' => json_encode([
                        $paramKey => ['name' => $paramName, 'value' => $row['pressure'], 'unit' => $paramUnit],
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
            $rmsF = round(max(0.6, 1.40 + (sin($h) * 0.12)), 2);
            $rmsT = round(8530.6 - (($h - 8) * 1.4), 1);
            $mhsF = round(max(0.3, 0.72 + (cos($h) * 0.08)), 2);
            $mhsT = round(6777.4 - (($h - 8) * 0.7), 1);
            $hrmsF = round(max(0.2, 0.45 + (sin($h) * 0.05)), 2);
            $hrmsT = round(4122.9 - (($h - 8) * 0.4), 1);
            $pressVal = round(2.32 + (sin($h / 4) * 0.06), 2);

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
            if ($pressureDevice) {
                $isPressure = $pressureDevice->isPressureSensor() || ! $pressureDevice->isPhSensor();
                $paramKey = $isPressure ? 'pressure' : 'ph';
                $paramName = $isPressure ? 'Nilai Tekanan (Pressure)' : 'Nilai pH';
                $paramUnit = $isPressure ? 'Bar' : 'pH';

                $records[] = [
                    'device_id' => $pressureDevice->id,
                    'voltage' => null, 'current' => null, 'power' => null, 'frequency' => null, 'power_factor' => null, 'energy' => null,
                    'totalizer' => null, 'flow_rate' => null, 'ph' => $pressVal,
                    'raw_parameters' => json_encode([$paramKey => ['name' => $paramName, 'value' => $pressVal, 'unit' => $paramUnit]]),
                    'is_simulated' => true, 'logged_at' => $timestamp, 'created_at' => $timestamp, 'updated_at' => $timestamp,
                ];
            }
        }

        MeterLog::insert($records);
    }
}
