<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Device;
use App\Models\MeterLog;
use App\Models\User;
use App\Services\ModbusTcpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HistoryAndSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_authenticated_user_can_view_history_page()
    {
        $device = Device::create([
            'name' => 'Meter Sejarah',
            'ip_address' => '192.168.1.10',
            'port' => 502,
            'slave_id' => 1,
        ]);

        MeterLog::create([
            'device_id' => $device->id,
            'voltage' => 220.5,
            'current' => 25.0,
            'power' => 10.0,
            'energy' => 300.0,
            'is_simulated' => true,
            'logged_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get('/history');

        $response->assertStatus(200);
        $response->assertSee('Data History');
        $response->assertSee('Meter Sejarah');
        $response->assertSee('220.5 V');
    }

    public function test_user_can_export_history_as_csv()
    {
        $device = Device::create([
            'name' => 'Water Meter Export',
            'category' => 'flow_meter',
            'ip_address' => '192.168.1.12',
            'port' => 502,
            'slave_id' => 2,
        ]);

        MeterLog::create([
            'device_id' => $device->id,
            'flow_rate' => 2.5,
            'totalizer' => 150.0,
            'voltage' => 222.0, // nilai residu atau mock
            'current' => 15.0,
            'power' => 5.5,
            'energy' => 120.0,
            'is_simulated' => false,
            'logged_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get('/history/export');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename="iot_meter_logs_', $response->headers->get('content-disposition'));

        // Parameter listrik tidak boleh diekspor jika sistem khusus water & pressure meter
        $content = $response->streamedContent();
        $this->assertStringContainsString('Totalizer Air (m³)', $content);
        $this->assertStringContainsString('Flow Rate (m³/h)', $content);
        $this->assertStringContainsString('Nilai pH / Pressure (Bar)', $content);
        $this->assertStringNotContainsString('Tegangan L-N (V)', $content);
        $this->assertStringNotContainsString('Arus (A)', $content);
        $this->assertStringNotContainsString('Daya Aktif (kW)', $content);
    }

    public function test_user_can_export_history_as_csv_with_electrical_columns_when_electric_device_exists()
    {
        $electricDev = Device::create([
            'name' => 'Power Meter Utama',
            'category' => 'power_meter',
            'ip_address' => '192.168.1.15',
            'port' => 502,
            'slave_id' => 10,
        ]);

        MeterLog::create([
            'device_id' => $electricDev->id,
            'voltage' => 226.5,
            'current' => 12.0,
            'power' => 2.7,
            'energy' => 85.0,
            'is_simulated' => false,
            'logged_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get('/history/export');

        $response->assertStatus(200);
        $content = $response->streamedContent();
        $this->assertStringContainsString('Tegangan L-N (V)', $content);
        $this->assertStringContainsString('Arus (A)', $content);
        $this->assertStringContainsString('Daya Aktif (kW)', $content);
        $this->assertStringContainsString('226.5', $content);
    }

    public function test_authenticated_user_can_view_settings_page()
    {
        $response = $this->actingAs($this->user)->get('/settings');

        $response->assertStatus(200);
        $response->assertSee('Pengaturan Interval Data Logger');
        $response->assertSee('Skala & Ambang Batas Warning', false);
        $response->assertSee(route('settings.scales'));
        $response->assertDontSee('name="max_flowrate_rms"', false);
    }

    public function test_user_can_trigger_manual_log_record()
    {
        $device = Device::create([
            'name' => 'Meter Manual Log',
            'ip_address' => '192.168.1.15',
            'port' => 502,
            'slave_id' => 1,
            'is_active' => true,
        ]);

        $mockModbus = $this->mock(ModbusTcpService::class);
        $mockModbus->shouldReceive('readDeviceRegisters')
            ->once()
            ->with(\Mockery::type(Device::class), false)
            ->andReturn([
                'device_id' => $device->id,
                'device_name' => $device->name,
                'slave_id' => $device->slave_id,
                'ip_gateway' => '192.168.1.15:502',
                'is_online' => true,
                'is_simulated' => false,
                'parameters' => [
                    'voltage' => ['value' => 220.0],
                    'current' => ['value' => 5.0],
                    'power' => ['value' => 1.1],
                    'energy' => ['value' => 100.0],
                ],
            ]);

        $response = $this->actingAs($this->user)->post('/history/log-now');

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('meter_logs', [
            'device_id' => $device->id,
            'is_simulated' => false,
            'voltage' => 220.0,
        ]);
    }

    public function test_manual_log_skips_offline_and_simulated_devices()
    {
        $device = Device::create([
            'name' => 'Offline Meter',
            'ip_address' => '192.168.1.15',
            'port' => 502,
            'slave_id' => 1,
            'is_active' => true,
        ]);

        $mockModbus = $this->mock(ModbusTcpService::class);
        $mockModbus->shouldReceive('readDeviceRegisters')
            ->once()
            ->with(\Mockery::type(Device::class), false)
            ->andReturn([
                'device_id' => $device->id,
                'device_name' => $device->name,
                'slave_id' => $device->slave_id,
                'ip_gateway' => '192.168.1.15:502',
                'is_online' => false,
                'is_simulated' => false,
                'parameters' => [],
            ]);

        $response = $this->actingAs($this->user)->post('/history/log-now');

        $response->assertSessionHas('warning');
        $this->assertEquals(0, MeterLog::count());
    }

    public function test_user_can_purge_history_logs()
    {
        $device = Device::create([
            'name' => 'Meter Purge',
            'ip_address' => '192.168.1.16',
            'port' => 502,
            'slave_id' => 2,
        ]);

        MeterLog::create([
            'device_id' => $device->id,
            'power' => 5.0,
            'is_simulated' => false,
            'logged_at' => now()->subDays(40),
        ]);

        MeterLog::create([
            'device_id' => $device->id,
            'power' => 8.0,
            'is_simulated' => false,
            'logged_at' => now(),
        ]);

        MeterLog::create([
            'device_id' => $device->id,
            'power' => 9.0,
            'is_simulated' => true,
            'logged_at' => now(),
        ]);

        $this->assertEquals(3, MeterLog::count());

        // Hapus hanya log simulasi
        $this->actingAs($this->user)->delete('/history/purge', [
            'range' => 'simulated_only',
        ]);
        $this->assertEquals(0, MeterLog::where('is_simulated', true)->count());
        $this->assertEquals(2, MeterLog::count());

        // Hapus log lebih dari 30 hari yang lalu
        $response = $this->actingAs($this->user)->delete('/history/purge', [
            'range' => 'older_30_days',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals(1, MeterLog::count());

        // Hapus seluruh log
        $this->actingAs($this->user)->delete('/history/purge', [
            'range' => 'all',
        ]);
        $this->assertEquals(0, MeterLog::count());
    }

    public function test_user_can_purge_history_logs_for_today()
    {
        $device = Device::create([
            'name' => 'Meter Purge Today',
            'ip_address' => '192.168.1.17',
            'port' => 502,
            'slave_id' => 1,
            'is_active' => true,
        ]);

        MeterLog::create([
            'device_id' => $device->id,
            'voltage' => 220.0,
            'logged_at' => now(), // hari ini
        ]);

        MeterLog::create([
            'device_id' => $device->id,
            'voltage' => 220.0,
            'logged_at' => now()->subDays(2), // 2 hari lalu
        ]);

        $this->assertEquals(2, MeterLog::count());

        $response = $this->actingAs($this->user)->delete('/history/purge', [
            'range' => 'today',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals(1, MeterLog::count());
    }

    public function test_user_can_delete_individual_log_entry()
    {
        $device = Device::create([
            'name' => 'Meter Single Delete',
            'ip_address' => '192.168.1.18',
            'port' => 502,
            'slave_id' => 1,
            'is_active' => true,
        ]);

        $log = MeterLog::create([
            'device_id' => $device->id,
            'voltage' => 220.0,
            'logged_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->delete(route('history.destroy', $log->id));

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('meter_logs', ['id' => $log->id]);
    }

    public function test_user_can_update_logger_interval_setting()
    {
        $response = $this->actingAs($this->user)->put('/settings/logger', [
            'logger_enabled' => 1,
            'logger_interval_seconds' => 86400, // 1 hari
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('1', AppSetting::getValue('logger_enabled'));
        $this->assertEquals('86400', AppSetting::getValue('logger_interval_seconds'));
    }

    public function test_user_can_download_database_backup()
    {
        // Pastikan file database ada
        $dbPath = database_path('database.sqlite');
        if (! file_exists($dbPath)) {
            touch($dbPath);
        }

        $response = $this->actingAs($this->user)->get('/settings/backup-db');

        $response->assertStatus(200);
        $this->assertStringContainsString('attachment; filename=backup_iotlistrik_', $response->headers->get('content-disposition'));
    }

    public function test_user_can_view_gauge_scales_settings_page()
    {
        $response = $this->actingAs($this->user)->get('/settings/scales');

        $response->assertStatus(200);
        $response->assertSee('Pengaturan Skala Maksimal Gauge');
        $response->assertSee('name="max_flowrate_rms"', false);
        $response->assertSee('name="max_flowrate_mhs"', false);
        $response->assertSee('name="max_flowrate_hrms"', false);
        $response->assertSee('name="max_pressure"', false);
        $response->assertSee('name="pressure_normal_min"', false);
        $response->assertSee('name="pressure_normal_max"', false);
        $response->assertSee('name="flow_warning_percent"', false);
    }

    public function test_user_can_update_meter_max_scales_setting()
    {
        $response = $this->actingAs($this->user)->put('/settings/scales', [
            'max_flowrate_rms' => 4.0,
            'max_flowrate_mhs' => 10.0,
            'max_flowrate_hrms' => 25.0,
            'max_pressure' => 16.0,
            'pressure_normal_min' => 1.5,
            'pressure_normal_max' => 5.0,
            'flow_warning_percent' => 90,
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('4', AppSetting::getValue('max_flowrate_rms'));
        $this->assertEquals('10', AppSetting::getValue('max_flowrate_mhs'));
        $this->assertEquals('25', AppSetting::getValue('max_flowrate_hrms'));
        $this->assertEquals('16', AppSetting::getValue('max_pressure'));
        $this->assertEquals('1.5', AppSetting::getValue('pressure_normal_min'));
        $this->assertEquals('5', AppSetting::getValue('pressure_normal_max'));
        $this->assertEquals('90', AppSetting::getValue('flow_warning_percent'));
    }

    public function test_dashboard_uses_configured_max_scales_for_gauge_percentage()
    {
        AppSetting::setValue('max_flowrate_rms', '4.0');

        $device = Device::create([
            'name' => 'FLOWMETER 1" - AREA RMS',
            'category' => 'flow_meter',
            'description' => 'Range: 0 - 4 m3/h',
            'ip_address' => '192.168.1.118',
            'port' => 502,
            'slave_id' => 1,
            'is_active' => true,
        ]);

        Cache::put("device_online_{$device->id}", true, 60);

        MeterLog::create([
            'device_id' => $device->id,
            'flow_rate' => 1.5,
            'totalizer' => 1.56,
            'is_simulated' => false,
            'logged_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200);
        // 1.5 / 4.0 = 37.5% -> round 38%
        $response->assertSee('38%');
        $response->assertSee('(Range 0 ~ 4 m³/h)');
    }

    public function test_dashboard_renders_cached_live_telemetry_instantly_without_waiting_or_resetting_to_zero()
    {
        $device = Device::create([
            'name' => 'FLOWMETER 1" - AREA RMS',
            'category' => 'flow_meter',
            'ip_address' => '192.168.1.118',
            'port' => 502,
            'slave_id' => 1,
            'is_active' => true,
        ]);

        // Simulasikan data realtime telah tercatat di Cache oleh background polling
        Cache::put("device_latest_telemetry_{$device->id}", [
            'is_online' => true,
            'flow_rate' => 1.8,
            'totalizer' => 250.4,
            'updated_at' => now()->toIso8601String(),
        ], 86400);

        Cache::put('scada_latest_telemetry', [
            'rms' => ['flow_rate' => 1.8, 'totalizer' => 250.4, 'is_online' => true],
        ], 86400);

        // Pengguna membuka halaman device meter
        $devicePage = $this->actingAs($this->user)->get('/devices');
        $devicePage->assertStatus(200);

        // Pengguna kembali ke menu utama dashboard
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('currentRmsFlow', 1.8);
        $response->assertViewHas('currentRmsTot', 250.4);
        $response->assertSee('1.8');
    }

    public function test_scale_modal_can_save_settings_via_ajax_json_response()
    {
        $response = $this->actingAs($this->user)->putJson(route('settings.scales.update'), [
            'max_flowrate_rms' => 4.0,
            'max_flowrate_mhs' => 10.0,
            'max_flowrate_hrms' => 10.0,
            'max_pressure' => 10.0,
            'pressure_normal_min' => 1.5,
            'pressure_normal_max' => 5.0,
            'flow_warning_percent' => 85,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'scales' => [
                'rms_max' => 4.0,
                'mhs_max' => 10.0,
                'hrms_max' => 10.0,
                'pressure_max' => 10.0,
                'pressure_normal_min' => 1.5,
                'pressure_normal_max' => 5.0,
                'warning_percent' => 85,
            ],
        ]);

        $this->assertEquals('4', AppSetting::getValue('max_flowrate_rms'));
        $this->assertEquals('1.5', AppSetting::getValue('pressure_normal_min'));
        $this->assertEquals('5', AppSetting::getValue('pressure_normal_max'));
    }

    public function test_history_displays_pressure_in_bar_and_calculates_proper_flow_aggregates()
    {
        $flowDev = Device::create([
            'name' => 'Flowmeter 2" Area Chiller',
            'category' => 'flow_meter',
            'ip_address' => '192.168.1.118',
            'port' => 502,
            'slave_id' => 1,
            'is_active' => true,
        ]);

        $pressureDev = Device::create([
            'name' => 'Pressure Sensor Pipa Distribusi',
            'category' => 'pressure_sensor',
            'ip_address' => '192.168.1.118',
            'port' => 502,
            'slave_id' => 4,
            'is_active' => true,
        ]);

        MeterLog::create([
            'device_id' => $flowDev->id,
            'flow_rate' => 1.5,
            'totalizer' => 100.0,
            'is_simulated' => false,
            'logged_at' => now()->subHours(2),
        ]);

        MeterLog::create([
            'device_id' => $flowDev->id,
            'flow_rate' => 1.7,
            'totalizer' => 105.0,
            'is_simulated' => false,
            'logged_at' => now()->subHour(),
        ]);

        MeterLog::create([
            'device_id' => $pressureDev->id,
            'ph' => 2.35,
            'is_simulated' => false,
            'logged_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get('/history');

        $response->assertStatus(200);
        $response->assertSee('Rata-rata Tekanan Pipa');
        $response->assertSee('2.35');
        $response->assertSee('Bar');
        $response->assertSee('NORMAL (1.0 - 4.0 Bar)');
        $response->assertSee('Tekanan Pipa (Bar)');
        $response->assertViewHas('totalWaterVolume', 5.0);
        $response->assertViewHas('avgPressure', 2.35);
    }

    public function test_history_supports_source_filter_and_active_device_detection()
    {
        $device = Device::create([
            'name' => 'Flowmeter 2" Area Chiller',
            'category' => 'flow_meter',
            'ip_address' => '192.168.1.118',
            'port' => 502,
            'slave_id' => 1,
            'is_active' => true,
        ]);

        MeterLog::create([
            'device_id' => $device->id,
            'flow_rate' => 1.5,
            'totalizer' => 100.0,
            'is_simulated' => false,
            'logged_at' => now()->subHour(),
        ]);

        MeterLog::create([
            'device_id' => $device->id,
            'flow_rate' => 185.0,
            'totalizer' => 8500.0,
            'is_simulated' => true,
            'logged_at' => now(),
        ]);

        // Filter hanya real
        $realResp = $this->actingAs($this->user)->get('/history?source=real');
        $realResp->assertStatus(200);
        $realResp->assertViewHas('totalLogs', 1);

        // Filter hanya simulated
        $simResp = $this->actingAs($this->user)->get('/history?source=simulated');
        $simResp->assertStatus(200);
        $simResp->assertViewHas('totalLogs', 1);
    }

    public function test_manual_log_records_cached_real_telemetry_when_device_online()
    {
        $device = Device::create([
            'name' => 'Flowmeter 2" Area Chiller',
            'category' => 'flow_meter',
            'ip_address' => '192.168.1.118',
            'port' => 502,
            'slave_id' => 1,
            'is_active' => true,
        ]);

        // Simulasikan cache telemetry terisi dari live background loop
        Cache::put("device_latest_telemetry_{$device->id}", [
            'is_online' => true,
            'flow_rate' => 1.55,
            'totalizer' => 108.5,
            'pressure' => 0.0,
            'updated_at' => now()->toIso8601String(),
        ], 86400);

        $mockModbus = $this->mock(ModbusTcpService::class);
        $mockModbus->shouldReceive('readDeviceRegisters')
            ->once()
            ->with(\Mockery::type(Device::class), false)
            ->andReturn([
                'device_id' => $device->id,
                'is_online' => false, // Modbus socket busy saat tombol diklik
                'is_simulated' => false,
                'parameters' => [],
            ]);

        $response = $this->actingAs($this->user)->post('/history/log-now');

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('meter_logs', [
            'device_id' => $device->id,
            'flow_rate' => 1.55,
            'totalizer' => 108.5,
            'is_simulated' => false,
        ]);
    }
}
