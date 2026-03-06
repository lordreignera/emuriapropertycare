<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FindingTemplateSetting extends Model
{
    protected $table = 'finding_template_settings';

    protected $fillable = [
        'task_question',
        'category',
        'default_priority',
        'default_included',
        'default_labour_hours',
        'photo_reference',
        'default_notes',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'default_priority' => 'integer',
        'default_included' => 'boolean',
        'default_labour_hours' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public static function defaults(): array
    {
        return [
            ['task_question' => 'Gutter cleaning + downspout re-secure', 'category' => 'Exterior & Drainage (Minor)', 'default_priority' => 2, 'default_included' => true, 'default_labour_hours' => 3.0, 'photo_reference' => 'R01', 'default_notes' => 'Gutter cleaning and downspout check', 'sort_order' => 1],
            ['task_question' => 'Re-caulk tub + sink (bath 1)', 'category' => 'Minor Repairs (General)', 'default_priority' => 2, 'default_included' => true, 'default_labour_hours' => 4.0, 'photo_reference' => 'R02', 'default_notes' => 'Bathroom caulking and minor leak check', 'sort_order' => 2],
            ['task_question' => 'Replace kitchen faucet (leaking)', 'category' => 'Plumbing (Minor)', 'default_priority' => 3, 'default_included' => true, 'default_labour_hours' => 2.0, 'photo_reference' => 'R03', 'default_notes' => 'Replace leaking kitchen faucet', 'sort_order' => 3],
            ['task_question' => 'Replace 2 bathroom supply lines', 'category' => 'Plumbing (Minor)', 'default_priority' => 1, 'default_included' => true, 'default_labour_hours' => 6.0, 'photo_reference' => 'R04', 'default_notes' => 'Moisture issue near basement window; seal', 'sort_order' => 4],
            ['task_question' => 'Replace 3 light switches (worn)', 'category' => 'Electrical (Minor)', 'default_priority' => 2, 'default_included' => true, 'default_labour_hours' => 3.0, 'photo_reference' => 'R05', 'default_notes' => 'Electrical GFCI replacement in kitchen', 'sort_order' => 5],
        ];
    }
}
