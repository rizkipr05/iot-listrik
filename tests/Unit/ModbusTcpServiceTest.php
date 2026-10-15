<?php

namespace Tests\Unit;

use App\Models\Device;
use App\Models\Register;
use App\Services\ModbusTcpService;
use Tests\TestCase;

class ModbusTcpServiceTest extends TestCase
{
    protected ModbusTcpService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ModbusTcpService;
    }

    public function test_decode_uint16()
    {
        // 0x01F4 = 500
        $bytes = pack('n', 500);
        $result = $this->service->decodeBytes($bytes, 'uint16');
        $this->assertEquals(500, $result);
    }

    public function test_decode_int16_negative()
    {
        // -15 in 16-bit signed (0xFFF1 = 65521 in big endian)
        $bytes = pack('n', 65536 - 15);

        $result = $this->service->decodeBytes($bytes, 'int16');
        $this->assertEquals(-15, $result);
    }

    public function test_decode_float32_abcd_big_endian()
    {
        // Float 220.5 in IEEE 754 Big Endian (G)
        $expectedFloat = 220.5;
        $bytes = pack('G', $expectedFloat);

        $result = $this->service->decodeBytes($bytes, 'float32', 'ABCD');
        $this->assertEqualsWithDelta($expectedFloat, $result, 0.001);
    }

    public function test_decode_float32_cdab_word_swap()
    {
        $expectedFloat = 220.5;
        $normalBytes = pack('G', $expectedFloat);
        // Word swap CDAB: swap first 2 bytes with last 2 bytes
        $wordSwapped = substr($normalBytes, 2, 2).substr($normalBytes, 0, 2);

        $result = $this->service->decodeBytes($wordSwapped, 'float32', 'CDAB');
        $this->assertEqualsWithDelta($expectedFloat, $result, 0.001);
    }

    public function test_simulator_fallback_when_offline()
    {
        $device = new Device([
            'ip_address' => '127.0.0.1',
            'port' => 65530, // port pasti offline
            'slave_id' => 1,
            'timeout_ms' => 200,
        ]);

        $register = new Register([
            'parameter_key' => 'voltage',
            'register_address' => 3000,
            'function_code' => 3,
            'data_type' => 'float32',
            'byte_order' => 'CDAB',
            'multiplier' => 1.0,
            'unit' => 'V',
        ]);

        $result = $this->service->readRegister($device, $register, true);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['is_simulated']);
        $this->assertGreaterThan(200, $result['value']);
        $this->assertLessThan(240, $result['value']);
    }
}
