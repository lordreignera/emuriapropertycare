<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PHARFinding extends Model
{
    protected $table = 'phar_findings';
    
    protected $fillable = [
        'inspection_id',
        'property_id',
        'task_question',
        'category',
        'priority',
        'included_yn',
        'labour_hours',
        'material_cost',
        'notes',
        'photo_ids',
    ];

    protected $casts = [
        'labour_hours' => 'decimal:2',
        'material_cost' => 'decimal:2',
        'included_yn' => 'boolean',
        'photo_ids' => 'array',
    ];

    /**
     * Get the inspection this finding belongs to
     */
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    /**
     * Get the property this finding belongs to
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Calculate labour cost for this finding
     */
    public function getLabourCostAttribute(): float
    {
        // Use a fixed rate of $165 if inspection relationship is not loaded
        $hourlyRate = 165;
        
        // Try to get rate from inspection if relationship is loaded
        if ($this->relationLoaded('inspection') && $this->inspection) {
            $hourlyRate = $this->inspection->labour_hourly_rate ?? 165;
        }
        
        return $this->labour_hours * $hourlyRate;
    }
}
