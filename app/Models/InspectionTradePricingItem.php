<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionTradePricingItem extends Model
{
    protected $fillable = [
        'inspection_id',
        'property_id',
        'phar_finding_id',
        'finding_index',
        'system_id',
        'subsystem_id',
        'trade_application_id',
        'trade_company_name',
        'fulfillment_type',
        'activity',
        'scope_area',
        'unit',
        'quantity',
        'estimated_duration_hours',
        'trade_unit_cost',
        'trade_total_cost',
        'etogo_client_price',
        'etogo_margin_amount',
        'margin_rate',
        'pricing_source',
        'approval_status',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'estimated_duration_hours' => 'decimal:2',
        'trade_unit_cost' => 'decimal:2',
        'trade_total_cost' => 'decimal:2',
        'etogo_client_price' => 'decimal:2',
        'etogo_margin_amount' => 'decimal:2',
        'margin_rate' => 'decimal:4',
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function pharFinding(): BelongsTo
    {
        return $this->belongsTo(PHARFinding::class, 'phar_finding_id');
    }

    public function tradeApplication(): BelongsTo
    {
        return $this->belongsTo(TradeApplication::class);
    }
}
