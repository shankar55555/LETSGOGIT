<?php

namespace Database\Seeders;

use App\Constants\CommonConst;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserAttendance;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Clients\Models\Client;
use Modules\Leads\Models\Lead;
use Modules\RolePermission\Constants\RolePermissionConst;
use Modules\Product\Models\ProductService;
use Modules\RolePermission\Models\Role;
use Nwidart\Modules\Facades\Module;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $users = [
            [
                'name' => 'Pankaj Sharma',
                'email' => CommonConst::TESTING_EMAIL,
                'user_name' => 'super_admin',
                'password' => CommonConst::TESTING_EMAIL_PASSWORD,
                'country_code' => '+91',
                'phone' => '9876919999',
                'avatar' => null,
                'status' => "active",
                'salary' => 0,
                'mark_attendance' => false,
                'email_verified_at' => now()->format('Y-m-d H:i:s'),
                'date_of_birth' => '1990-01-01',
                'anniversary_date' => '2015-01-01',
                "roles" => [RolePermissionConst::SLUG_SUPER_ADMIN],
            ],
            [
                'name' => 'Admin',
                'email' => 'admin1@eligocs.com',
                'user_name' => 'admin',
                'password' => CommonConst::TESTING_EMAIL_PASSWORD,
                'country_code' => '+91',
                'phone' => '9876919888',
                'avatar' => null,
                'status' => "active",
                'salary' => 50000,
                'mark_attendance' => false,
                'email_verified_at' => now()->format('Y-m-d H:i:s'),
                'date_of_birth' => '1992-05-24',
                'anniversary_date' => '2017-02-02',
                "roles" => [RolePermissionConst::SLUG_ADMIN],
            ],
            [
                'name' => 'Employee User',
                'email' => 'employee@eligocs.com',
                'user_name' => 'employee_user',
                'password' => CommonConst::TESTING_EMAIL_PASSWORD,
                'country_code' => '+91',
                'phone' => '9876919777',
                'avatar' => null,
                'status' => "active",
                'salary' => 20000,
                'mark_attendance' => true,
                'email_verified_at' => now()->format('Y-m-d H:i:s'),
                'date_of_birth' => '1995-03-03',
                'anniversary_date' => '2020-05-22',
                "roles" => [RolePermissionConst::SLUG_EMPLOYEE],
            ],
        ];

        # Create or update roles with a progress bar
        $output = new ConsoleOutput();
        $output->writeln('Seeding users and assigning roles...');
        $progressBar = new ProgressBar($output, count($users));
        $progressBar->start();

        foreach ($users as $userData) {
            # Find roles by their names
            $roleIds = Role::whereIn('slug', $userData['roles'])->pluck('id')->toArray();

            # Create or update the user
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'user_name' => $userData['user_name'],
                    'password' => Hash::make($userData['password']),
                    'avatar' => $userData['avatar'] ?? null,
                    'country_code' => $userData['country_code'],
                    'phone' => $userData['phone'] ?? null,
                    'status' => $userData['status'],
                    'salary' => $userData['salary'],
                    'mark_attendance' => $userData['mark_attendance'],
                    'email_verified_at' => $userData['email_verified_at'],
                    'date_of_birth' => $userData['date_of_birth'],
                    'anniversary_date' => $userData['anniversary_date'],
                ]
            );

            # Remove old roles and assign new ones
            $user->roles()->sync($roleIds);
            $progressBar->advance();
        }

        # Add setting in info 
        $user_id = User::where('email', CommonConst::TESTING_EMAIL)->pluck('uuid')->first();
        $setting_list = [
            ["key" => "company_name", "value" => "Noble Solar", "created_by" => $user_id],
            ["key" => "phone", "value" => "08091706162", "created_by" => $user_id],
            ["key" => "address", "value" => "DEALS IN : SOLAR WATER HEATERS SOLAR ROOF TOP PLANT SOLAR HEAT PUMP SOLAR CCTV CAMERA SOLAR INVERTER SOLAR BATTERY SOLAR STREET LIGHTS SOLAR FENCING SOLAR DECORATIVE LIGHTING SOLAR HOME LIGHTING SYSTEM ETC", "created_by" => $user_id],
            ["key" => "company_logo", "value" => asset('images\logo\logo.png'), "created_by" => $user_id],
            ["key" => "email_color", "value" => "#7367f0", "created_by" => $user_id],
        ];
        foreach ($setting_list as $setting) {
            $existingSetting = Setting::where('key', $setting['key'])->first();
            if ($existingSetting) {
                $existingSetting->value = $setting['value'];
                $existingSetting->updated_by = $setting['created_by'];
                $existingSetting->save();
            } else {
                # Insert with created_by
                Setting::create(['key' => $setting['key'], 'value' => $setting['value'], 'created_by' => $setting['created_by']]);
            }
        }

        $progressBar->finish();

        $user_id = User::where('email', CommonConst::TESTING_EMAIL)->pluck('uuid')->first();
        if (Module::has(CommonConst::MODULE_PRODUCT_SERVICE)) {
            ProductService::query()->update(['created_by' => $user_id]);
        }

        if (Module::has(CommonConst::MODULE_LEAD)) {
            Lead::query()->update(['created_by' => $user_id]);
        }

        if (Module::has(CommonConst::MODULE_CLIENT)) {
            Client::query()->update(['created_by' => $user_id]);
        }

        # User Attendance Create last month
        $userId = User::where('email', 'employee@eligocs.com')->value('uuid');

        # Last month
        $this->markAttendanceForRange($userId, now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth());

        # This month (until yesterday)
        $this->markAttendanceForRange($userId, now()->startOfMonth(), now()->subDay());

        UserAttendance::where('user_id', $userId)->where('attendance_date', now()->subMonth()->startOfMonth()->format('Y-m-d'))->update(['status' => CommonConst::ABSENT]);
        UserAttendance::where('user_id', $userId)->where('attendance_date', '2025-04-16')->update(['status' => CommonConst::ABSENT]);

        $output->writeln("\nUsers seeded and roles assigned successfully!");
    }

    private function markAttendanceForRange($userId, Carbon $start, Carbon $end)
    {
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (!$date->isSunday()) {
                UserAttendance::firstOrCreate([
                    'user_id' => $userId,
                    'attendance_date' => $date->toDateString(),
                ], [
                    'status' => CommonConst::PRESENT,
                ]);
            }
        }
    }
}
