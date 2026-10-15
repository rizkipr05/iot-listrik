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
        // Tabel Pengaturan Global Sistem (Interval Data Logger & Sistem)
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Tabel Data Logger Meter Listrik
        Schema::create('meter_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();

            // Parameter Utama Teragregasi (untuk pencarian cepat & grafik)
            $table->double('voltage', 10, 2)->nullable();       // Tegangan (V)
            $table->double('current', 10, 2)->nullable();       // Arus (A)
            $table->double('power', 12, 3)->nullable();         // Daya Aktif (kW)
            $table->double('frequency', 6, 2)->nullable();      // Frekuensi (Hz)
            $table->double('power_factor', 6, 3)->nullable();   // Power Factor (Cos Phi)
            $table->double('energy', 16, 3)->nullable();        // Energi Total (kWh)

            // Detail JSON seluruh register yang terbaca
            $table->json('raw_parameters')->nullable();

            $table->boolean('is_simulated')->default(false);
            $table->timestamp('logged_at')->index();
            $table->timestamps();

            // Index gabungan untuk query filter periode per device yang sangat cepat
            $table->index(['device_id', 'logged_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meter_logs');
        Schema::dropIfExists('app_settings');
    }
};
