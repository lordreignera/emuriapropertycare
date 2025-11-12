<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Saving extends Model
{
    protected $fillable = [
        'project_id', 'client_id', 'tier_id', 'service_category', 'retail_cost',
        'subscription_cost', 'annual_savings', 'savings_percentage', 'calculation_notes'
    ];

    protected $casts = [
        'retail_cost' => 'decimal:2',
        'subscription_cost' => 'decimal:2',
        'annual_savings' => 'decimal:2',
        'savings_percentage' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(Tier::class);
    }

    public function getFormattedSavingsAttribute(): string
    {
        return '$' . number_format($this->annual_savings, 2);
    }

    public function getFormattedPercentageAttribute(): string
    {
        return number_format($this->savings_percentage, 1) . '%';
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('service_category', $category);
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }
}
