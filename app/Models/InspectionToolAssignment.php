<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionToolAssignment extends Model
{
    protected $table = 'inspection_tool_assignments';

    protected $fillable = [
        'inspection_id',
        'property_id',
        'tool_setting_id',
        'system_id',
        'subsystem_id',
        'tool_name',
        'quantity',
        'ownership_status',
        'availability_status',
        'finding_count',
        'returned_at',
        'returned_by',
        'return_notes',
        'assign_notes',
    ];

    protected $casts = [
        'inspection_id' => 'integer',
        'property_id' => 'integer',
        'tool_setting_id' => 'integer',
        'system_id' => 'integer',
        'subsystem_id' => 'integer',
        'finding_count' => 'integer',
        'quantity' => 'integer',
        'returned_at' => 'datetime',
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class, 'inspection_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function toolSetting(): BelongsTo
    {
        return $this->belongsTo(ToolSetting::class, 'tool_setting_id');
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'returned_by');
    }

    public function isReturned(): bool
    {
        return $this->returned_at !== null;
    }
}
