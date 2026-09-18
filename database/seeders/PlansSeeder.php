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
                'name' => 'Individual Monthly',
                'description' => 'Full access to all case studies, AI plans, and assessment modules for 1 month.',
                'price' => 9.99,
                'validity' => 1,
                'validity_type' => 'month',
                'type' => 0, // Individual
                'user_quota' => null,
                'ios_product_id' => 'com.banking.individual.monthly',
                'android_product_id' => 'com.banking.individual.monthly',
                'status' => 1,
            ],
            [
                'name' => 'Individual Annual',
                'description' => 'Full access to all features for 1 year with discounted pricing.',
                'price' => 99.99,
                'validity' => 12,
                'validity_type' => 'month',
                'type' => 0, // Individual
                'user_quota' => null,
                'ios_product_id' => 'com.banking.individual.annual',
                'android_product_id' => 'com.banking.individual.annual',
                'status' => 1,
            ],
            [
                'name' => 'Business Starter (10 Users)',
                'description' => 'Business package for small teams up to 10 employee seats with dedicated dashboard.',
                'price' => 199.00,
                'validity' => 12,
                'validity_type' => 'month',
                'type' => 1, // Business
                'user_quota' => 10,
                'ios_product_id' => null,
                'android_product_id' => null,
                'status' => 1,
            ],
            [
                'name' => 'Business Growth (25 Users)',
                'description' => 'Business package for growing teams up to 25 employee seats.',
                'price' => 399.00,
                'validity' => 12,
                'validity_type' => 'month',
                'type' => 1, // Business
                'user_quota' => 25,
                'ios_product_id' => null,
                'android_product_id' => null,
                'status' => 1,
            ],
            [
                'name' => 'Business Enterprise (100 Users)',
                'description' => 'Enterprise corporate subscription for up to 100 employee seats.',
                'price' => 999.00,
                'validity' => 12,
                'validity_type' => 'month',
                'type' => 1, // Business
                'user_quota' => 100,
                'ios_product_id' => null,
                'android_product_id' => null,
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
