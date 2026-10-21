<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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
        if (in_array($this->category, ['flow_meter', 'water_meter', 'digital_water_meter'])) {
            return true;
        }

        $text = strtolower($this->name.' '.$this->description);
        if (str_contains($text, 'flow')
            || str_contains($text, 'air')
            || str_contains($text, 'meter')
            || str_contains($text, 'water')
            || str_contains($text, 'chiller')
            || str_contains($text, 'toilet')
            || str_contains($text, 'office')
            || str_contains($text, 'debit')
            || str_contains($text, 'wm')) {
            return true;
        }

        if ($this->relationLoaded('registers')) {
            return $this->registers->contains(fn ($r) => in_array($r->parameter_key, ['flow_rate', 'totalizer', 'flow', 'volume']) || in_array(strtolower($r->unit ?? ''), ['m3/h', 'm³/h', 'm3', 'm³', 'l/min', 'l/h', 'lps', 'gpm']));
        }

        return false;
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
     * Cek apakah device bertipe sensor tekanan / pressure.
     */
    public function isPressureSensor(): bool
    {
        if ($this->category === 'pressure_sensor') {
            return true;
        }

        $text = strtolower($this->name.' '.$this->description);
        if (preg_match('/\b(pressure|bar|tekanan|press|psi)\b/i', $text)) {
            return true;
        }

        if ($this->relationLoaded('registers')) {
            return $this->registers->contains(fn ($r) => in_array($r->parameter_key, ['pressure', 'press', 'tekanan']) || in_array(strtolower($r->unit ?? ''), ['bar', 'psi', 'kpa', 'mpa']));
        }

        if ($this->exists) {
            try {
                return $this->registers()->where(function ($q) {
                    $q->whereIn('parameter_key', ['pressure', 'press', 'tekanan'])
                        ->orWhereIn('unit', ['Bar', 'bar', 'psi', 'PSI', 'kPa', 'kpa', 'MPa', 'mpa']);
                })->exists();
            } catch (\Throwable) {
                return false;
            }
        }

        return false;
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
            'pressure_sensor' => 'Pressure Sensor (0 ~ 10 Bar)',
            'water_meter' => 'Meter Air (Totalizer)',
            'digital_water_meter' => 'Meter Air Digital (Totalizer & Flow)',
            'kwh_meter' => 'kWh Meter (Total kWh, V, A)',
            'power_meter' => 'Power Meter (Total kWh, V, A, kW)',
            default => 'Instrumen Meter',
        };
    }

    /**
     * Label badge ringkas untuk tabel log riwayat dan antarmuka.
     */
    public function getCategoryBadgeAttribute(): string
    {
        return match ($this->category) {
            'flow_meter' => 'Flowmeter',
            'ph_sensor' => 'pH Sensor',
            'pressure_sensor' => 'Pressure Sensor',
            'water_meter' => 'Meter Air',
            'digital_water_meter' => 'Meter Air Digital',
            'kwh_meter' => 'kWh Meter',
            'power_meter' => 'Power Meter',
            default => 'Meter',
        };
    }

    /**
     * Memetakan daftar perangkat menjadi 4 instrumen SCADA standar:
     * - rms (Card 1): Flowmeter Chiller / Utama
     * - mhs (Card 2): Water meter Toilet / Sekunder
     * - hrms (Card 3): Water meter Office / Tersier
     * - pressure (Card 4): Pressure Sensor / Sensor Tekanan Pipa
     *
     * @param  Collection<int, Device>|null  $devices
     * @return array{rms: ?Device, mhs: ?Device, hrms: ?Device, pressure: ?Device}
     */
    public static function resolveScadaInstruments(?Collection $devices = null): array
    {
        if ($devices === null) {
            $devices = static::active()->orderBy('id')->get();
        }

        // 1. Identifikasi Sensor Tekanan / pH terlebih dahulu (Card 4)
        $pressureDev = $devices->first(fn ($d) => $d->isPressureSensor() || $d->isPhSensor())
            ?? $devices->where('slave_id', 4)->first();

        // 2. Kumpulkan instrumen meter aliran (khusus Flow / Water Meter, kecualikan sensor tekanan)
        $flowDevices = $devices->reject(fn ($d) => $pressureDev && $d->id === $pressureDev->id)
            ->filter(fn ($d) => ! ($d->isPressureSensor() || $d->isPhSensor()))
            ->values();

        // Jika tidak ada flow device terpisah yang cocok, gunakan device selain pressureDev
        if ($flowDevices->isEmpty()) {
            $flowDevices = $devices->reject(fn ($d) => $pressureDev && $d->id === $pressureDev->id)->values();
        }

        // 3. Pencocokan spesifik instrumen berdasarkan keyword nama/deskripsi atau Slave ID standar:
        $rmsDev = $flowDevices->first(function ($d) {
            $text = strtolower($d->name.' '.$d->description);

            return (str_contains($text, 'chiller') || str_contains($text, 'rms') || str_contains($text, '2"') || str_contains($text, '2 inch') || str_contains($text, 'dn50') || str_contains($text, 'electromagnetic'))
                && ! str_contains($text, 'h-rms')
                && ! str_contains($text, 'hrms');
        }) ?? $flowDevices->firstWhere('slave_id', 1);

        $mhsDev = $flowDevices->reject(fn ($d) => $rmsDev && $d->id === $rmsDev->id)->first(function ($d) {
            $text = strtolower($d->name.' '.$d->description);

            return str_contains($text, 'toilet') || str_contains($text, 'mhs') || str_contains($text, '3/4') || str_contains($text, 'ultrasonic');
        }) ?? $flowDevices->reject(fn ($d) => $rmsDev && $d->id === $rmsDev->id)->firstWhere('slave_id', 2);

        $hrmsDev = $flowDevices->reject(fn ($d) => ($rmsDev && $d->id === $rmsDev->id) || ($mhsDev && $d->id === $mhsDev->id))->first(function ($d) {
            $text = strtolower($d->name.' '.$d->description);

            return str_contains($text, 'office')
                || str_contains($text, 'hrms')
                || str_contains($text, 'h-rms')
                || str_contains($text, 'dn20')
                || str_contains($text, 'photoelectric')
                || str_contains($text, '1/2');
        }) ?? $flowDevices->reject(fn ($d) => ($rmsDev && $d->id === $rmsDev->id) || ($mhsDev && $d->id === $mhsDev->id))->firstWhere('slave_id', 3);

        // 4. Untuk slot yang belum terisi, distribusikan sisa device yang belum terpakai secara berurutan:
        $assignedIds = collect([$rmsDev?->id, $mhsDev?->id, $hrmsDev?->id])->filter()->all();
        $unassigned = $flowDevices->reject(fn ($d) => in_array($d->id, $assignedIds, true))->values();

        if (! $rmsDev && $unassigned->isNotEmpty()) {
            $rmsDev = $unassigned->shift();
        }
        if (! $mhsDev && $unassigned->isNotEmpty()) {
            $mhsDev = $unassigned->shift();
        }
        if (! $hrmsDev && $unassigned->isNotEmpty()) {
            $hrmsDev = $unassigned->shift();
        }

        return [
            'rms' => $rmsDev,
            'mhs' => $mhsDev,
            'hrms' => $hrmsDev,
            'pressure' => $pressureDev,
        ];
    }
}
