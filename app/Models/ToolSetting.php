<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolSetting extends Model
{
    protected $table = 'tool_settings';

    protected $fillable = [
        'tool_name',
        'quantity',
        'system_id',
        'subsystem_id',
        'finding_template_setting_id',
        'ownership_status',
        'availability_status',
        'notes',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'system_id' => 'integer',
        'subsystem_id' => 'integer',
        'finding_template_setting_id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function system(): BelongsTo
    {
        return $this->belongsTo(InspectionSystem::class, 'system_id');
    }

    public function subsystem(): BelongsTo
    {
        return $this->belongsTo(InspectionSubsystem::class, 'subsystem_id');
    }

    public function findingTemplateSetting(): BelongsTo
    {
        return $this->belongsTo(FindingTemplateSetting::class, 'finding_template_setting_id');
    }

    public function assignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InspectionToolAssignment::class, 'tool_setting_id');
    }

    /**
     * Total quantity currently deployed (not yet returned) across all projects.
     */
    public function deployedQuantity(): int
    {
        return (int) $this->assignments()
            ->whereNull('returned_at')
            ->where('quantity', '>', 0)
            ->sum('quantity');
    }

    /**
     * Quantity remaining in stock (total minus deployed).
     */
    public function remainingQuantity(): int
    {
        return max(0, (int) $this->quantity - $this->deployedQuantity());
    }
}
