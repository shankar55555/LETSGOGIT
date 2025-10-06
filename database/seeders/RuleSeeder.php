<?php

namespace Database\Seeders;

use App\Constants\CommonConst;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\AlertAndNotification\Models\Rule;
use Modules\RolePermission\Constants\RolePermissionConst;
use Modules\RolePermission\Models\Role;

class RuleSeeder extends Seeder
{
    public function run(): void
    {
        $header_list = [];
        $prams = ["name" => "RULE_ITEM", "list" => $header_list, "position" => false];
        $list = readConstFileList(...$prams);

        $userEmail = CommonConst::TESTING_EMAIL;

        // Get or create the admin user
        $user = User::firstOrCreate(
            ['email' => $userEmail],
            [
                'name' => 'Pankaj Sharma',
                'user_name' => 'super_admin',
                'password' => Hash::make(CommonConst::TESTING_EMAIL_PASSWORD),
                'country_code' => '+91',
                'phone' => '9876919999',
                'avatar' => null,
                'status' => "active",
                'salary' => 0,
                'mark_attendance' => false,
                'email_verified_at' => now(),
                'date_of_birth' => '1990-01-01',
                'anniversary_date' => '2015-01-01',
            ]
        );

        // Assign super admin role if not already assigned
        $roleIds = Role::whereIn('slug', [RolePermissionConst::SLUG_SUPER_ADMIN])->pluck('id')->toArray();
        $user->roles()->syncWithoutDetaching($roleIds);

        $createdBy = $user->uuid;

        $bar = $this->command->getOutput()->createProgressBar(count($list));
        $bar->start();

        $conditionalRules = [
            "client-inactive",
            "follow-up-due",
            "follow-up-overdue",
            "days-before-due",
            "after-due-date",
            "no_action",
            "quotation-expired",
            "site-visit-due"
        ];

        Rule::truncate();

        foreach ($list as $ruleData) {
            if (empty($ruleData['rule'])) {
                $this->command->warn("Skipping rule due to missing 'rule' field: " . json_encode($ruleData));
                continue;
            }

            $ruleData['created_by'] = $createdBy;

            if (!in_array($ruleData['rule_slug'], $conditionalRules)) {
                Rule::create($ruleData);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->info("\nRule seeding completed successfully.");
    }
}
