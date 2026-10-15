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
        Schema::table('devices', function (Blueprint $table) {
            $table->string('category')->default('electricity')->after('name');
        });

        Schema::table('meter_logs', function (Blueprint $table) {
            $table->double('totalizer', 16, 3)->nullable()->after('energy');
            $table->double('flow_rate', 12, 3)->nullable()->after('totalizer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        Schema::table('meter_logs', function (Blueprint $table) {
            $table->dropColumn(['totalizer', 'flow_rate']);
        });
    }
};
