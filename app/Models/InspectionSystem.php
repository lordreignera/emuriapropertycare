<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspectionSystem extends Model
{
    protected $table = 'systems';

    protected $fillable = [
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

    public function subsystems(): HasMany
    {
        return $this->hasMany(InspectionSubsystem::class, 'system_id');
    }
}
