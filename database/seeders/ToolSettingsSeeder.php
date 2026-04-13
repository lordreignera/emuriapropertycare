<?php

namespace Database\Seeders;

use App\Models\FindingTemplateSetting;
use App\Models\InspectionSystem;
use App\Models\ToolSetting;
use Illuminate\Database\Seeder;

class ToolSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $systemMap = InspectionSystem::query()->pluck('id', 'name');

        $defaults = [
            ['tool_name' => 'Moisture Meter', 'system_name' => 'Exterior', 'finding_keyword' => 'moisture', 'ownership_status' => 'owned', 'availability_status' => 'available', 'notes' => 'Diagnose moisture penetration and damp spots.'],
            ['tool_name' => 'Infrared Thermal Camera', 'system_name' => 'Electrical', 'finding_keyword' => 'hot', 'ownership_status' => 'hired', 'availability_status' => 'available', 'notes' => 'Identify overheating circuits and concealed thermal anomalies.'],
            ['tool_name' => 'Digital Multimeter', 'system_name' => 'Electrical', 'finding_keyword' => 'electrical', 'ownership_status' => 'owned', 'availability_status' => 'available', 'notes' => 'Verify voltage, continuity, and electrical health.'],
            ['tool_name' => 'Drain Snake', 'system_name' => 'Plumbing', 'finding_keyword' => 'drain', 'ownership_status' => 'owned', 'availability_status' => 'available', 'notes' => 'Resolve blocked drains and slow-flow issues.'],
            ['tool_name' => 'Pipe Wrench Set', 'system_name' => 'Plumbing', 'finding_keyword' => 'leak', 'ownership_status' => 'owned', 'availability_status' => 'available', 'notes' => 'Tightening and replacement tasks for plumbing defects.'],
            ['tool_name' => 'Roof Safety Harness Kit', 'system_name' => 'Roof', 'finding_keyword' => 'roof', 'ownership_status' => 'owned', 'availability_status' => 'available', 'notes' => 'Fall protection for roof inspection and remediation.'],
            ['tool_name' => 'Shingle Lifting Bar', 'system_name' => 'Roof', 'finding_keyword' => 'shingle', 'ownership_status' => 'owned', 'availability_status' => 'available', 'notes' => 'Repair and replacement of damaged shingles.'],
            ['tool_name' => 'Gutter Vacuum System', 'system_name' => 'Gutters', 'finding_keyword' => 'gutter', 'ownership_status' => 'hired', 'availability_status' => 'non_available', 'notes' => 'Clearing debris and restoring gutter flow.'],
            ['tool_name' => 'Pressure Washer', 'system_name' => 'Exterior', 'finding_keyword' => 'surface', 'ownership_status' => 'hired', 'availability_status' => 'available', 'notes' => 'Surface cleaning prior to repair/coating.'],
            ['tool_name' => 'Extension Ladder 32ft', 'system_name' => 'Exterior', 'finding_keyword' => 'access', 'ownership_status' => 'owned', 'availability_status' => 'available', 'notes' => 'Safe access to upper-level repair points.'],
            ['tool_name' => 'HEPA Vacuum', 'system_name' => 'Basement', 'finding_keyword' => 'dust', 'ownership_status' => 'owned', 'availability_status' => 'available', 'notes' => 'Containment and cleanup for remediation activities.'],
            ['tool_name' => 'Oscillating Multi-Tool', 'system_name' => 'Walls', 'finding_keyword' => 'wall', 'ownership_status' => 'owned', 'availability_status' => 'available', 'notes' => 'Precision cut-outs and wall section repairs.'],
            ['tool_name' => 'Caulking Gun Kit', 'system_name' => 'Windows', 'finding_keyword' => 'seal', 'ownership_status' => 'owned', 'availability_status' => 'available', 'notes' => 'Resealing joints around windows and penetrations.'],
            ['tool_name' => 'Door Hinge Jig Set', 'system_name' => 'Doors', 'finding_keyword' => 'door', 'ownership_status' => 'owned', 'availability_status' => 'available', 'notes' => 'Alignment and repair for door hardware defects.'],
            ['tool_name' => 'Concrete Crack Repair Kit', 'system_name' => 'Foundation', 'finding_keyword' => 'crack', 'ownership_status' => 'owned', 'availability_status' => 'available', 'notes' => 'Foundation crack stabilization and sealing.'],
            ['tool_name' => 'Sump Pump Test Rig', 'system_name' => 'Foundation', 'finding_keyword' => 'sump', 'ownership_status' => 'hired', 'availability_status' => 'available', 'notes' => 'Verification of sump operation and performance.'],
            ['tool_name' => 'HVAC Coil Cleaning Kit', 'system_name' => 'HVAC', 'finding_keyword' => 'hvac', 'ownership_status' => 'owned', 'availability_status' => 'available', 'notes' => 'Coil cleaning and airflow restoration tasks.'],
            ['tool_name' => 'PPE Safety Kit', 'system_name' => 'Safety', 'finding_keyword' => 'safety', 'ownership_status' => 'owned', 'availability_status' => 'available', 'notes' => 'Mandatory PPE for all remediation operations.'],
        ];

        foreach ($defaults as $index => $row) {
            $systemId = $systemMap[$row['system_name']] ?? null;
            $finding = null;

            if ($systemId !== null && !empty($row['finding_keyword'])) {
                $finding = FindingTemplateSetting::query()
                    ->where('is_active', true)
                    ->where('system_id', $systemId)
                    ->where('task_question', 'like', '%' . $row['finding_keyword'] . '%')
                    ->orderBy('sort_order')
                    ->first(['id', 'subsystem_id']);
            }

            $subsystemId = $finding?->subsystem_id;
            $findingId = $finding?->id;

            ToolSetting::updateOrCreate(
                [
                    'tool_name' => $row['tool_name'],
                    'system_id' => $systemId,
                    'subsystem_id' => $subsystemId,
                    'finding_template_setting_id' => $findingId,
                ],
                [
                    'ownership_status' => $row['ownership_status'],
                    'availability_status' => $row['availability_status'],
                    'notes' => $row['notes'] ?? null,
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
        }
    }
}
