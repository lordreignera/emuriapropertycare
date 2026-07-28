<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StewardshipPlan extends Model
{
    protected $fillable = [
        'property_id',
        'created_by',
        'status',
        'title',
        'inspection_frequency',
        'strategy',
        'next_review_date',
    ];

    protected $casts = [
        'strategy' => 'array',
        'next_review_date' => 'date',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StewardshipPlanItem::class);
    }
}
