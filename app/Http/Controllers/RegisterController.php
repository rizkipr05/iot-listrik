<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Register;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    /**
     * Tampilkan daftar konfigurasi register Modbus.
     */
    public function index(Request $request)
    {
        $devices = Device::orderBy('name')->get();
        $selectedDeviceId = $request->query('device_id');

        $query = Register::with('device')->latest();

        if ($selectedDeviceId) {
            $query->where('device_id', $selectedDeviceId);
        }

        $registers = $query->paginate(15)->withQueryString();

        return view('registers.index', compact('registers', 'devices', 'selectedDeviceId'));
    }

    /**
     * Form tambah register baru.
     */
    public function create(Request $request)
    {
        $devices = Device::orderBy('name')->get();
        $selectedDeviceId = $request->query('device_id');

        return view('registers.create', compact('devices', 'selectedDeviceId'));
    }

    /**
     * Simpan register baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'device_id' => ['required', 'exists:devices,id'],
            'name' => ['required', 'string', 'max:100'],
            'parameter_key' => ['required', 'string', 'in:voltage,current,power,energy,frequency,power_factor,totalizer,flow_rate,ph,pressure,other'],
            'register_address' => ['required', 'integer', 'min:0'],
            'function_code' => ['required', 'integer', 'in:3,4'],
            'data_type' => ['required', 'string', 'in:float32,int16,uint16,int32,uint32'],
            'byte_order' => ['required', 'string', 'in:ABCD,CDAB,BADC,DCBA'],
            'multiplier' => ['required', 'numeric'],
            'unit' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'device_id.required' => 'Pilih device meter yang sesuai.',
            'name.required' => 'Nama parameter register wajib diisi.',
            'register_address.required' => 'Alamat register Modbus wajib diisi.',
            'function_code.required' => 'Pilih Function Code (FC03 atau FC04).',
        ]);

        $validated['is_active'] = $request->has('is_active');

        Register::create($validated);

        return redirect()->route('registers.index', ['device_id' => $validated['device_id']])
            ->with('success', 'Register Modbus berhasil didaftarkan.');
    }

    /**
     * Form edit register.
     */
    public function edit(Register $register)
    {
        $devices = Device::orderBy('name')->get();

        return view('registers.edit', compact('register', 'devices'));
    }

    /**
     * Perbarui konfigurasi register.
     */
    public function update(Request $request, Register $register)
    {
        $validated = $request->validate([
            'device_id' => ['required', 'exists:devices,id'],
            'name' => ['required', 'string', 'max:100'],
            'parameter_key' => ['required', 'string', 'in:voltage,current,power,energy,frequency,power_factor,totalizer,flow_rate,ph,pressure,other'],
            'register_address' => ['required', 'integer', 'min:0'],
            'function_code' => ['required', 'integer', 'in:3,4'],
            'data_type' => ['required', 'string', 'in:float32,int16,uint16,int32,uint32'],
            'byte_order' => ['required', 'string', 'in:ABCD,CDAB,BADC,DCBA'],
            'multiplier' => ['required', 'numeric'],
            'unit' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->has('is_active');

        $register->update($validated);

        return redirect()->route('registers.index', ['device_id' => $validated['device_id']])
            ->with('success', 'Konfigurasi register Modbus berhasil diperbarui.');
    }

    /**
     * Hapus register.
     */
    public function destroy(Register $register)
    {
        $deviceId = $register->device_id;
        $name = $register->name;
        $register->delete();

        return redirect()->route('registers.index', ['device_id' => $deviceId])
            ->with('success', "Register '{$name}' berhasil dihapus.");
    }
}
