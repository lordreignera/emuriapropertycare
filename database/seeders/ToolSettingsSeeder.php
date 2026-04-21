<?php

namespace Database\Seeders;

use App\Models\FindingTemplateSetting;
use App\Models\InspectionSystem;
use App\Models\ToolSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ToolSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Disable FK checks so we can truncate cleanly
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('inspection_tool_assignments')->truncate();
        DB::table('tool_settings')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $systemMap = InspectionSystem::query()->pluck('id', 'name');

        // Each entry: tool_name, system_names (array = multi-system rows), quantity, ownership, availability, finding_keyword, notes
        $defaults = [
            // Exterior
            ['tool_name' => 'Moisture Meter',        'system_names' => ['Exterior', 'Basement'], 'quantity' => 3, 'ownership_status' => 'owned',  'availability_status' => 'available',     'finding_keyword' => 'moisture', 'notes' => 'Diagnose moisture penetration and damp spots across exterior and basement.'],
            ['tool_name' => 'Pressure Washer',        'system_names' => ['Exterior', 'Roof'],     'quantity' => 2, 'ownership_status' => 'hired',  'availability_status' => 'available',     'finding_keyword' => 'surface',  'notes' => 'Surface cleaning prior to repair/coating — shared across exterior and roof.'],
            ['tool_name' => 'Extension Ladder 32ft',  'system_names' => ['Exterior', 'Gutters', 'Roof'], 'quantity' => 3, 'ownership_status' => 'owned', 'availability_status' => 'available', 'finding_keyword' => 'access', 'notes' => 'Safe access to upper-level repair points.'],

            // Electrical
            ['tool_name' => 'Infrared Thermal Camera','system_names' => ['Electrical'],           'quantity' => 2, 'ownership_status' => 'hired',  'availability_status' => 'available',     'finding_keyword' => 'hot',      'notes' => 'Identify overheating circuits and concealed thermal anomalies.'],
            ['tool_name' => 'Digital Multimeter',     'system_names' => ['Electrical'],           'quantity' => 4, 'ownership_status' => 'owned',  'availability_status' => 'available',     'finding_keyword' => 'electrical','notes' => 'Verify voltage, continuity, and electrical health.'],

            // Plumbing
            ['tool_name' => 'Drain Snake',            'system_names' => ['Plumbing'],             'quantity' => 3, 'ownership_status' => 'owned',  'availability_status' => 'available',     'finding_keyword' => 'drain',    'notes' => 'Resolve blocked drains and slow-flow issues.'],
            ['tool_name' => 'Pipe Wrench Set',        'system_names' => ['Plumbing'],             'quantity' => 4, 'ownership_status' => 'owned',  'availability_status' => 'available',     'finding_keyword' => 'leak',     'notes' => 'Tightening and replacement tasks for plumbing defects.'],

            // Roof
            ['tool_name' => 'Roof Safety Harness Kit','system_names' => ['Roof'],                 'quantity' => 4, 'ownership_status' => 'owned',  'availability_status' => 'available',     'finding_keyword' => 'roof',     'notes' => 'Fall protection for roof inspection and remediation.'],
            ['tool_name' => 'Shingle Lifting Bar',    'system_names' => ['Roof'],                 'quantity' => 3, 'ownership_status' => 'owned',  'availability_status' => 'available',     'finding_keyword' => 'shingle',  'notes' => 'Repair and replacement of damaged shingles.'],

            // Gutters
            ['tool_name' => 'Gutter Vacuum System',  'system_names' => ['Gutters'],              'quantity' => 1, 'ownership_status' => 'hired',  'availability_status' => 'non_available', 'finding_keyword' => 'gutter',   'notes' => 'Clearing debris and restoring gutter flow.'],

            // Basement
            ['tool_name' => 'HEPA Vacuum',            'system_names' => ['Basement', 'Walls'],   'quantity' => 2, 'ownership_status' => 'owned',  'availability_status' => 'available',     'finding_keyword' => 'dust',     'notes' => 'Containment and cleanup for remediation activities.'],

            // Walls
            ['tool_name' => 'Oscillating Multi-Tool', 'system_names' => ['Walls'],               'quantity' => 4, 'ownership_status' => 'owned',  'availability_status' => 'available',     'finding_keyword' => 'wall',     'notes' => 'Precision cut-outs and wall section repairs.'],

            // Windows
            ['tool_name' => 'Caulking Gun Kit',       'system_names' => ['Windows', 'Doors'],    'quantity' => 5, 'ownership_status' => 'owned',  'availability_status' => 'available',     'finding_keyword' => 'seal',     'notes' => 'Resealing joints around windows, doors, and penetrations.'],

            // Doors
            ['tool_name' => 'Door Hinge Jig Set',     'system_names' => ['Doors'],               'quantity' => 3, 'ownership_status' => 'owned',  'availability_status' => 'available',     'finding_keyword' => 'door',     'notes' => 'Alignment and repair for door hardware defects.'],

            // Foundation
            ['tool_name' => 'Concrete Crack Repair Kit','system_names' => ['Foundation'],        'quantity' => 3, 'ownership_status' => 'owned',  'availability_status' => 'available',     'finding_keyword' => 'crack',    'notes' => 'Foundation crack stabilization and sealing.'],
            ['tool_name' => 'Sump Pump Test Rig',     'system_names' => ['Foundation'],          'quantity' => 1, 'ownership_status' => 'hired',  'availability_status' => 'available',     'finding_keyword' => 'sump',     'notes' => 'Verification of sump operation and performance.'],

            // HVAC
            ['tool_name' => 'HVAC Coil Cleaning Kit', 'system_names' => ['HVAC'],                'quantity' => 2, 'ownership_status' => 'owned',  'availability_status' => 'available',     'finding_keyword' => 'hvac',     'notes' => 'Coil cleaning and airflow restoration tasks.'],

            // Safety (global / applies to all systems)
            ['tool_name' => 'PPE Safety Kit',         'system_names' => [null],                  'quantity' => 6, 'ownership_status' => 'owned',  'availability_status' => 'available',     'finding_keyword' => null,       'notes' => 'Mandatory PPE for all remediation operations.'],
        ];

        $sortOrder = 0;
        foreach ($defaults as $row) {
            foreach ($row['system_names'] as $systemName) {
                $systemId = $systemName !== null ? ($systemMap[$systemName] ?? null) : null;
                $finding = null;

                if ($systemId !== null && !empty($row['finding_keyword'])) {
                    $finding = FindingTemplateSetting::query()
                        ->where('is_active', true)
                        ->where('system_id', $systemId)
                        ->where('task_question', 'like', '%' . $row['finding_keyword'] . '%')
                        ->orderBy('sort_order')
                        ->first(['id', 'subsystem_id']);
                }

                ToolSetting::create([
                    'tool_name'                    => $row['tool_name'],
                    'quantity'                     => $row['quantity'],
                    'system_id'                    => $systemId,
                    'subsystem_id'                 => $finding?->subsystem_id,
                    'finding_template_setting_id'  => $finding?->id,
                    'ownership_status'             => $row['ownership_status'],
                    'availability_status'          => $row['availability_status'],
                    'notes'                        => $row['notes'] ?? null,
                    'sort_order'                   => $sortOrder++,
                    'is_active'                    => true,
                ]);
            }
        }
    }
}
