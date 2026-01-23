<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PricingPackagesSeeder extends Seeder
{
    public function run(): void
    {
        // Insert packages
        $packages = [
            ['id' => 1, 'package_name' => 'Essentials', 'description' => 'Essential maintenance services for your property', 'features' => json_encode(['Regular inspections', 'Basic repairs', 'Emergency support']), 'sort_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'package_name' => 'Premium', 'description' => 'Enhanced care with priority service', 'features' => json_encode(['All Essentials features', 'Priority scheduling', 'Preventive maintenance', 'Quarterly reviews']), 'sort_order' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'package_name' => 'White-Glove', 'description' => 'Comprehensive premium care package', 'features' => json_encode(['All Premium features', 'Dedicated property manager', 'Monthly inspections', '24/7 concierge support', 'Landscaping services']), 'sort_order' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ];
        DB::table('pricing_packages')->insert($packages);

        // Get property type IDs
        $residential = DB::table('property_types')->where('type_code', 'residential')->value('id');
        $commercial = DB::table('property_types')->where('type_code', 'commercial')->value('id');
        $mixedUse = DB::table('property_types')->where('type_code', 'mixed_use')->value('id');

        // Insert package pricing (Package × Property Type = Price)
        $pricing = [
            // Essentials Package
            ['pricing_package_id' => 1, 'property_type_id' => $residential, 'base_monthly_price' => 199.00, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['pricing_package_id' => 1, 'property_type_id' => $commercial, 'base_monthly_price' => 650.00, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['pricing_package_id' => 1, 'property_type_id' => $mixedUse, 'base_monthly_price' => 425.00, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            
            // Premium Package
            ['pricing_package_id' => 2, 'property_type_id' => $residential, 'base_monthly_price' => 299.00, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['pricing_package_id' => 2, 'property_type_id' => $commercial, 'base_monthly_price' => 1200.00, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['pricing_package_id' => 2, 'property_type_id' => $mixedUse, 'base_monthly_price' => 750.00, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            
            // White-Glove Package
            ['pricing_package_id' => 3, 'property_type_id' => $residential, 'base_monthly_price' => 399.00, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['pricing_package_id' => 3, 'property_type_id' => $commercial, 'base_monthly_price' => 2000.00, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['pricing_package_id' => 3, 'property_type_id' => $mixedUse, 'base_monthly_price' => 1200.00, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ];
        DB::table('package_pricing')->insert($pricing);
    }
}
