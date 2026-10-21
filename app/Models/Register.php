<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Register extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'name',
        'parameter_key',
        'register_address',
        'function_code',
        'data_type',
        'byte_order',
        'multiplier',
        'unit',
        'is_active',
    ];

    protected $casts = [
        'device_id' => 'integer',
        'register_address' => 'integer',
        'function_code' => 'integer',
        'multiplier' => 'float',
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke Device pemilik register.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Scope register aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Dapatkan label Function Code.
     */
    public function getFunctionCodeLabelAttribute(): string
    {
        return $this->function_code === 4
            ? 'FC04 (Read Input Register)'
            : 'FC03 (Read Holding Register)';
    }

    /**
     * Dapatkan label Kategori Parameter.
     */
    public function getParameterKeyLabelAttribute(): string
    {
        return match ($this->parameter_key) {
            'totalizer' => 'Totalizer (m³)',
            'flow_rate' => 'Flow Rate (m³/h)',
            'pressure' => 'Tekanan (Bar)',
            'ph' => 'Nilai pH (pH)',
            'voltage' => 'Tegangan (V)',
            'current' => 'Arus (A)',
            'power' => 'Daya Aktif (kW)',
            'energy' => 'Total kWh (kWh)',
            'frequency' => 'Frekuensi (Hz)',
            'power_factor' => 'Power Factor (Cos Phi)',
            default => 'Lainnya',
        };
    }
}
