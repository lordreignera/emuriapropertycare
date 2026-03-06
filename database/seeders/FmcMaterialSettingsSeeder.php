<?php

namespace Database\Seeders;

use App\Models\FmcMaterialSetting;
use Illuminate\Database\Seeder;

class FmcMaterialSettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (FmcMaterialSetting::defaults() as $row) {
            FmcMaterialSetting::updateOrCreate(
                ['material_name' => $row['material_name']],
                [
                    'default_unit' => $row['default_unit'],
                    'default_unit_cost' => $row['default_unit_cost'],
                    'sort_order' => $row['sort_order'],
                    'description' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}
