<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;

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
     * Tes koneksi socket TCP ke IP dan Port gateway device.
     */
    public function testConnection(Device $device)
    {
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

        if ($fp) {
            fclose($fp);

            return response()->json([
                'success' => true,
                'message' => "Koneksi ke {$device->ip_address}:{$device->port} BERHASIL ({$durationMs} ms).",
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => "Gagal terhubung ke {$device->ip_address}:{$device->port}. Error: {$errstr} ({$errno}).",
        ]);
    }
}
