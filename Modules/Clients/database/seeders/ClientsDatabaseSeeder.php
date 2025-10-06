<?php

namespace Modules\Clients\Database\Seeders;

use App\Constants\CommonConst;
use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Clients\Models\Client;

class ClientsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        return;


        if (!app()->environment('local')) {
            return;
        }

        $faker = \Faker\Factory::create();
        Client::truncate();

        $now = now();

        foreach (range(1, 5) as $i) {
            Client::create([
                'name' => $faker->company,
                'contact_person' => $faker->name,
                'contact_person_role' => $faker->jobTitle,
                'email' => $faker->unique()->safeEmail,
                'country_code' => '91',
                'phone' => $faker->unique()->numerify('9#########'),
                'status' => CommonConst::ACTIVE,
                'date_of_birth' => "1995-" . str_pad($i, 2, '0', STR_PAD_LEFT) . "-01",
                'anniversary_date' => "2020-" . str_pad($i, 2, '0', STR_PAD_LEFT) . "-15",
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $userId = User::where('email', CommonConst::TESTING_EMAIL)->value('uuid');
        if ($userId) {
            Client::query()->update(['created_by' => $userId]);
        }
    }
}
