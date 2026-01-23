<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StewardshipLossReductionsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('stewardship_loss_reductions')->insert([
            [
                'cpi_band' => 'CPI-0',
                'loss_reduction' => 0.20,
                'description' => 'Excellent condition - 20% loss reduction',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'cpi_band' => 'CPI-1',
                'loss_reduction' => 0.25,
                'description' => 'Good condition - 25% loss reduction',
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'cpi_band' => 'CPI-2',
                'loss_reduction' => 0.30,
                'description' => 'Fair condition - 30% loss reduction',
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'cpi_band' => 'CPI-3',
                'loss_reduction' => 0.35,
                'description' => 'Poor condition - 35% loss reduction',
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'cpi_band' => 'CPI-4',
                'loss_reduction' => 0.40,
                'description' => 'Critical condition - 40% loss reduction',
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
