<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceAndRegisterTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_authenticated_user_can_view_devices_index()
    {
        $device = Device::create([
            'name' => 'Meter Test 1',
            'ip_address' => '192.168.1.100',
            'port' => 502,
            'slave_id' => 1,
            'timeout_ms' => 1500,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get('/devices');

        $response->assertStatus(200);
        $response->assertSee('Meter Test 1');
        $response->assertSee('192.168.1.100:502');
    }

    public function test_user_can_create_new_device()
    {
        $response = $this->actingAs($this->user)->post('/devices', [
            'name' => 'Chiller Utama Panel',
            'description' => 'Panel pendingin ruang',
            'ip_address' => '192.168.1.105',
            'port' => 502,
            'slave_id' => 5,
            'timeout_ms' => 2000,
            'is_active' => 1,
        ]);

        $response->assertRedirect('/devices');
        $this->assertDatabaseHas('devices', [
            'name' => 'Chiller Utama Panel',
            'ip_address' => '192.168.1.105',
            'slave_id' => 5,
        ]);
    }

    public function test_user_can_update_device()
    {
        $device = Device::create([
            'name' => 'Device Lama',
            'ip_address' => '192.168.1.10',
            'port' => 502,
            'slave_id' => 1,
            'timeout_ms' => 1000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->put("/devices/{$device->id}", [
            'name' => 'Device Diedit',
            'ip_address' => '192.168.1.20',
            'port' => 502,
            'slave_id' => 2,
            'timeout_ms' => 1200,
            'is_active' => 1,
        ]);

        $response->assertRedirect('/devices');
        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'name' => 'Device Diedit',
            'ip_address' => '192.168.1.20',
            'slave_id' => 2,
        ]);
    }

    public function test_user_can_delete_device()
    {
        $device = Device::create([
            'name' => 'Device Mau Dihapus',
            'ip_address' => '192.168.1.11',
            'port' => 502,
            'slave_id' => 1,
        ]);

        $response = $this->actingAs($this->user)->delete("/devices/{$device->id}");

        $response->assertRedirect('/devices');
        $this->assertDatabaseMissing('devices', [
            'id' => $device->id,
        ]);
    }

    public function test_user_can_create_register_for_device()
    {
        $device = Device::create([
            'name' => 'Meter Incomer',
            'ip_address' => '192.168.1.101',
            'port' => 502,
            'slave_id' => 1,
        ]);

        $response = $this->actingAs($this->user)->post('/registers', [
            'device_id' => $device->id,
            'name' => 'Tegangan Phase A',
            'parameter_key' => 'voltage',
            'register_address' => 3000,
            'function_code' => 3,
            'data_type' => 'float32',
            'byte_order' => 'CDAB',
            'multiplier' => 1.0,
            'unit' => 'V',
            'is_active' => 1,
        ]);

        $response->assertRedirect("/registers?device_id={$device->id}");
        $this->assertDatabaseHas('registers', [
            'device_id' => $device->id,
            'name' => 'Tegangan Phase A',
            'register_address' => 3000,
            'unit' => 'V',
        ]);
    }

    public function test_socket_connection_endpoint_responds_json()
    {
        $device = Device::create([
            'name' => 'Test Local',
            'ip_address' => '127.0.0.1',
            'port' => 502,
            'slave_id' => 1,
            'timeout_ms' => 200,
        ]);

        $response = $this->actingAs($this->user)->post("/devices/{$device->id}/test-connection");

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'message']);
    }

    public function test_user_can_create_water_meter_with_totalizer_and_flow_rate_registers()
    {
        $response = $this->actingAs($this->user)->post('/devices', [
            'name' => 'Flow Meter Produksi',
            'category' => 'flow_meter',
            'description' => 'Monitoring laju alir pipa air',
            'ip_address' => '192.168.1.100',
            'port' => 502,
            'slave_id' => 3,
            'timeout_ms' => 1500,
            'is_active' => 1,
        ]);

        $response->assertRedirect('/devices');
        $this->assertDatabaseHas('devices', [
            'name' => 'Flow Meter Produksi',
            'category' => 'flow_meter',
            'slave_id' => 3,
        ]);

        $device = Device::where('name', 'Flow Meter Produksi')->first();
        $this->assertTrue($device->isWater());

        // Register Totalizer
        $regTotalizer = $this->actingAs($this->user)->post('/registers', [
            'device_id' => $device->id,
            'name' => 'Totalizer Air',
            'parameter_key' => 'totalizer',
            'register_address' => 100,
            'function_code' => 3,
            'data_type' => 'float32',
            'byte_order' => 'CDAB',
            'multiplier' => 1.0,
            'unit' => 'm3',
            'is_active' => 1,
        ]);
        $regTotalizer->assertRedirect("/registers?device_id={$device->id}");

        // Register Flow Rate
        $regFlow = $this->actingAs($this->user)->post('/registers', [
            'device_id' => $device->id,
            'name' => 'Laju Alir',
            'parameter_key' => 'flow_rate',
            'register_address' => 102,
            'function_code' => 3,
            'data_type' => 'float32',
            'byte_order' => 'CDAB',
            'multiplier' => 1.0,
            'unit' => 'm3/h',
            'is_active' => 1,
        ]);
        $regFlow->assertRedirect("/registers?device_id={$device->id}");

        $this->assertDatabaseHas('registers', [
            'device_id' => $device->id,
            'parameter_key' => 'totalizer',
            'unit' => 'm3',
        ]);
        $this->assertDatabaseHas('registers', [
            'device_id' => $device->id,
            'parameter_key' => 'flow_rate',
            'unit' => 'm3/h',
        ]);
    }
}
