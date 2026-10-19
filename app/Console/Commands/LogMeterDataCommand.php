<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use App\Models\Device;
use App\Models\MeterLog;
use App\Services\ModbusTcpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class LogMeterDataCommand extends Command
{
    /**
     * Nama dan signature perintah console.
     */
    protected $signature = 'meter:log 
                            {--loop : Jalankan secara berkelanjutan sebagai background worker} 
                            {--interval= : Override interval per detik} 
                            {--device= : Catat hanya untuk ID device tertentu}';

    /**
     * Deskripsi perintah.
     */
    protected $description = 'Pencatatan otomatis data telemetri meter listrik ke database meter_logs';

    /**
     * Eksekusi perintah console.
     */
    public function handle(ModbusTcpService $modbusService): int
    {
        $isLoop = $this->option('loop');
        $deviceFilter = $this->option('device');

        $this->info('=== IoT Listrik Data Logger Engine ===');

        do {
            $isEnabled = AppSetting::getValue('logger_enabled', '1') === '1';

            if (! $isEnabled) {
                $this->warn('['.now()->format('H:i:s').'] Data logger dinonaktifkan di pengaturan sistem.');
            } else {
                $query = Device::with(['registers' => function ($q) {
                    $q->active();
                }])->active();

                if ($deviceFilter) {
                    $query->where('id', $deviceFilter);
                }

                $devices = $query->get();

                if ($devices->isEmpty()) {
                    $this->warn('['.now()->format('H:i:s').'] Tidak ada device aktif untuk dicatat.');
                } else {
                    $now = now();
                    $this->line('<comment>['.$now->format('Y-m-d H:i:s').']</comment> Memulai pencatatan '.$devices->count().' device...');

                    foreach ($devices as $device) {
                        // Jangan buat data dummy simulasi jika offline pada production logger
                        $readData = $modbusService->readDeviceRegisters($device, false);

                        if (empty($readData['is_online']) || ! empty($readData['is_simulated'])) {
                            $this->line(" <fg=red>[OFFLINE]</> <comment>{$device->name}</comment> (Slave ID: {$device->slave_id}) tidak merespons pada RS485 loop, dilewati.");

                            continue;
                        }

                        $params = $readData['parameters'];

                        $log = MeterLog::create([
                            'device_id' => $device->id,
                            'voltage' => $params['voltage']['value'] ?? null,
                            'current' => $params['current']['value'] ?? null,
                            'power' => $params['power']['value'] ?? null,
                            'frequency' => $params['frequency']['value'] ?? null,
                            'power_factor' => $params['power_factor']['value'] ?? null,
                            'energy' => $params['energy']['value'] ?? null,
                            'totalizer' => $params['totalizer']['value'] ?? null,
                            'flow_rate' => $params['flow_rate']['value'] ?? null,
                            'ph' => $params['pressure']['value'] ?? $params['ph']['value'] ?? null,
                            'raw_parameters' => $params,
                            'is_simulated' => false,
                            'logged_at' => $now,
                        ]);

                        // Tandai cache agar tidak terduplikasi dengan auto-logger dashboard
                        Cache::put("auto_logger_last_time_{$device->id}", time(), 180);

                        $simTag = '<fg=green>[LIVE]</>';
                        if ($device->isPressureSensor()) {
                            $this->line(" {$simTag} <info>{$device->name}</info> (ID: {$device->slave_id}) => Tekanan: ".($log->ph ?? '-').' Bar');
                        } elseif ($device->isPhSensor()) {
                            $this->line(" {$simTag} <info>{$device->name}</info> (ID: {$device->slave_id}) => Nilai pH: ".($log->ph ?? '-').' pH');
                        } elseif ($device->isWater()) {
                            $this->line(" {$simTag} <info>{$device->name}</info> (ID: {$device->slave_id}) => Flow: ".($log->flow_rate ?? '-').' m3/h, Totalizer: '.($log->totalizer ?? '-').' m3');
                        } else {
                            $this->line(" {$simTag} <info>{$device->name}</info> (ID: {$device->slave_id}) => V: ".($log->voltage ?? '-').'V, I: '.($log->current ?? '-').'A, P: '.($log->power ?? '-').'kW, kWh: '.($log->energy ?? '-').'kWh');
                        }
                    }
                }
            }

            if ($isLoop) {
                $intervalSeconds = (int) ($this->option('interval') ?? AppSetting::getValue('logger_interval_seconds', '60'));
                $intervalSeconds = max(1, $intervalSeconds);

                $this->line("<fg=gray>Menunggu {$intervalSeconds} detik untuk pencatatan berikutnya... (Tekan Ctrl+C untuk berhenti)</>");
                sleep($intervalSeconds);
            }
        } while ($isLoop);

        $this->info('Pencatatan log selesai.');

        return Command::SUCCESS;
    }
}
