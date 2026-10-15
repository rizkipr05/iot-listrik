<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\MeterLog;

class DashboardController extends Controller
{
    /**
     * Tampilkan halaman utama monitoring kelistrikan.
     */
    public function index()
    {
        $devices = Device::with(['registers' => function ($query) {
            $query->active();
        }])->active()->get();

        $recentLogs = MeterLog::with('device')
            ->latest('logged_at')
            ->take(8)
            ->get();

        return view('dashboard', compact('devices', 'recentLogs'));
    }
}
