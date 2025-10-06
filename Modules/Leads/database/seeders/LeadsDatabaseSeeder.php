<?php

namespace Modules\Leads\Database\Seeders;

use App\Constants\CommonConst;
use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Leads\Models\Lead;
use Modules\Leads\Constants\LeadConst;

class LeadsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!app()->environment('local')) {
            return;
        }

        $faker = \Faker\Factory::create();
        $now = now();

        $sources = [
            'Website',
            'Referral',
            'Advertisement',
        ];

        Lead::truncate();

        foreach (range(1, 10) as $i) {
            Lead::create([
                'name' => $faker->company,
                'contact_person' => $faker->name,
                'contact_person_role' => $faker->jobTitle,
                'email' => $faker->unique()->safeEmail,
                'country_code' => '91',
                'phone' => $faker->unique()->numerify('9#########'),
                'address' => $faker->address,
                'status' => LeadConst::NO_ACTION,
                'source' => $faker->randomElement($sources),
                'note' => $faker->sentence,
                'date_of_birth' => "1995-" . str_pad($i, 2, '0', STR_PAD_LEFT) . "-01",
                'anniversary_date' => "2020-" . str_pad($i, 2, '0', STR_PAD_LEFT) . "-15",
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $userId = User::where('email', CommonConst::TESTING_EMAIL)->value('uuid');
        if ($userId) {
            Lead::query()->update(['created_by' => $userId]);
        }
    }
}
