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
        Schema::table('meter_logs', function (Blueprint $table) {
            $table->double('ph', 6, 2)->nullable()->after('flow_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meter_logs', function (Blueprint $table) {
            $table->dropColumn('ph');
        });
    }
};
