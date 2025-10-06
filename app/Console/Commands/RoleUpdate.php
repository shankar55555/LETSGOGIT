<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Modules\AlertAndNotification\Models\Rule;
use Modules\RolePermission\Constants\RolePermissionConst;
use Modules\RolePermission\Models\Role;

# OptionUpdate
class RoleUpdate extends Command
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
        // $this->optimizeRolesUpdate();
        // return;

        $option = $this->argument('option');

        $options = [
            'all' => 'Run all update functions (email, role, rule)',
            'role' => 'Sync and update all role definitions',
            'rule' => 'Sync and update all rule definitions',
        ];

        if (!$option) {
            $optionKeys = array_keys($options);

            # Combine key + description for better display
            $displayOptions = array_map(
                fn($key) => "$key - {$options[$key]}",
                $optionKeys
            );

            $selectedIndex = $this->choice(
                'Which update option would you like to run?',
                $displayOptions,
                0
            );

            # Extract the real option key (e.g., 'all') before the " - " part
            $option = explode(' - ', $selectedIndex)[0];
        }

        match ($option) {
            'role' => $this->runRoleUpdate(),
            'rule' => $this->runRuleUpdate(),
            'all'  => $this->runAllUpdates(),
            default => $this->error("Invalid option: $option"),
        };

        return Command::SUCCESS;
    }

    protected function optimizeRolesUpdate()
    {
        $slug = "eligo-admin";
        Role::where('slug', $slug)->update(['slug' => RolePermissionConst::SLUG_SUPER_ADMIN]);
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
        $this->runRoleUpdate();
        $this->optimizeRules();
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
                'description' => $role['description'],
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

        # Step 1: Get existing relevant rule IDs from constant definitions
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

        # Step 2: Delete rules not in the current defined rule set
        Rule::whereNotIn('id', $rule_ids)->delete();

        # Step 3: Sync rules from RULE_ITEM constants
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
