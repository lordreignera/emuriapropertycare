<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspectionSubsystem extends Model
{
    protected $table = 'subsystems';

    protected $fillable = [
        'system_id',
        'name',
        'slug',
        'description',
        'recommended_actions',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'recommended_actions' => 'array',
        'is_active' => 'boolean',
    ];

    public function system(): BelongsTo
    {
        return $this->belongsTo(InspectionSystem::class, 'system_id');
    }

    public function findingTemplateSettings(): HasMany
    {
        return $this->hasMany(FindingTemplateSetting::class, 'subsystem_id');
    }

    public function fmcMaterialSettings(): HasMany
    {
        return $this->hasMany(FmcMaterialSetting::class, 'subsystem_id');
    }
}
