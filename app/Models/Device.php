<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'description',
        'ip_address',
        'port',
        'slave_id',
        'timeout_ms',
        'is_active',
    ];

    protected $casts = [
        'port' => 'integer',
        'slave_id' => 'integer',
        'timeout_ms' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke daftar register Modbus.
     */
    public function registers(): HasMany
    {
        return $this->hasMany(Register::class);
    }

    /**
     * Scope untuk device yang aktif saja.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Cek apakah device bertipe flow meter / meter air.
     */
    public function isFlowMeter(): bool
    {
        return in_array($this->category, ['flow_meter', 'water_meter', 'digital_water_meter'])
            || str_contains(strtolower($this->name), 'flow')
            || str_contains(strtolower($this->name), 'air');
    }

    /**
     * Cek apakah device bertipe sensor pH.
     */
    public function isPhSensor(): bool
    {
        return $this->category === 'ph_sensor'
            || str_contains(strtolower($this->name), 'ph');
    }

    /**
     * Cek apakah device bertipe meter air / flow meter (alias kompatibilitas).
     */
    public function isWater(): bool
    {
        return $this->isFlowMeter();
    }

    /**
     * Label kategori untuk tampilan UI.
     */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'flow_meter' => 'Electromagnetic Flowmeter (Flowrate & Totalizer)',
            'ph_sensor' => 'pH Sensor (0 ~ 14 pH)',
            'water_meter' => 'Meter Air (Totalizer)',
            'digital_water_meter' => 'Meter Air Digital (Totalizer & Flow)',
            'kwh_meter' => 'kWh Meter (Total kWh, V, A)',
            'power_meter' => 'Power Meter (Total kWh, V, A, kW)',
            default => 'Instrumen Meter',
        };
    }
}
