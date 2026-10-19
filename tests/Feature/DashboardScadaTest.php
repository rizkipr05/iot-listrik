<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardScadaTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_dashboard_renders_with_zero_values_and_empty_state_when_no_logs_exist()
    {
        $device1 = Device::create([
            'name' => 'Flowmeter 2" Area Chiller',
            'category' => 'flow_meter',
            'description' => 'Nominal Size : 2 inch | Range : 0 ~ 300 m³/h',
            'ip_address' => '127.0.0.1',
            'port' => 65530,
            'slave_id' => 1,
            'timeout_ms' => 200,
            'is_active' => true,
        ]);

        $device4 = Device::create([
            'name' => 'Pressure Sensor',
            'category' => 'pressure_sensor',
            'description' => 'Range : 0 ~ 10 bar | Normal : 1 ~ 4 bar',
            'ip_address' => '127.0.0.1',
            'port' => 65530,
            'slave_id' => 4,
            'timeout_ms' => 200,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Flowmeter 2&quot; Area Chiller', false);
        $response->assertSee('Pressure Sensor');
        $response->assertSee('Belum ada riwayat data log tersimpan');
        // Ensure old hardcoded mock date is not present
        $response->assertDontSee('2026-09-30 13:00');
    }

    public function test_live_telemetry_returns_zero_and_standby_when_offline()
    {
        $device = Device::create([
            'name' => 'Pressure Sensor Lapangan',
            'category' => 'pressure_sensor',
            'description' => 'Range : 0 ~ 10 bar',
            'ip_address' => '127.0.0.1',
            'port' => 65530,
            'slave_id' => 4,
            'timeout_ms' => 200,
            'is_active' => true,
        ]);

        Register::create([
            'device_id' => $device->id,
            'name' => 'Nilai Tekanan',
            'parameter_key' => 'pressure',
            'register_address' => 0,
            'function_code' => 3,
            'data_type' => 'float32',
            'byte_order' => 'CDAB',
            'multiplier' => 1.0,
            'unit' => 'Bar',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson(route('api.telemetry.live'));

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertTrue($json['success']);
        $this->assertEquals(0, $json['scada']['pressure']['value']);
        $this->assertEquals('Standby', $json['scada']['pressure']['status']);
        $this->assertFalse($json['scada']['pressure']['is_online']);
    }

    public function test_dashboard_displays_device_online_and_offline_badges()
    {
        $device = Device::create([
            'name' => 'Flowmeter 2" Area Chiller',
            'category' => 'flow_meter',
            'description' => 'Nominal Size : 2 inch | Range : 0 ~ 300 m³/h',
            'ip_address' => '127.0.0.1',
            'port' => 65530,
            'slave_id' => 1,
            'timeout_ms' => 200,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('id="headerBadgeRMS"', false);
        $response->assertSee('id="statusTextRMS"', false);
        $response->assertSee('id="headerBadgeMHS"', false);
        $response->assertSee('id="statusTextMHS"', false);
        $response->assertSee('id="headerBadgeHRMS"', false);
        $response->assertSee('id="statusTextHRMS"', false);
        $response->assertSee('id="headerBadgePH"', false);
        $response->assertSee('id="statusTextPH"', false);
    }

    public function test_live_telemetry_reports_is_online_status_per_slave()
    {
        $device1 = Device::create([
            'name' => 'Flowmeter Chiller',
            'category' => 'flow_meter',
            'ip_address' => '127.0.0.1',
            'port' => 65530,
            'slave_id' => 1,
            'timeout_ms' => 100,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson(route('api.telemetry.live'));

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertTrue($json['success']);
        $this->assertArrayHasKey('online_devices_count', $json['summary']);
        $this->assertArrayHasKey('is_online', $json['scada']['rms']);
        $this->assertArrayHasKey('is_online', $json['scada']['mhs']);
        $this->assertArrayHasKey('is_online', $json['scada']['h_rms']);
        $this->assertArrayHasKey('is_online', $json['scada']['pressure']);
    }
}
