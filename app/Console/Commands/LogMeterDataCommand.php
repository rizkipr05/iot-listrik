<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use App\Models\Device;
use App\Models\MeterLog;
use App\Services\ModbusTcpService;
use Illuminate\Console\Command;

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
                        $readData = $modbusService->readDeviceRegisters($device, true);
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
                            'raw_parameters' => $params,
                            'is_simulated' => $readData['is_simulated'] ?? false,
                            'logged_at' => $now,
                        ]);

                        $simTag = $log->is_simulated ? '<fg=yellow>[SIM]</>' : '<fg=green>[LIVE]</>';
                        if ($device->isWater()) {
                            $this->line(" {$simTag} <info>{$device->name}</info> (ID: {$device->slave_id}) => Totalizer: ".($log->totalizer ?? '-').' m3'.($log->flow_rate !== null ? ', Flow: '.$log->flow_rate.' m3/h' : ''));
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
