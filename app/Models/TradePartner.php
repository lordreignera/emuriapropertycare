<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TradePartner extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'partner_number',
        'trade_application_id',
        'company_name',
        'contact_person',
        'phone',
        'email',
        'service_area',
        'system_ids',
        'subsystem_ids',
        'agreed_subsystem_pricing',
        'agreed_custom_coverage',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'system_ids' => 'array',
        'subsystem_ids' => 'array',
        'agreed_subsystem_pricing' => 'array',
        'agreed_custom_coverage' => 'array',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (TradePartner $partner) {
            if (!empty($partner->partner_number)) {
                return;
            }

            do {
                $candidate = 'TP-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
            } while (self::query()->where('partner_number', $candidate)->exists());

            $partner->partner_number = $candidate;
        });
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(TradeApplication::class, 'trade_application_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function selectedSystems()
    {
        return InspectionSystem::query()
            ->whereIn('id', $this->system_ids ?? [])
            ->orderBy('name')
            ->get();
    }

    public function selectedSubsystems()
    {
        return InspectionSubsystem::with('system')
            ->whereIn('id', $this->subsystem_ids ?? [])
            ->orderBy('name')
            ->get();
    }
}
