<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_number',
        'user_id',
        'property_id',
        'project_id',
        'inspection_id',
        'source',
        'request_type',
        'urgency',
        'title',
        'description',
        'requested_location',
        'items_reported',
        'photos',
        'floor_plan_pin',
        'preferred_visit_window',
        'status',
        'triage_notes',
        'assessment_summary',
        'quotation_id',
        'approved_change_order_id',
        'created_inspection_id',
        'assigned_to',
        'submitted_at',
        'triaged_at',
        'assessed_at',
        'resolved_at',
    ];

    protected $casts = [
        'items_reported' => 'array',
        'photos' => 'array',
        'floor_plan_pin' => 'array',
        'submitted_at' => 'datetime',
        'triaged_at' => 'datetime',
        'assessed_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ServiceRequest $serviceRequest) {
            if (!empty($serviceRequest->request_number)) {
                return;
            }

            do {
                $candidate = 'SR-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
            } while (self::query()->where('request_number', $candidate)->exists());

            $serviceRequest->request_number = $candidate;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(InspectionQuotation::class, 'quotation_id');
    }

    public function approvedChangeOrder(): BelongsTo
    {
        return $this->belongsTo(ChangeOrder::class, 'approved_change_order_id');
    }

    public function createdInspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class, 'created_inspection_id');
    }
}
