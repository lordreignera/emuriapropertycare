<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EquipmentRequirementsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('equipment_requirements')->insert([
            [
                'requirement_code' => 'standard_ladder',
                'requirement_name' => 'Standard ladder only',
                'score_points' => 0,
                'description' => 'Basic equipment sufficient',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'requirement_code' => 'extended_ladder',
                'requirement_name' => 'Extended ladder / roof anchors',
                'score_points' => 1,
                'description' => 'Specialized ladder equipment needed',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'requirement_code' => 'scissor_lift',
                'requirement_name' => 'Scissor lift required',
                'score_points' => 2,
                'description' => 'Mechanical lift equipment needed',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'requirement_code' => 'boom_lift',
                'requirement_name' => 'Boom lift / crane / confined-space protocol',
                'score_points' => 3,
                'description' => 'Heavy equipment or special protocols required',
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
