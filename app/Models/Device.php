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
     * Cek apakah device bertipe meter air / flow meter.
     */
    public function isWater(): bool
    {
        return in_array($this->category, ['water_meter', 'digital_water_meter', 'flow_meter'])
            || str_contains(strtolower($this->name), 'air')
            || str_contains(strtolower($this->name), 'flow');
    }

    /**
     * Label kategori untuk tampilan UI.
     */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'water_meter' => 'Meter Air (Totalizer)',
            'digital_water_meter' => 'Meter Air Digital (Totalizer & Flow)',
            'flow_meter' => 'Flow Meter (Totalizer & Flow)',
            'kwh_meter' => 'kWh Meter (Total kWh, V, A)',
            'power_meter' => 'Power Meter (Total kWh, V, A)',
            default => 'Meter Listrik',
        };
    }
}
