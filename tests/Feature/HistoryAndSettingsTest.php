<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Device;
use App\Models\MeterLog;
use App\Models\User;
use App\Services\ModbusTcpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'name' => 'Meter Export',
            'ip_address' => '192.168.1.12',
            'port' => 502,
            'slave_id' => 2,
        ]);

        MeterLog::create([
            'device_id' => $device->id,
            'voltage' => 222.0,
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
    }

    public function test_authenticated_user_can_view_settings_page()
    {
        $response = $this->actingAs($this->user)->get('/settings');

        $response->assertStatus(200);
        $response->assertSee('Pengaturan Interval Data Logger');
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
}
