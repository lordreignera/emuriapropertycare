<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspectionMaterial extends Model
{
    protected $fillable = [
        'inspection_id',
        'property_id',
        'material_name',
        'description',
        'quantity',
        'unit',
        'unit_cost',
        'line_total',
        'notes',
        'category',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    /**
     * Relationship: Material belongs to an Inspection
     */
    public function inspection()
    {
        return $this->belongsTo(Inspection::class);
    }

    /**
     * Relationship: Material belongs to a Property
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Calculate line total automatically from quantity and unit cost
     */
    public function calculateLineTotal()
    {
        return round($this->quantity * $this->unit_cost, 2);
    }

    /**
     * Accessor: Get computed line total if not manually set
     */
    public function getComputedLineTotalAttribute()
    {
        return $this->line_total ?? $this->calculateLineTotal();
    }
}
