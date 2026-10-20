<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\MeterLog;
use App\Models\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
            'description' => 'Nominal Size : 1 inch | Range : 0 ~ 4 m³/h',
            'ip_address' => '127.0.0.1',
            'port' => 65530,
            'slave_id' => 1,
            'timeout_ms' => 200,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);
        // Header badges removed as requested (no double status), status only below % Flow
        $response->assertDontSee('id="headerBadgeRMS"', false);
        $response->assertDontSee('id="headerBadgeMHS"', false);
        $response->assertDontSee('id="headerBadgeHRMS"', false);
        $response->assertDontSee('id="headerBadgePH"', false);

        // Status text below % Flow must exist
        $response->assertSee('id="statusTextRMS"', false);
        $response->assertSee('id="statusTextMHS"', false);
        $response->assertSee('id="statusTextHRMS"', false);
        $response->assertSee('id="statusTextPH"', false);

        // Scale configuration link to dedicated page must exist on dashboard (modal moved to separate page)
        $response->assertSee('ATUR SKALA MAX');
        $response->assertSee(route('settings.scales'));
        $response->assertDontSee('id="scaleSettingsModal"', false);
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

    public function test_offline_devices_do_not_show_anomalous_or_simulated_values_in_trend_chart_and_history_table()
    {
        $dev1 = Device::create([
            'name' => 'FLOWMETER 1" - AREA RMS',
            'category' => 'flow_meter',
            'ip_address' => '192.168.1.118',
            'port' => 502,
            'slave_id' => 1,
            'is_active' => true,
        ]);

        $dev2 = Device::create([
            'name' => 'FLOWMETER 1" - AREA MHS',
            'category' => 'flow_meter',
            'ip_address' => '192.168.1.118',
            'port' => 502,
            'slave_id' => 2,
            'is_active' => true,
        ]);

        $dev3 = Device::create([
            'name' => 'Water Meter Photoelectric - AREA H-RMS',
            'category' => 'flow_meter',
            'ip_address' => '192.168.1.118',
            'port' => 502,
            'slave_id' => 3,
            'is_active' => true,
        ]);

        $dev4 = Device::create([
            'name' => 'Pressure Sensor',
            'category' => 'pressure_sensor',
            'ip_address' => '192.168.1.118',
            'port' => 502,
            'slave_id' => 4,
            'is_active' => true,
        ]);

        // Simulasikan hanya Device 1 yang online
        Cache::put("device_latest_telemetry_{$dev1->id}", [
            'is_online' => true,
            'flow_rate' => 1.4,
            'totalizer' => 2.34,
            'updated_at' => now()->toIso8601String(),
        ], 86400);

        // Buat data anomali dummy lama di database (simulasi dan nilai pH 7.68)
        MeterLog::create([
            'device_id' => $dev4->id,
            'ph' => 7.68,
            'is_simulated' => true,
            'logged_at' => now()->subHour(),
        ]);

        MeterLog::create([
            'device_id' => $dev2->id,
            'flow_rate' => 142.7,
            'totalizer' => 6783.5,
            'is_simulated' => true,
            'logged_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);

        // Pastikan nilai grafik untuk device 2, 3, dan 4 mutlak 0.0 (tidak ada spike ke 7.68)
        $chartMhs = $response->viewData('chartMhs');
        $chartHrms = $response->viewData('chartHrms');
        $chartPh = $response->viewData('chartPh');
        $chartRms = $response->viewData('chartRms');

        $this->assertEquals(array_fill(0, count($chartMhs), 0.0), $chartMhs);
        $this->assertEquals(array_fill(0, count($chartHrms), 0.0), $chartHrms);
        $this->assertEquals(array_fill(0, count($chartPh), 0.0), $chartPh);
        $this->assertFalse(in_array(7.68, $chartPh));

        // Device 1 yang online memiliki nilai terkini 1.4
        $this->assertEquals(1.4, end($chartRms));

        // Di tabel history, pastikan tidak menampilkan 7.68 atau 142.7
        $response->assertDontSee('7.68');
        $response->assertDontSee('142.7');
    }
}
