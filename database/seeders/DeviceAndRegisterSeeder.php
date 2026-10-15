<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Register;
use Illuminate\Database\Seeder;

class DeviceAndRegisterSeeder extends Seeder
{
    /**
     * Seed 5 perangkat meter dan konfigurasi register standar.
     */
    public function run(): void
    {
        $hardwareDevices = [
            [
                'device' => [
                    'name' => 'Meter Air RS485',
                    'category' => 'water_meter',
                    'description' => '1 unit Meter Air mekanik/elektromagnetik RS485 via Gateway TCP/IP',
                    'ip_address' => '192.168.1.100',
                    'port' => 502,
                    'slave_id' => 1,
                    'timeout_ms' => 1500,
                    'is_active' => true,
                ],
                'registers' => [
                    [
                        'name' => 'Totalizer Volume Air (m³)',
                        'parameter_key' => 'totalizer',
                        'register_address' => 0,
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
                    'name' => 'Meter Air Digital RS485',
                    'category' => 'digital_water_meter',
                    'description' => '1 unit Meter Air Digital ultrasonik/elektromagnetik RS485 via Gateway TCP/IP',
                    'ip_address' => '192.168.1.100',
                    'port' => 502,
                    'slave_id' => 2,
                    'timeout_ms' => 1500,
                    'is_active' => true,
                ],
                'registers' => [
                    [
                        'name' => 'Totalizer Volume Air (m³)',
                        'parameter_key' => 'totalizer',
                        'register_address' => 0,
                        'function_code' => 3,
                        'data_type' => 'float32',
                        'byte_order' => 'CDAB',
                        'multiplier' => 1.0,
                        'unit' => 'm³',
                    ],
                    [
                        'name' => 'Laju Alir / Flow Rate (m³/h)',
                        'parameter_key' => 'flow_rate',
                        'register_address' => 2,
                        'function_code' => 3,
                        'data_type' => 'float32',
                        'byte_order' => 'CDAB',
                        'multiplier' => 1.0,
                        'unit' => 'm³/h',
                    ],
                ],
            ],
            [
                'device' => [
                    'name' => 'Flow Meter RS485',
                    'category' => 'flow_meter',
                    'description' => '1 unit Flow Meter pipa industri RS485 via Gateway TCP/IP',
                    'ip_address' => '192.168.1.100',
                    'port' => 502,
                    'slave_id' => 3,
                    'timeout_ms' => 1500,
                    'is_active' => true,
                ],
                'registers' => [
                    [
                        'name' => 'Totalizer Akumulatif (m³)',
                        'parameter_key' => 'totalizer',
                        'register_address' => 0,
                        'function_code' => 3,
                        'data_type' => 'float32',
                        'byte_order' => 'CDAB',
                        'multiplier' => 1.0,
                        'unit' => 'm³',
                    ],
                    [
                        'name' => 'Laju Aliran Sesaat / Flow Rate (m³/h)',
                        'parameter_key' => 'flow_rate',
                        'register_address' => 2,
                        'function_code' => 3,
                        'data_type' => 'float32',
                        'byte_order' => 'CDAB',
                        'multiplier' => 1.0,
                        'unit' => 'm³/h',
                    ],
                ],
            ],
            [
                'device' => [
                    'name' => 'kWh Meter RS485',
                    'category' => 'kwh_meter',
                    'description' => '1 unit kWh Meter listrik digital RS485 via Gateway TCP/IP',
                    'ip_address' => '192.168.1.100',
                    'port' => 502,
                    'slave_id' => 4,
                    'timeout_ms' => 1500,
                    'is_active' => true,
                ],
                'registers' => [
                    [
                        'name' => 'Total Energi Listrik (Total kWh)',
                        'parameter_key' => 'energy',
                        'register_address' => 3200,
                        'function_code' => 3,
                        'data_type' => 'float32',
                        'byte_order' => 'CDAB',
                        'multiplier' => 1.0,
                        'unit' => 'kWh',
                    ],
                    [
                        'name' => 'Tegangan Listrik (V)',
                        'parameter_key' => 'voltage',
                        'register_address' => 3000,
                        'function_code' => 3,
                        'data_type' => 'float32',
                        'byte_order' => 'CDAB',
                        'multiplier' => 1.0,
                        'unit' => 'V',
                    ],
                    [
                        'name' => 'Arus Beban Listrik (A)',
                        'parameter_key' => 'current',
                        'register_address' => 3002,
                        'function_code' => 3,
                        'data_type' => 'float32',
                        'byte_order' => 'CDAB',
                        'multiplier' => 1.0,
                        'unit' => 'A',
                    ],
                ],
            ],
            [
                'device' => [
                    'name' => 'Power Meter RS485',
                    'category' => 'power_meter',
                    'description' => '1 unit Multifunction Power Meter Analyzer RS485 via Gateway TCP/IP',
                    'ip_address' => '192.168.1.100',
                    'port' => 502,
                    'slave_id' => 5,
                    'timeout_ms' => 1500,
                    'is_active' => true,
                ],
                'registers' => [
                    [
                        'name' => 'Total Energi Listrik (Total kWh)',
                        'parameter_key' => 'energy',
                        'register_address' => 3200,
                        'function_code' => 3,
                        'data_type' => 'float32',
                        'byte_order' => 'CDAB',
                        'multiplier' => 1.0,
                        'unit' => 'kWh',
                    ],
                    [
                        'name' => 'Tegangan Listrik (V)',
                        'parameter_key' => 'voltage',
                        'register_address' => 3000,
                        'function_code' => 3,
                        'data_type' => 'float32',
                        'byte_order' => 'CDAB',
                        'multiplier' => 1.0,
                        'unit' => 'V',
                    ],
                    [
                        'name' => 'Arus Beban Listrik (A)',
                        'parameter_key' => 'current',
                        'register_address' => 3002,
                        'function_code' => 3,
                        'data_type' => 'float32',
                        'byte_order' => 'CDAB',
                        'multiplier' => 1.0,
                        'unit' => 'A',
                    ],
                    [
                        'name' => 'Daya Aktif Total (kW)',
                        'parameter_key' => 'power',
                        'register_address' => 3006,
                        'function_code' => 3,
                        'data_type' => 'float32',
                        'byte_order' => 'CDAB',
                        'multiplier' => 1.0,
                        'unit' => 'kW',
                    ],
                ],
            ],
        ];

        foreach ($hardwareDevices as $item) {
            $device = Device::updateOrCreate(
                ['name' => $item['device']['name']],
                $item['device']
            );

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
