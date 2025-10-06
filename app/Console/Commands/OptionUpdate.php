<?php

namespace App\Console\Commands;

use App\Constants\CommonConst;
use App\Models\User;
use Illuminate\Console\Command;
use Modules\AlertAndNotification\Models\NotificationCategory;
use Modules\AlertAndNotification\Models\NotificationTemplateSection;
use Modules\AlertAndNotification\Models\NotificationType;
use Modules\AlertAndNotification\Models\Rule;
use Modules\RolePermission\Constants\RolePermissionConst;
use Modules\RolePermission\Models\Role;

class OptionUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'option:update {option? : The update option to run (email|role|all|rule)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command manages multiple project update tasks (email, role, etc.)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $option = $this->argument('option');

        $options = [
            'all' => 'Run all update functions (email, role, rule)',
            'system-email' => 'Only System email update templates',
            'role' => 'Sync and update all role definitions',
            'rule' => 'Sync and update all rule definitions',
        ];

        if (!$option) {
            $optionKeys = array_keys($options);

            $displayOptions = array_map(
                fn($key) => "$key - {$options[$key]}",
                $optionKeys
            );

            $selectedIndex = $this->choice(
                'Which update option would you like to run?',
                $displayOptions,
                0
            );

            // Extract the real option key (e.g., 'all') before the " - " part
            $option = explode(' - ', $selectedIndex)[0];
        }

        match ($option) {
            'system-email' => $this->runSystemEmailUpdate(),
            'role' => $this->runRoleUpdate(),
            'rule' => $this->runRuleUpdate(),
            'all'  => $this->runAllUpdates(),
            default => $this->error("Invalid option: $option"),
        };

        return Command::SUCCESS;
    }
    // public function handle()
    // {
    //     $option = $this->argument('option');

    //     $options = [
    //         'all' => 'Run all update functions (email, role, rule)',
    //         'system-email' => 'Only System email Update templates ',
    //         'role' => 'Sync and update all role definitions',
    //         'rule' => 'Sync and update all rule definitions',
    //     ];

    //     if (!$option) {
    //         $option = $this->choice(
    //             'Which update option would you like to run?',
    //             array_keys($options),
    //             0
    //         );
    //     }

    //     match ($option) {
    //         'system-email' => $this->runSystemEmailUpdate(),
    //         'role' => $this->runRoleUpdate(),
    //         'rule' => $this->runRuleUpdate(),
    //         'all'  => $this->runAllUpdates(),
    //         default => $this->error("Invalid option: $option"),
    //     };

    //     return Command::SUCCESS;
    // }

    protected function runSystemEmailUpdate()
    {
        $this->info('🔁 Running email template update...');
        $this->onlySystemEmailUpdate();
        $this->info('✅ Email templates updated successfully.');
    }

    protected function runRoleUpdate()
    {
        $this->info('🔁 Syncing roles...');
        $this->optimizeRoles();
        $this->info('✅ Roles updated successfully.');
    }

    protected function runRuleUpdate()
    {
        $this->info('🔁 Syncing Rule...');
        $this->optimizeRules();
        $this->info('✅ Rule updated successfully.');
    }

    protected function runAllUpdates()
    {
        $this->runSystemEmailUpdate();
        $this->runRoleUpdate();
        $this->optimizeRules();
    }

    /**
     * Restore soft-deleted notification categories and types from constants.
     *
     * This method reads the email template list from the constant `ACCOUNT_EMAIL_LIST`,
     * parses the associated categories and type keys, then marks them as active
     * (`is_delete = false`) in the database.
     *
     * Steps:
     * - Load email templates from constants via `readConstFileList`
     * - Extract `category` and `type_key` values
     * - Update matching `NotificationCategory` and `NotificationType` records
     */
    protected function onlySystemEmailUpdate()
    {
        $email_List = CommonConst::ACCOUNT_EMAIL_LIST;
        $params = ["name" => "EMAIL_TEMPLATE", "list" => $email_List, "position" => false];
        $email_List = readConstFileList(...$params);

        $category_ids = [];
        $type_ids = [];

        foreach ($email_List as $info) {
            // $category_ids[] = NotificationCategory::where('category', $info['category'])->value('id');
            NotificationCategory::where('category', $info['category'])->update(['is_delete' => $info['is_delete']]);
            foreach ($info['type'] as $type) {
                // $type_ids[] = NotificationType::where('type_key', $type['type_key'])->value('id');
                NotificationType::where('type_key', $type['type_key'])->update(['is_delete' => $type['is_delete']]);
                $notification_type_id = NotificationType::where('type_key', $type['type_key'])->value('id');
                NotificationTemplateSection::where('notification_type_id', $notification_type_id)->update(['title' => $type['template']['title']]);
            }
        }

        NotificationCategory::whereIn('id', array_filter($category_ids))->update(['is_delete' => false]);
        NotificationType::whereIn('id', array_filter($type_ids))->update(['is_delete' => false]);
    }

    /**
     * Synchronize predefined roles into the roles table.
     *
     * This method reads the role list from the constant `ROLE_LIST`, and creates or updates
     * each role using its name, slug, description, and position. It also sets the `created_by`
     * field to the first user ID found.
     *
     * A progress bar is displayed during the update process.
     */
    protected function optimizeRoles()
    {
        $roles = RolePermissionConst::ROLE_LIST;
        $created_by = User::value('id');

        $bar = $this->output->createProgressBar(count($roles));
        $bar->start();

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], [
                'name' => $role['name'],
                'slug' => $role['slug'],
                'position' => $role['position'],
                'description' => $role['description'] ?? '',
                'created_by' => $created_by,
            ]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Optimize and sync system rules from constant files.
     *
     * This method performs two major tasks:
     * 
     * 1. **Cleanup Unused Rules:**
     *    - Loads the rule structure from the constant file `RULE`.
     *    - For each rule slug and its trigger events, it gathers all rule IDs that belong to the defined module.
     *    - Any `Rule` in the database *not present in this set of rule IDs* is deleted.
     *
     * 2. **Sync Rule Items:**
     *    - Reads rule definitions from the constant file `RULE_ITEM`.
     *    - Uses `updateOrCreate` to sync each rule based on its `rule_slug`.
     *    - Displays a progress bar to indicate progress.
     */
    protected function optimizeRules()
    {
        $createdBy = adminUserId()[0];

        // Step 1: Get existing relevant rule IDs from constant definitions
        $ruleList = readConstFileList('RULE', [], false);
        $rule_ids = [];

        foreach ($ruleList as $item) {
            $module = $item['module'];
            foreach ($item['trigger_event'] as $event) {
                $ids = Rule::where('rule_slug', $event['slug'])
                    ->get()
                    ->filter(function ($rule) use ($module) {
                        $conditions = json_decode($rule->conditions, true);
                        return collect($conditions)->contains(function ($condition) use ($module) {
                            return $condition['module'] === $module;
                        });
                    })->pluck('id')->toArray();

                $rule_ids = array_unique(array_merge($rule_ids, $ids));
            }
        }

        // Step 2: Delete rules not in the current defined rule set
        Rule::whereNotIn('id', $rule_ids)->delete();

        // Step 3: Sync rules from RULE_ITEM constants
        $params = ["name" => "RULE_ITEM", "list" => [], "position" => false];
        $ruleItems = readConstFileList(...$params);

        $bar = $this->output->createProgressBar(count($ruleItems));
        $bar->start();

        foreach ($ruleItems as $rule) {
            if (empty($rule['rule'])) {
                $this->warn("Skipping rule due to missing 'rule' field: " . json_encode($rule));
                continue;
            }

            Rule::updateOrCreate(['rule_slug' => $rule['rule_slug']], $rule);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }
}
