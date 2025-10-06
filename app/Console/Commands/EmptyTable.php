<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EmptyTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:empty-tables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate specific tables for cleanup or reset';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tables = [
            'leads',
            'clients',
            'invoices',
            'quotations',
            'site_visits',
            'site_risk_management',
            'follow_ups',
            'site_risk_media',
        ];

        if (! $this->confirm('This will truncate the following tables: ' . implode(', ', $tables) . '. Are you sure?')) {
            $this->info('Operation cancelled.');
            return;
        }

        // Disable foreign key checks temporarily
        Schema::disableForeignKeyConstraints();

        foreach ($tables as $table) {
            DB::table($table)->truncate();
            $this->info("Truncated: {$table}");
        }

        // Re-enable foreign key checks
        Schema::enableForeignKeyConstraints();

        $this->info('All specified tables have been truncated.');
    }
}
