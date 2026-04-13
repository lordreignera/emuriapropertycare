<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolSetting extends Model
{
    protected $table = 'tool_settings';

    protected $fillable = [
        'tool_name',
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
}
