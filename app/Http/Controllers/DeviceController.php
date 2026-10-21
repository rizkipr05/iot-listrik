<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\MeterLog;
use App\Services\ModbusTcpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DeviceController extends Controller
{
    /**
     * Tampilkan daftar device meter.
     */
    public function index()
    {
        $devices = Device::withCount('registers')->latest()->get();

        return view('devices.index', compact('devices'));
    }

    /**
     * Form tambah device baru.
     */
    public function create()
    {
        return view('devices.create');
    }

    /**
     * Simpan device baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['required', 'string', 'max:45'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'slave_id' => ['required', 'integer', 'min:1', 'max:247'],
            'timeout_ms' => ['required', 'integer', 'min:200', 'max:10000'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama device wajib diisi.',
            'ip_address.required' => 'Alamat IP Gateway wajib diisi.',
            'port.required' => 'Port Modbus wajib diisi.',
            'slave_id.required' => 'Slave ID (Unit ID) wajib diisi.',
            'slave_id.between' => 'Slave ID harus antara 1 sampai 247.',
        ]);

        $validated['is_active'] = $request->has('is_active');

        Device::create($validated);

        return redirect()->route('devices.index')->with('success', 'Device meter berhasil ditambahkan.');
    }

    /**
     * Form edit device.
     */
    public function edit(Device $device)
    {
        return view('devices.edit', compact('device'));
    }

    /**
     * Perbarui data device.
     */
    public function update(Request $request, Device $device)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['required', 'string', 'max:45'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'slave_id' => ['required', 'integer', 'min:1', 'max:247'],
            'timeout_ms' => ['required', 'integer', 'min:200', 'max:10000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->has('is_active');

        $device->update($validated);

        return redirect()->route('devices.index')->with('success', 'Konfigurasi device berhasil diperbarui.');
    }

    /**
     * Hapus device beserta registernya.
     */
    public function destroy(Device $device)
    {
        $name = $device->name;
        $device->delete();

        return redirect()->route('devices.index')->with('success', "Device '{$name}' dan registernya berhasil dihapus.");
    }

    /**
     * Tes koneksi socket TCP ke IP dan Port gateway device, serta uji respon Slave ID RS485 jika memiliki register.
     */
    public function testConnection(Device $device, ModbusTcpService $modbusService)
    {
        $modbusService->clearGatewayCache($device->ip_address, $device->port);
        $timeoutSeconds = ($device->timeout_ms ?? 1500) / 1000;
        $errno = 0;
        $errstr = '';

        $startTime = microtime(true);
        $fp = @stream_socket_client(
            "tcp://{$device->ip_address}:{$device->port}",
            $errno,
            $errstr,
            $timeoutSeconds
        );
        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        if (! $fp) {
            Cache::put("device_online_{$device->id}", false, 30);

            return response()->json([
                'success' => false,
                'message' => "Gagal terhubung ke Gateway {$device->ip_address}:{$device->port}. Error: {$errstr} ({$errno}).",
            ]);
        }

        fclose($fp);

        // Jika gateway aktif dan device memiliki register aktif, uji respon query Modbus ke Slave ID di jalur loop RS485
        $firstRegister = $device->registers()->where('is_active', true)->first();
        if ($firstRegister) {
            $readResult = $modbusService->readRegister($device, $firstRegister, false);
            if ($readResult['success']) {
                $val = (float) ($readResult['value'] ?? 0);
                Cache::put("device_online_{$device->id}", true, 300);

                // Segera simpan nilai hasil pembacaan real ke cache telemetri agar instan tampil di dashboard
                $prevCached = Cache::get("device_latest_telemetry_{$device->id}", []);
                $prevCached['is_online'] = true;
                $prevCached['updated_at'] = now()->toIso8601String();

                if ($device->isPressureSensor() || $device->isPhSensor()) {
                    $prevCached['pressure'] = $val;
                    $prevCached['ph'] = $val;

                    $scada = Cache::get('scada_latest_telemetry', []);
                    $status = ($val >= 1.0 && $val <= 4.0) ? 'Normal' : ($val > 0 ? 'Warning' : 'Standby');
                    $scada['pressure'] = ['value' => $val, 'status' => $status, 'is_online' => true];
                    $scada['ph'] = ['value' => $val, 'status' => $status, 'is_online' => true];
                    Cache::put('scada_latest_telemetry', $scada, 86400);

                    // Catat ke MeterLog agar historis dan grafik langsung menangkap nilai real
                    MeterLog::create([
                        'device_id' => $device->id,
                        'ph' => $val,
                        'is_simulated' => false,
                        'logged_at' => now(),
                    ]);
                } elseif ($device->isFlowMeter() || $device->isWater()) {
                    $paramKey = $firstRegister->parameter_key ?? 'flow_rate';
                    if ($paramKey === 'flow_rate') {
                        $prevCached['flow_rate'] = $val;
                    } elseif ($paramKey === 'totalizer') {
                        $prevCached['totalizer'] = $val;
                    }
                }
                Cache::put("device_latest_telemetry_{$device->id}", $prevCached, 86400);

                return response()->json([
                    'success' => true,
                    'message' => "Koneksi ke Gateway ({$device->ip_address}:{$device->port}) dan respon RS485 Slave ID {$device->slave_id} BERHASIL ({$durationMs} ms). Nilai '{$firstRegister->name}': {$readResult['value']} {$readResult['unit']}.",
                ]);
            }

            Cache::put("device_online_{$device->id}", false, 30);

            return response()->json([
                'success' => false,
                'message' => "Koneksi socket TCP Gateway BERHASIL ({$durationMs} ms), namun Slave ID {$device->slave_id} pada loop RS485 tidak merespon: {$readResult['message']}.",
            ]);
        }

        Cache::put("device_online_{$device->id}", true, 30);

        return response()->json([
            'success' => true,
            'message' => "Koneksi ke Gateway {$device->ip_address}:{$device->port} BERHASIL ({$durationMs} ms).",
        ]);
    }
}
