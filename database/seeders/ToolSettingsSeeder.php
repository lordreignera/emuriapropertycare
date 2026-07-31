<?php

namespace Database\Seeders;

use App\Models\BuildingSystem;
use App\Models\FindingTemplateSetting;
use App\Models\ToolSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ToolSettingsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('inspection_tool_assignments')->delete();
        DB::table('tool_settings')->delete();

        $systemMap = BuildingSystem::query()->pluck('id', 'name');

        $defaults = [
            ['tool_name' => 'Moisture Meter', 'system_names' => ['Building Envelope'], 'quantity' => 3, 'ownership_status' => 'owned', 'availability_status' => 'available', 'finding_keyword' => 'moisture', 'notes' => 'Diagnose moisture penetration and damp spots across enclosure assemblies.'],
            ['tool_name' => 'Pressure Washer', 'system_names' => ['Building Envelope'], 'quantity' => 2, 'ownership_status' => 'hired', 'availability_status' => 'available', 'finding_keyword' => 'surface', 'notes' => 'Surface cleaning prior to repair or coating.'],
            ['tool_name' => 'Extension Ladder 32ft', 'system_names' => ['Building Envelope'], 'quantity' => 3, 'ownership_status' => 'owned', 'availability_status' => 'available', 'finding_keyword' => 'access', 'notes' => 'Safe access to upper-level envelope and roofing repair points.'],
            ['tool_name' => 'Infrared Thermal Camera', 'system_names' => ['Electrical', 'Building Envelope'], 'quantity' => 2, 'ownership_status' => 'hired', 'availability_status' => 'available', 'finding_keyword' => 'hot', 'notes' => 'Identify overheating circuits and concealed thermal anomalies.'],
            ['tool_name' => 'Digital Multimeter', 'system_names' => ['Electrical'], 'quantity' => 4, 'ownership_status' => 'owned', 'availability_status' => 'available', 'finding_keyword' => 'electrical', 'notes' => 'Verify voltage, continuity, and electrical health.'],
            ['tool_name' => 'Drain Snake', 'system_names' => ['Plumbing'], 'quantity' => 3, 'ownership_status' => 'owned', 'availability_status' => 'available', 'finding_keyword' => 'drain', 'notes' => 'Resolve blocked drains and slow-flow issues.'],
            ['tool_name' => 'Pipe Wrench Set', 'system_names' => ['Plumbing'], 'quantity' => 4, 'ownership_status' => 'owned', 'availability_status' => 'available', 'finding_keyword' => 'leak', 'notes' => 'Tightening and replacement tasks for plumbing defects.'],
            ['tool_name' => 'Roof Safety Harness Kit', 'system_names' => ['Building Envelope'], 'quantity' => 4, 'ownership_status' => 'owned', 'availability_status' => 'available', 'finding_keyword' => 'roof', 'notes' => 'Fall protection for roof inspection and remediation.'],
            ['tool_name' => 'Shingle Lifting Bar', 'system_names' => ['Building Envelope'], 'quantity' => 3, 'ownership_status' => 'owned', 'availability_status' => 'available', 'finding_keyword' => 'shingle', 'notes' => 'Repair and replacement of damaged shingles.'],
            ['tool_name' => 'Gutter Vacuum System', 'system_names' => ['Building Envelope'], 'quantity' => 1, 'ownership_status' => 'hired', 'availability_status' => 'non_available', 'finding_keyword' => 'gutter', 'notes' => 'Clearing debris and restoring gutter flow.'],
            ['tool_name' => 'HEPA Vacuum', 'system_names' => ['Interiors', 'Building Envelope'], 'quantity' => 2, 'ownership_status' => 'owned', 'availability_status' => 'available', 'finding_keyword' => 'dust', 'notes' => 'Containment and cleanup for remediation activities.'],
            ['tool_name' => 'Oscillating Multi-Tool', 'system_names' => ['Interiors'], 'quantity' => 4, 'ownership_status' => 'owned', 'availability_status' => 'available', 'finding_keyword' => 'wall', 'notes' => 'Precision cut-outs and wall section repairs.'],
            ['tool_name' => 'Caulking Gun Kit', 'system_names' => ['Building Envelope'], 'quantity' => 5, 'ownership_status' => 'owned', 'availability_status' => 'available', 'finding_keyword' => 'seal', 'notes' => 'Resealing joints around windows, doors, and penetrations.'],
            ['tool_name' => 'Door Hinge Jig Set', 'system_names' => ['Building Envelope', 'Interiors'], 'quantity' => 3, 'ownership_status' => 'owned', 'availability_status' => 'available', 'finding_keyword' => 'door', 'notes' => 'Alignment and repair for door hardware defects.'],
            ['tool_name' => 'Concrete Crack Repair Kit', 'system_names' => ['Structure and Substructure'], 'quantity' => 3, 'ownership_status' => 'owned', 'availability_status' => 'available', 'finding_keyword' => 'crack', 'notes' => 'Foundation crack stabilization and sealing.'],
            ['tool_name' => 'Sump Pump Test Rig', 'system_names' => ['Plumbing'], 'quantity' => 1, 'ownership_status' => 'hired', 'availability_status' => 'available', 'finding_keyword' => 'sump', 'notes' => 'Verification of sump operation and performance.'],
            ['tool_name' => 'HVAC Coil Cleaning Kit', 'system_names' => ['HVAC and Mechanical'], 'quantity' => 2, 'ownership_status' => 'owned', 'availability_status' => 'available', 'finding_keyword' => 'hvac', 'notes' => 'Coil cleaning and airflow restoration tasks.'],
            ['tool_name' => 'PPE Safety Kit', 'system_names' => [null], 'quantity' => 6, 'ownership_status' => 'owned', 'availability_status' => 'available', 'finding_keyword' => null, 'notes' => 'Mandatory PPE for all remediation operations.'],
        ];

        $sortOrder = 0;
        foreach ($defaults as $row) {
            foreach ($row['system_names'] as $systemName) {
                $systemId = $systemName !== null ? ($systemMap[$systemName] ?? null) : null;
                $finding = null;

                if ($systemId !== null && !empty($row['finding_keyword'])) {
                    $finding = FindingTemplateSetting::query()
                        ->where('is_active', true)
                        ->where('building_system_id', $systemId)
                        ->where('task_question', 'like', '%' . $row['finding_keyword'] . '%')
                        ->orderBy('sort_order')
                        ->first(['id', 'building_subsystem_id']);
                }

                ToolSetting::create([
                    'tool_name' => $row['tool_name'],
                    'quantity' => $row['quantity'],
                    'building_system_id' => $systemId,
                    'building_subsystem_id' => $finding?->building_subsystem_id,
                    'finding_template_setting_id' => $finding?->id,
                    'ownership_status' => $row['ownership_status'],
                    'availability_status' => $row['availability_status'],
                    'notes' => $row['notes'] ?? null,
                    'sort_order' => $sortOrder++,
                    'is_active' => true,
                ]);
            }
        }
    }
}
