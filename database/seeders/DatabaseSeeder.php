<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator IoT',
                'email' => 'admin@iotlistrik.local',
                'password' => Hash::make('admin123'),
            ]
        );

        $this->call(DeviceAndRegisterSeeder::class);
    }
}
