<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendationSetting extends Model
{
    protected $fillable = [
        'recommendation',
        'system_id',
        'subsystem_id',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'system_id' => 'integer',
        'subsystem_id' => 'integer',
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('recommendation');
    }
}
