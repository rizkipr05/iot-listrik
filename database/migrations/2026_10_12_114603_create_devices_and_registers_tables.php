<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabel Devices (Meter / Instrument)
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Contoh: Main Incomer Panel
            $table->string('description')->nullable();
            $table->string('ip_address')->default('192.168.1.100'); // IP Gateway RS485-to-TCP
            $table->integer('port')->default(502); // Modbus TCP Port standar
            $table->unsignedTinyInteger('slave_id')->default(1); // Modbus RTU Slave/Unit ID (1 - 247)
            $table->unsignedInteger('timeout_ms')->default(1500); // Socket timeout dalam millisecond
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tabel Modbus Registers per Device
        Schema::create('registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->string('name'); // Contoh: Tegangan Phase A
            $table->string('parameter_key')->default('other'); // voltage, current, power, energy, frequency, power_factor, other
            $table->unsignedInteger('register_address'); // Contoh: 0, 1, 30001, 40001
            $table->unsignedTinyInteger('function_code')->default(3); // 3 = FC03 Holding Register, 4 = FC04 Input Register
            $table->string('data_type')->default('float32'); // float32, int16, uint16, int32, uint32
            $table->string('byte_order')->default('CDAB'); // ABCD (Big Endian), CDAB (Word Swap / Modbus standard), BADC, DCBA
            $table->double('multiplier', 15, 6)->default(1.0); // Faktor pengali / skala
            $table->string('unit', 20)->nullable(); // V, A, kW, kWh, Hz, PF
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registers');
        Schema::dropIfExists('devices');
    }
};
