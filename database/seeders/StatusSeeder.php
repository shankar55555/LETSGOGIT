<?php

namespace Database\Seeders;

use App\Constants\CommonConst;
use Illuminate\Database\Seeder;
use App\Models\AdminControlConfig;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        # Always include RolePermissionConst
        $header_list = CommonConst::USER_MODULE_STATUS_LIST ?? [];
        $prams = ["name" => "MODULE_STATUS", "list" => $header_list, "position" => true];
        $list = readConstFileList(...$prams);

        foreach ($list as $page) {
            foreach ($page['statuses'] as $index => $status) {
                AdminControlConfig::updateOrCreate(
                    [
                        'status_for' => $page['page'],
                        'status_text' => $status['status_text'],
                    ],
                    [
                        'invoice_footer_text' => $status['invoice_footer_text'],
                        'contract_footer_text' => $status['contract_footer_text'],
                        'status_color' => $status['status_color'],
                        'slug' => $status['slug'],
                        'position' =>  (int)$status['position'],
                        'is_predefined' => $status['is_predefined'],
                    ]
                );
            }
        }
    }
}
