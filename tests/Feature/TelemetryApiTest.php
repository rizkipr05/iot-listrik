<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelemetryApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_guest_cannot_access_telemetry_api()
    {
        $response = $this->getJson('/api/telemetry/live');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_get_live_telemetry()
    {
        $device = Device::create([
            'name' => 'Main Incomer Panel',
            'ip_address' => '127.0.0.1',
            'port' => 65530,
            'slave_id' => 1,
            'timeout_ms' => 200,
            'is_active' => true,
        ]);

        Register::create([
            'device_id' => $device->id,
            'name' => 'Tegangan Phase A',
            'parameter_key' => 'voltage',
            'register_address' => 3000,
            'function_code' => 3,
            'data_type' => 'float32',
            'byte_order' => 'CDAB',
            'multiplier' => 1.0,
            'unit' => 'V',
            'is_active' => true,
        ]);

        Register::create([
            'device_id' => $device->id,
            'name' => 'Daya Total',
            'parameter_key' => 'power',
            'register_address' => 3006,
            'function_code' => 3,
            'data_type' => 'float32',
            'byte_order' => 'CDAB',
            'multiplier' => 1.0,
            'unit' => 'kW',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/telemetry/live');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'timestamp',
            'time_label',
            'summary' => [
                'total_power_kw',
                'avg_voltage_v',
                'total_current_a',
                'total_energy_kwh',
                'avg_power_factor',
                'grid_frequency_hz',
                'total_water_m3',
                'total_flow_rate_m3h',
                'water_devices_count',
                'electric_devices_count',
            ],
            'devices',
        ]);

        $json = $response->json();
        $this->assertTrue($json['success']);
        $this->assertNotEmpty($json['devices']);
    }

    public function test_telemetry_includes_water_and_flow_meter_values()
    {
        $waterDevice = Device::create([
            'name' => 'Meter Air Digital Lapangan',
            'category' => 'digital_water_meter',
            'ip_address' => '127.0.0.1',
            'port' => 65531,
            'slave_id' => 2,
            'timeout_ms' => 200,
            'is_active' => true,
        ]);

        Register::create([
            'device_id' => $waterDevice->id,
            'name' => 'Totalizer Akumulasi',
            'parameter_key' => 'totalizer',
            'register_address' => 100,
            'function_code' => 3,
            'data_type' => 'float32',
            'byte_order' => 'CDAB',
            'multiplier' => 1.0,
            'unit' => 'm3',
            'is_active' => true,
        ]);

        Register::create([
            'device_id' => $waterDevice->id,
            'name' => 'Laju Aliran Saat Ini',
            'parameter_key' => 'flow_rate',
            'register_address' => 102,
            'function_code' => 3,
            'data_type' => 'float32',
            'byte_order' => 'CDAB',
            'multiplier' => 1.0,
            'unit' => 'm3/h',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/telemetry/live');

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertGreaterThanOrEqual(1, $json['summary']['water_devices_count']);

        $deviceData = collect($json['devices'])->firstWhere('device_id', $waterDevice->id);
        $this->assertNotNull($deviceData);
        $this->assertArrayHasKey('totalizer', $deviceData['parameters']);
        $this->assertArrayHasKey('flow_rate', $deviceData['parameters']);
    }

    public function test_get_gateway_status_returns_correct_gateway_info()
    {
        Device::create([
            'name' => 'Flowmeter Real',
            'ip_address' => '192.168.1.118',
            'port' => 502,
            'slave_id' => 1,
            'timeout_ms' => 100,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/gateway/status');

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertTrue($json['success']);
        $this->assertEquals('192.168.1.118', $json['gateway']['ip_address']);
        $this->assertEquals(502, $json['gateway']['port']);
        $this->assertArrayHasKey('is_online', $json['gateway']);
        $this->assertArrayHasKey('status_label', $json['gateway']);
    }
}
