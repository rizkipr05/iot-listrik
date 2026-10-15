<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Register;
use Exception;
use Illuminate\Support\Facades\Cache;

class ModbusTcpService
{
    /**
     * Cache waktu gateway dianggap offline (dalam detik).
     * Diset 60 detik agar tidak memblokir worker PHP berulang kali.
     */
    protected const OFFLINE_CACHE_TTL = 60;

    /**
     * Cek apakah gateway IP:Port dapat dijangkau dalam waktu singkat (maks 50ms).
     */
    public function isGatewayReachable(string $ip, int $port): bool
    {
        $cacheKey = "gw_online_{$ip}_{$port}";

        return Cache::remember($cacheKey, self::OFFLINE_CACHE_TTL, function () use ($ip, $port) {
            $errno = 0;
            $errstr = '';
            // Timeout kilat 0.05 detik (50ms) agar tidak membebani respons web
            $socket = @stream_socket_client(
                "tcp://{$ip}:{$port}",
                $errno,
                $errstr,
                0.05
            );

            if ($socket) {
                fclose($socket);

                return true;
            }

            return false;
        });
    }

    /**
     * Paksa hapus status cache gateway (misal saat user menekan tombol Test Koneksi).
     */
    public function clearGatewayCache(string $ip, int $port): void
    {
        Cache::forget("gw_online_{$ip}_{$port}");
    }

    /**
     * Baca nilai dari satu register spesifik pada device.
     */
    public function readRegister(Device $device, Register $register, bool $allowSimulation = true): array
    {
        // 1. Cek dulu apakah gateway device bisa dijangkau
        $isOnline = $this->isGatewayReachable($device->ip_address, $device->port);

        if (! $isOnline) {
            if ($allowSimulation) {
                $simulatedValue = $this->generateSimulatedValue($device, $register);

                return [
                    'success' => true,
                    'value' => $simulatedValue,
                    'raw_value' => $simulatedValue,
                    'unit' => $register->unit,
                    'is_simulated' => true,
                    'message' => 'Gateway offline. Menampilkan data simulasi.',
                ];
            }

            return [
                'success' => false,
                'value' => 0,
                'raw_value' => 0,
                'unit' => $register->unit,
                'is_simulated' => false,
                'message' => "Gateway {$device->ip_address}:{$device->port} tidak merespon.",
            ];
        }

        // 2. Jika gateway online, baca data via Modbus TCP
        $quantity = in_array($register->data_type, ['float32', 'int32', 'uint32']) ? 2 : 1;

        try {
            $rawBytes = $this->queryModbusTcp(
                $device->ip_address,
                $device->port,
                $device->slave_id,
                $register->function_code,
                $register->register_address,
                $quantity,
                $device->timeout_ms
            );

            $decodedValue = $this->decodeBytes(
                $rawBytes,
                $register->data_type,
                $register->byte_order
            );

            $finalValue = round($decodedValue * $register->multiplier, 3);

            return [
                'success' => true,
                'value' => $finalValue,
                'raw_value' => $decodedValue,
                'unit' => $register->unit,
                'is_simulated' => false,
                'message' => 'Pembacaan socket Modbus berhasil.',
            ];
        } catch (Exception $e) {
            if ($allowSimulation) {
                $simulatedValue = $this->generateSimulatedValue($device, $register);

                return [
                    'success' => true,
                    'value' => $simulatedValue,
                    'raw_value' => $simulatedValue,
                    'unit' => $register->unit,
                    'is_simulated' => true,
                    'message' => 'Gagal membaca register: '.$e->getMessage(),
                ];
            }

            return [
                'success' => false,
                'value' => 0,
                'raw_value' => 0,
                'unit' => $register->unit,
                'is_simulated' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Baca seluruh register aktif dari sebuah device dalam satu panggilan.
     */
    public function readDeviceRegisters(Device $device, bool $allowSimulation = true): array
    {
        $registers = $device->registers()->where('is_active', true)->get();
        $results = [];

        // Cek konektivitas gateway sekali saja untuk seluruh register device ini
        $isOnline = $this->isGatewayReachable($device->ip_address, $device->port);
        $isAnySimulated = ! $isOnline;

        foreach ($registers as $reg) {
            if (! $isOnline && $allowSimulation) {
                $val = $this->generateSimulatedValue($device, $reg);
                $results[$reg->parameter_key] = [
                    'name' => $reg->name,
                    'value' => $val,
                    'unit' => $reg->unit,
                    'register_address' => $reg->register_address,
                    'data_type' => $reg->data_type,
                ];
            } else {
                $read = $this->readRegister($device, $reg, $allowSimulation);
                if (! empty($read['is_simulated'])) {
                    $isAnySimulated = true;
                }
                $results[$reg->parameter_key] = [
                    'name' => $reg->name,
                    'value' => $read['value'],
                    'unit' => $reg->unit,
                    'register_address' => $reg->register_address,
                    'data_type' => $reg->data_type,
                ];
            }
        }

        return [
            'device_id' => $device->id,
            'device_name' => $device->name,
            'slave_id' => $device->slave_id,
            'ip_gateway' => "{$device->ip_address}:{$device->port}",
            'is_online' => true,
            'is_simulated' => $isAnySimulated,
            'parameters' => $results,
        ];
    }

    /**
     * Melakukan komunikasi raw socket Modbus TCP (MBAP + PDU).
     */
    protected function queryModbusTcp(
        string $ip,
        int $port,
        int $slaveId,
        int $functionCode,
        int $startAddress,
        int $quantity,
        int $timeoutMs = 1500
    ): string {
        $timeoutSeconds = max(0.1, min(2.0, $timeoutMs / 1000));
        $errno = 0;
        $errstr = '';

        $socket = @stream_socket_client(
            "tcp://{$ip}:{$port}",
            $errno,
            $errstr,
            $timeoutSeconds
        );

        if (! $socket) {
            throw new Exception("Gagal terhubung ke {$ip}:{$port} ({$errstr})");
        }

        stream_set_timeout($socket, (int) $timeoutSeconds, (int) (($timeoutSeconds - (int) $timeoutSeconds) * 1000000));

        // 1. Susun Modbus TCP Request Frame
        $transactionId = mt_rand(1, 65535);
        $protocolId = 0; // Modbus Protocol
        $length = 6;     // Unit ID (1) + FC (1) + Address (2) + Quantity (2) = 6 bytes

        $mbapHeader = pack('nnnC', $transactionId, $protocolId, $length, $slaveId);
        $pdu = pack('Cnn', $functionCode, $startAddress, $quantity);
        $packet = $mbapHeader.$pdu;

        fwrite($socket, $packet);

        // 2. Baca MBAP Header Respon (7 byte)
        $respHeader = fread($socket, 7);
        if (strlen($respHeader) < 7) {
            fclose($socket);
            throw new Exception('Timeout atau tidak ada respon header dari gateway');
        }

        $headerData = unpack('ntrans_id/nproto_id/nlength/Cunit_id', $respHeader);
        $respRemainingLength = $headerData['length'] - 1;

        if ($respRemainingLength <= 0) {
            fclose($socket);
            throw new Exception('Panjang data respon Modbus tidak valid');
        }

        // 3. Baca PDU Respon
        $respPdu = fread($socket, $respRemainingLength);
        fclose($socket);

        if (strlen($respPdu) < 2) {
            throw new Exception('Data PDU respon Modbus tidak lengkap');
        }

        $respFc = ord($respPdu[0]);

        if ($respFc & 0x80) {
            $exceptionCode = ord($respPdu[1]);
            throw new Exception("Modbus Exception Code: {$exceptionCode}");
        }

        $byteCount = ord($respPdu[1]);

        return substr($respPdu, 2, $byteCount);
    }

    /**
     * Decode data byte biner sesuai tipe data dan byte order.
     */
    public function decodeBytes(string $bytes, string $dataType, string $byteOrder = 'CDAB'): float|int
    {
        $len = strlen($bytes);

        if ($len < 2) {
            return 0;
        }

        switch ($dataType) {
            case 'float32':
                if ($len < 4) {
                    return 0.0;
                }
                $ordered = $this->arrangeByteOrder(substr($bytes, 0, 4), $byteOrder);
                $unpacked = unpack('G', $ordered);

                return isset($unpacked[1]) && ! is_nan($unpacked[1]) && ! is_infinite($unpacked[1]) ? (float) $unpacked[1] : 0.0;

            case 'uint16':
                $unpacked = unpack('n', substr($bytes, 0, 2));

                return (int) $unpacked[1];

            case 'int16':
                $u = unpack('n', substr($bytes, 0, 2))[1];

                return ($u >= 0x8000) ? (int) ($u - 0x10000) : (int) $u;

            case 'uint32':
                if ($len < 4) {
                    return 0;
                }
                $ordered = $this->arrangeByteOrder(substr($bytes, 0, 4), $byteOrder);
                $unpacked = unpack('N', $ordered);

                return (int) $unpacked[1];

            case 'int32':
                if ($len < 4) {
                    return 0;
                }
                $ordered = $this->arrangeByteOrder(substr($bytes, 0, 4), $byteOrder);
                $u = unpack('N', $ordered)[1];

                return ($u >= 0x80000000) ? (int) ($u - 0x100000000) : (int) $u;

            default:
                $unpacked = unpack('n', substr($bytes, 0, 2));

                return (int) $unpacked[1];
        }
    }

    /**
     * Atur urutan byte berdasarkan byte order 4-byte (ABCD, CDAB, BADC, DCBA).
     */
    protected function arrangeByteOrder(string $fourBytes, string $order): string
    {
        $b1 = $fourBytes[0];
        $b2 = $fourBytes[1];
        $b3 = $fourBytes[2];
        $b4 = $fourBytes[3];

        return match (strtoupper($order)) {
            'CDAB' => $b3.$b4.$b1.$b2, // Word swap
            'BADC' => $b2.$b1.$b4.$b3, // Byte swap
            'DCBA' => $b4.$b3.$b2.$b1, // Little Endian
            default => $b1.$b2.$b3.$b4, // ABCD - Big Endian
        };
    }

    /**
     * Bangkitkan nilai simulasi realistis jika perangkat fisik offline.
     */
    protected function generateSimulatedValue(Device $device, Register $register): float
    {
        $index = $device->slave_id;
        $seed = time() + $index;

        return match ($register->parameter_key) {
            'totalizer' => round(124.500 + ($index * 42.3) + ((time() % 3600) * 0.002), 3),
            'flow_rate' => round(max(0.1, 3.8 + (sin($seed / 8) * 1.2) + (($index % 2) * 1.5)), 2),
            'voltage' => round(221.5 + (sin($seed / 10) * 1.5) + (($index % 3) * 0.8), 1),
            'current' => round(14.0 + (cos($seed / 15) * 3.0) + ($index * 2.5), 1),
            'power' => round(5.2 + (sin($seed / 20) * 1.5) + ($index * 1.8), 2),
            'frequency' => round(50.00 + (sin($seed / 5) * 0.03), 2),
            'power_factor' => round(0.95 + (cos($seed / 30) * 0.03), 2),
            'energy' => round(1250.0 + ($index * 320) + ((time() % 3600) / 100), 1),
            default => round(10.0 + ($index * 2), 2),
        };
    }
}
