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
                    'name' => 'Flowmeter 2" Area Chiller',
                    'category' => 'flow_meter',
                    'description' => 'Nominal Size : 2 inch | Range : 0 ~ 300 m³/h',
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
                    'name' => 'Water meter 3/4" Area Toilet',
                    'category' => 'flow_meter',
                    'description' => 'Nominal Size : 3/4 inch | Range : 0 ~ 300 m³/h',
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
                    'name' => 'Water meter 1/2" Area Office',
                    'category' => 'flow_meter',
                    'description' => 'Nominal Size : 1/2 inch | Range : 0 ~ 300 m³/h',
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
                    'name' => 'Pressure Sensor',
                    'category' => 'pressure_sensor',
                    'description' => 'Range : 0 ~ 10 bar | Normal : 1 ~ 4 bar',
                    'ip_address' => '192.168.1.100',
                    'port' => 502,
                    'slave_id' => 4,
                    'timeout_ms' => 1500,
                    'is_active' => true,
                ],
                'registers' => [
                    [
                        'name' => 'Nilai Tekanan (Pressure Value)',
                        'parameter_key' => 'pressure',
                        'register_address' => 0,
                        'function_code' => 3,
                        'data_type' => 'float32',
                        'byte_order' => 'CDAB',
                        'multiplier' => 1.0,
                        'unit' => 'Bar',
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
