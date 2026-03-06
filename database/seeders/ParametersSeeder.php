<?php

namespace Database\Seeders;

use App\Models\Parameter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ParametersSeeder extends Seeder
{
    public function run(): void
    {
        $rows = Parameter::defaultBaseServiceParameters();

        foreach ($rows as $row) {
            DB::table('parameters')->updateOrInsert(
                ['parameter_key' => $row['parameter_key']],
                [
                    'parameter_value' => $row['parameter_value'],
                    'group_name' => 'base_service_pricing',
                    'description' => $row['description'],
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
