<?php

namespace App\Console\Commands;

use App\Constants\CommonConst;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Modules\RolePermission\Models\Role;
use Modules\RolePermission\Models\Permission;
use Modules\RolePermission\Constants\RolePermissionConst;
use Exception;

class CreateSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-superadmin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a superadmin user with predefined credentials';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = CommonConst::TESTING_EMAIL;
        $password =  CommonConst::TESTING_EMAIL_PASSWORD;
        $name = 'Super Admin';

        DB::beginTransaction();

        try {
            // Check if user already exists
            $existingUser = User::where('email', $email)->first();

            if ($existingUser) {
                $this->warn("User with email {$email} already exists!");

                if ($this->confirm('Do you want to update the existing user with superadmin role?')) {
                    $user = $existingUser;
                    $user->password = Hash::make($password);
                    $user->save();
                    $this->info("Password updated for existing user: {$email}");
                } else {
                    $this->info('Operation cancelled.');
                    return 0;
                }
            } else {
                // Create new user
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'user_name' => $email,
                    'status' => "active",
                    'salary' => 0,
                    'phone' => '9876919999',
                    'password' => Hash::make($password),
                    'status' => "active", // Active status
                    'email_verified_at' => now(),
                ]);

                $this->info("User created successfully: {$email}");
            }

            // Find or create the Super Admin role
            $superAdminRole = Role::where('name', RolePermissionConst::SUPER_ADMIN)->first();

            if (!$superAdminRole) {
                $this->info('Super Admin role not found. Creating it now...');

                // Create the Super Admin role
                $superAdminRole = Role::create([
                    'name' => RolePermissionConst::SUPER_ADMIN,
                    'slug' => RolePermissionConst::SLUG_SUPER_ADMIN,
                    'description' => 'Full access to all system features and settings.',
                    'position' => 0
                ]);

                $this->info('Super Admin role created successfully.');

                // Get all permissions and assign them to Super Admin role
                $allPermissions = Permission::all();

                if ($allPermissions->count() > 0) {
                    $superAdminRole->permissions()->sync($allPermissions->pluck('id'));
                    $this->info("Assigned {$allPermissions->count()} permissions to Super Admin role.");
                } else {
                    $this->warn('No permissions found in the system. Super Admin role created without permissions.');
                    $this->info('You may need to run: php artisan user:role to set up permissions first.');
                }
            }

            // Assign Super Admin role to user
            if (!$user->roles()->where('role_id', $superAdminRole->id)->exists()) {
                $user->roles()->attach($superAdminRole->id);
                $this->info("Super Admin role assigned to user: {$email}");
            } else {
                $this->info("User {$email} already has Super Admin role.");
            }

            DB::commit();

            $this->info('✅ Super Admin user setup completed successfully!');
            $this->line('');
            $this->line('📧 Email: ' . $email);
            $this->line('🔑 Password: ' . $password);
            $this->line('👤 Role: Super Admin');

            return 0;
        } catch (Exception $e) {
            DB::rollBack();
            $this->error("Error creating super admin user: {$e->getMessage()}");
            return 1;
        }
    }
}
