<?php

namespace App\Providers;

use App\Models\Device;
use App\Services\ModbusTcpService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        date_default_timezone_set(config('app.timezone', 'Asia/Jakarta'));

        View::composer(['layouts.app', 'devices.index'], function ($view) {
            try {
                $primaryDevice = Device::active()->first() ?? Device::first();
                $ip = $primaryDevice?->ip_address ?? '192.168.1.118';
                $port = $primaryDevice?->port ?? 502;
                $isOnline = $primaryDevice ? app(ModbusTcpService::class)->isGatewayReachable($ip, $port) : false;

                $view->with('activeGatewayIp', $ip);
                $view->with('activeGatewayPort', $port);
                $view->with('isActiveGatewayOnline', $isOnline);
            } catch (\Throwable $e) {
                $view->with('activeGatewayIp', '192.168.1.118');
                $view->with('activeGatewayPort', 502);
                $view->with('isActiveGatewayOnline', false);
            }
        });
    }
}
