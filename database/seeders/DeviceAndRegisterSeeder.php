<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Register;
use Illuminate\Database\Seeder;

class DeviceAndRegisterSeeder extends Seeder
{
    /**
     * Seed 4 perangkat meter SCADA: 3 Flowmeter & 1 pH Sensor.
     */
    public function run(): void
    {
        $hardwareDevices = [
            [
                'device' => [
                    'name' => 'FLOWMETER 8" - AREA RMS',
                    'category' => 'flow_meter',
                    'description' => 'Electromagnetic Flowmeter 8 inch - Area RMS (Nominal Size: 8 inch | Range: 0 ~ 300 m³/h)',
                    'ip_address' => '192.168.1.100',
                    'port' => 502,
                    'slave_id' => 1,
                    'timeout_ms' => 1500,
                    'is_active' => true,
                ],
                'registers' => [
                    [
                        'name' => 'Laju Aliran / Flowrate (m³/h)',
                        'parameter_key' => 'flow_rate',
                        'register_address' => 0,
                        'function_code' => 3,
                        'data_type' => 'float32',
                        'byte_order' => 'CDAB',
                        'multiplier' => 1.0,
                        'unit' => 'm³/h',
                    ],
                    [
                        'name' => 'Totality / Totalizer Akumulatif (m³)',
                        'parameter_key' => 'totalizer',
                        'register_address' => 2,
                        'function_code' => 3,
                        'data_type' => 'float32',
                        'byte_order' => 'CDAB',
                        'multiplier' => 1.0,
                        'unit' => 'm³',
                    ],
                ],
            ],
            [
                'device' => [
                    'name' => 'FLOWMETER 8" - AREA MHS',
                    'category' => 'flow_meter',
                    'description' => 'Electromagnetic Flowmeter 8 inch - Area MHS (Nominal Size: 8 inch | Range: 0 ~ 300 m³/h)',
                    'ip_address' => '192.168.1.100',
                    'port' => 502,
                    'slave_id' => 2,
                    'timeout_ms' => 1500,
                    'is_active' => true,
                ],
                'registers' => [
                    [
                        'name' => 'Laju Aliran / Flowrate (m³/h)',
                        'parameter_key' => 'flow_rate',
                        'register_address' => 0,
                        'function_code' => 3,
                        'data_type' => 'float32',
                        'byte_order' => 'CDAB',
                        'multiplier' => 1.0,
                        'unit' => 'm³/h',
                    ],
                    [
                        'name' => 'Totality / Totalizer Akumulatif (m³)',
                        'parameter_key' => 'totalizer',
                        'register_address' => 2,
                        'function_code' => 3,
                        'data_type' => 'float32',
                        'byte_order' => 'CDAB',
                        'multiplier' => 1.0,
                        'unit' => 'm³',
                    ],
                ],
            ],
            [
                'device' => [
                    'name' => 'FLOWMETER 8" - AREA H-RMS',
                    'category' => 'flow_meter',
                    'description' => 'Electromagnetic Flowmeter 8 inch - Area H-RMS (Nominal Size: 8 inch | Range: 0 ~ 300 m³/h)',
                    'ip_address' => '192.168.1.100',
                    'port' => 502,
                    'slave_id' => 3,
                    'timeout_ms' => 1500,
                    'is_active' => true,
                ],
                'registers' => [
                    [
                        'name' => 'Laju Aliran / Flowrate (m³/h)',
                        'parameter_key' => 'flow_rate',
                        'register_address' => 0,
                        'function_code' => 3,
                        'data_type' => 'float32',
                        'byte_order' => 'CDAB',
                        'multiplier' => 1.0,
                        'unit' => 'm³/h',
                    ],
                    [
                        'name' => 'Totality / Totalizer Akumulatif (m³)',
                        'parameter_key' => 'totalizer',
                        'register_address' => 2,
                        'function_code' => 3,
                        'data_type' => 'float32',
                        'byte_order' => 'CDAB',
                        'multiplier' => 1.0,
                        'unit' => 'm³',
                    ],
                ],
            ],
            [
                'device' => [
                    'name' => 'pH SENSOR',
                    'category' => 'ph_sensor',
                    'description' => 'Sensor pH Cairan Industri (Range: 0 ~ 14 pH | Normal: 7.0 ~ 8.0)',
                    'ip_address' => '192.168.1.100',
                    'port' => 502,
                    'slave_id' => 4,
                    'timeout_ms' => 1500,
                    'is_active' => true,
                ],
                'registers' => [
                    [
                        'name' => 'Nilai pH (pH Value)',
                        'parameter_key' => 'ph',
                        'register_address' => 0,
                        'function_code' => 3,
                        'data_type' => 'float32',
                        'byte_order' => 'CDAB',
                        'multiplier' => 1.0,
                        'unit' => 'pH',
                    ],
                ],
            ],
        ];

        // Hapus device lama yang bukan bagian dari 4 instrumen SCADA (Slave ID 1-4)
        Device::whereNotIn('slave_id', [1, 2, 3, 4])->delete();

        foreach ($hardwareDevices as $item) {
            $device = Device::updateOrCreate(
                ['slave_id' => $item['device']['slave_id']],
                $item['device']
            );

            // Bersihkan register lama yang tidak ada di mapping baru untuk device ini
            $activeKeys = collect($item['registers'])->pluck('parameter_key')->toArray();
            $device->registers()->whereNotIn('parameter_key', $activeKeys)->delete();

            // Perbarui atau tambahkan register
            foreach ($item['registers'] as $regData) {
                $device->registers()->updateOrCreate(
                    [
                        'device_id' => $device->id,
                        'parameter_key' => $regData['parameter_key'],
                    ],
                    $regData
                );
            }
        }
    }
}
