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
        // Independent tables first
        $this->call([
            PropertyTypesSeeder::class,
            PricingPackagesSeeder::class,
            MixedUseCalculationSettingsSeeder::class,
            CpiBandRangesSeeder::class,
            CpiMultipliersSeeder::class,
            CpiDomainsSeeder::class,
            
            // Lookup tables
            SupplyLineMaterialsSeeder::class,
            AgeBracketsSeeder::class,
            ContainmentCategoriesSeeder::class,
            CrawlAccessCategoriesSeeder::class,
            RoofAccessCategoriesSeeder::class,
            EquipmentRequirementsSeeder::class,
            ComplexityCategoriesSeeder::class,
            
            // Size factor tables
            ResidentialSizeTiersSeeder::class,
            CommercialSizeSettingsSeeder::class,
            
            // System config
            PricingSystemConfigSeeder::class,
            ReactiveCostAssumptionsSeeder::class,
            StewardshipLossReductionsSeeder::class,
            
            // CPI Scoring Factors are seeded inside CpiDomainsSeeder
        ]);

        $this->command->info('✅ All CPI Pricing System tables seeded successfully!');
    }
}
