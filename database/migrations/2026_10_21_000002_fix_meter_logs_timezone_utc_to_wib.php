<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Konversikan waktu riwayat log yang sebelumnya tercatat dalam timezone UTC (GMT+0) ke WIB (GMT+7, Asia/Jakarta).
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("UPDATE meter_logs SET logged_at = datetime(logged_at, '+7 hours') WHERE logged_at IS NOT NULL");
        } else {
            DB::statement('UPDATE meter_logs SET logged_at = DATE_ADD(logged_at, INTERVAL 7 HOUR) WHERE logged_at IS NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("UPDATE meter_logs SET logged_at = datetime(logged_at, '-7 hours') WHERE logged_at IS NOT NULL");
        } else {
            DB::statement('UPDATE meter_logs SET logged_at = DATE_SUB(logged_at, INTERVAL 7 HOUR) WHERE logged_at IS NOT NULL');
        }
    }
};
