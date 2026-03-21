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

            FmcMaterialSetting::updateOrCreate(
                [
                    'material_name' => $row['material_name'],
                    'system_id'     => $systemId,
                    'subsystem_id'  => $subsystemId,
                ],
                [
                    'default_unit'     => $row['default_unit'],
                    'default_unit_cost' => $row['default_unit_cost'],
                    'sort_order'       => $row['sort_order'],
                    'description'      => $row['description'] ?? null,
                    'is_active'        => true,
                ]
            );
        }
    }
}

