<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CpiScoringFactor extends Model
{
    protected $fillable = [
        'domain_id',
        'factor_code',
        'factor_label',
        'field_type',
        'lookup_table',
        'max_points',
        'calculation_rule',
        'is_required',
        'is_active',
        'sort_order',
        'help_text',
    ];

    protected $casts = [
        'domain_id' => 'integer',
        'max_points' => 'integer',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'calculation_rule' => 'array',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(CpiDomain::class, 'domain_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function getLookupOptions()
    {
        if (!$this->lookup_table) {
            return collect();
        }

        // Dynamically get lookup options based on lookup_table value
        return match($this->lookup_table) {
            'supply_line_materials' => SupplyLineMaterial::active()->get(),
            'age_brackets' => AgeBracket::active()->get(),
            'containment_categories' => ContainmentCategory::active()->get(),
            'crawl_access_categories' => CrawlAccessCategory::active()->get(),
            'roof_access_categories' => RoofAccessCategory::active()->get(),
            'equipment_requirements' => EquipmentRequirement::active()->get(),
            'complexity_categories' => ComplexityCategory::active()->get(),
            default => collect(),
        };
    }
}
