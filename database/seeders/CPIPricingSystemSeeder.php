<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CPIPricingSystemSeeder extends Seeder
{
    /**
     * Seed all CPI pricing system tables in correct order
     */
    public function run(): void
    {
        $this->call([
            PropertyTypesSeeder::class,
            ParametersSeeder::class,
            EquipmentRequirementsSeeder::class,
            ComplexityCategoriesSeeder::class,

            // Size factor tables
            ResidentialSizeTiersSeeder::class,
            CommercialSizeSettingsSeeder::class,

            // System config
            PricingSystemConfigSeeder::class,
        ]);

        $this->command->info('✅ All CPI Pricing System tables seeded successfully!');
    }
}
