<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plans;

class PlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Explorer',
                'description' => '3 analyses offered (lifetime). Get started for free.',
                'price' => 0.00,
                'validity' => 1,
                'validity_type' => 'lifetime',
                'type' => 0, // Individual
                'user_quota' => null,
                'ios_product_id' => null,
                'android_product_id' => null,
                'status' => 1,
            ],
            [
                'name' => 'Negomaster Pro (Monthly)',
                'description' => 'Unlimited analyses (fair use). Billed monthly.',
                'price' => 19.00,
                'validity' => 1,
                'validity_type' => 'month',
                'type' => 0, // Individual
                'user_quota' => null,
                'ios_product_id' => 'com.negomaster.pro.monthly',
                'android_product_id' => 'com.negomaster.pro.monthly',
                'status' => 1,
            ],
            [
                'name' => 'Negomaster Pro (Annual)',
                'description' => 'Unlimited analyses (fair use). 12 months commitment with 7-day free trial.',
                'price' => 149.00,
                'validity' => 1,
                'validity_type' => 'year',
                'type' => 0, // Individual
                'user_quota' => null,
                'ios_product_id' => 'com.negomaster.pro.annual',
                'android_product_id' => 'com.negomaster.pro.annual',
                'status' => 1,
            ],
            [
                'name' => 'Single Analysis',
                'description' => '1 analysis, full profiling, and PDF export. One-time purchase.',
                'price' => 9.00,
                'validity' => 1,
                'validity_type' => 'one-time',
                'type' => 0, // Individual
                'user_quota' => null,
                'ios_product_id' => 'com.negomaster.single',
                'android_product_id' => 'com.negomaster.single',
                'status' => 1,
            ],
        ];

        foreach ($plans as $plan) {
            Plans::updateOrCreate(
                ['name' => $plan['name']],
                $plan
            );
        }
    }
}
