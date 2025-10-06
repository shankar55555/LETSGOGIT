<?php

namespace Modules\AlertAndNotification\Database\Seeders;

use Illuminate\Database\Seeder;

class AlertAndNotificationDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            EmailSeeder::class,
        ]);
    }
}
