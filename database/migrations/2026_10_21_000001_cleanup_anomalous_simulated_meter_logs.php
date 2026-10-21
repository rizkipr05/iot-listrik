<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Bersihkan data anomali lama dari database (data simulasi dan anomali nilai pH 7.x pada sensor tekanan).
     */
    public function up(): void
    {
        DB::table('meter_logs')
            ->where('is_simulated', true)
            ->orWhere('ph', '>=', 6.0)
            ->orWhere('flow_rate', '>=', 50.0)
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pembersihan data tidak perlu di-rollback
    }
};
