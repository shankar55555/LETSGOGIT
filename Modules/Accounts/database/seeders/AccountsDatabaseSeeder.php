<?php

namespace Modules\Accounts\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounts\Database\Seeders\AccountSeeder;

class AccountsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            AccountSeeder::class,
        ]);
    }
}
