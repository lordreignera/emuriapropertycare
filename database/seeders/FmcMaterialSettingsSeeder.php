<?php

namespace Database\Seeders;

use App\Models\FmcMaterialSetting;
use App\Models\InspectionSubsystem;
use App\Models\InspectionSystem;
use Illuminate\Database\Seeder;

class FmcMaterialSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $systemMap = InspectionSystem::query()->pluck('id', 'name');
        $subsystemMap = InspectionSubsystem::query()->get()->keyBy(function ($subsystem) {
            return $subsystem->system_id . '|' . $subsystem->name;
        });

        foreach (FmcMaterialSetting::defaults() as $row) {
            $systemId = $systemMap[$row['system_name']] ?? null;
            $subsystemId = null;

            if ($systemId !== null) {
                $subsystemKey = $systemId . '|' . $row['subsystem_name'];
                $subsystemId = optional($subsystemMap->get($subsystemKey))->id;
            }

            $existing = FmcMaterialSetting::where([
                    'material_name' => $row['material_name'],
                    'system_id'     => $systemId,
                    'subsystem_id'  => $subsystemId,
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
                    'system_id'         => $systemId,
                    'subsystem_id'      => $subsystemId,
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

