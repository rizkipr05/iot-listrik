<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeterLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'voltage',
        'current',
        'power',
        'frequency',
        'power_factor',
        'energy',
        'totalizer',
        'flow_rate',
        'ph',
        'raw_parameters',
        'is_simulated',
        'logged_at',
    ];

    protected $casts = [
        'voltage' => 'float',
        'current' => 'float',
        'power' => 'float',
        'frequency' => 'float',
        'power_factor' => 'float',
        'energy' => 'float',
        'totalizer' => 'float',
        'flow_rate' => 'float',
        'ph' => 'float',
        'raw_parameters' => 'array',
        'is_simulated' => 'boolean',
        'logged_at' => 'datetime',
    ];

    /**
     * Relasi ke device meter terkait.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
