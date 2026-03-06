<?php

namespace Database\Seeders;

use App\Models\FindingTemplateSetting;
use Illuminate\Database\Seeder;

class FindingTemplateSettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (FindingTemplateSetting::defaults() as $row) {
            FindingTemplateSetting::updateOrCreate(
                ['task_question' => $row['task_question']],
                [
                    'category' => $row['category'],
                    'default_priority' => $row['default_priority'],
                    'default_included' => $row['default_included'],
                    'default_labour_hours' => $row['default_labour_hours'],
                    'photo_reference' => $row['photo_reference'],
                    'default_notes' => $row['default_notes'],
                    'is_active' => true,
                    'sort_order' => $row['sort_order'],
                ]
            );
        }
    }
}
