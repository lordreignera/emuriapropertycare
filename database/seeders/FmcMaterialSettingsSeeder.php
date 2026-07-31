<?php

namespace Database\Seeders;

use App\Models\FmcMaterialSetting;
use App\Support\BuildingTaxonomyResolver;
use Illuminate\Database\Seeder;

class FmcMaterialSettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (FmcMaterialSetting::defaults() as $row) {
            $taxonomy = BuildingTaxonomyResolver::resolve($row['system_name'] ?? null, $row['subsystem_name'] ?? null);

            $existing = FmcMaterialSetting::where([
                    'material_name' => $row['material_name'],
                    'building_system_id' => $taxonomy['building_system_id'],
                    'building_subsystem_id' => $taxonomy['building_subsystem_id'],
                    'building_component_id' => $taxonomy['building_component_id'],
                ])->first();

            if ($existing) {
                // Only fill in tax rates if they haven't been set yet — never overwrite user edits
                $updates = [];
                if ($existing->hst_rate === null) $updates['hst_rate'] = $row['hst_rate'] ?? 5.00;
                if ($existing->pst_rate === null) $updates['pst_rate'] = $row['pst_rate'] ?? 7.00;
                if (!$existing->is_active)         $updates['is_active'] = true;
                if (!empty($updates)) $existing->update($updates);
            } else {
                FmcMaterialSetting::create([
                    'material_name'     => $row['material_name'],
                    'building_system_id' => $taxonomy['building_system_id'],
                    'building_subsystem_id' => $taxonomy['building_subsystem_id'],
                    'building_component_id' => $taxonomy['building_component_id'],
                    'default_unit'      => $row['default_unit'],
                    'default_unit_cost' => $row['default_unit_cost'],
                    'hst_rate'          => $row['hst_rate']  ?? 5.00,
                    'pst_rate'          => $row['pst_rate']  ?? 7.00,
                    'sort_order'        => $row['sort_order'],
                    'description'       => $row['description'] ?? null,
                    'is_active'         => true,
                ]);
            }
        }
    }
}

