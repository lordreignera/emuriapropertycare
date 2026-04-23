<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Inspection extends Model
{
    protected $fillable = [
        'project_id',
        'property_id',
        'inspector_id',
        'technician_id',
        'assigned_by',
        'scheduled_date',
        'completed_date',
        'summary',
        'findings',
        'report_file',
        'photos',
        'status',
        'approved_by_client',
        'client_approved_at',
        'client_signature',
        'client_full_name',
        'client_acknowledgment',
        'active_quotation_id',
        'quotation_status',
        'quotation_shared_at',
        'quotation_approved_at',
        'etogo_signed_by',
        'etogo_signed_at',
        'planned_start_date',
        'estimated_duration_days',
        'target_completion_date',
        'schedule_blocked_reason',
        'inspection_fee_amount',
        'inspection_fee_status',
        'inspection_fee_paid_at',
        'work_payment_amount',
        'work_payment_status',
        'work_payment_cadence',
        'work_payment_paid_at',
        'work_stripe_payment_intent_id',
        'payment_plan',
        'installment_months',
        'installments_paid',
        'arp_total_locked',
        'installment_amount',
        'arp_fully_paid_at',
        'next_installment_due_date',
        'property_size_psf',
        'estimated_task_hours',
        'minimum_required_hours',
        'bdc_visits_per_year',
        // Travel BDC inputs
        'bdc_distance_km',
        'bdc_time_minutes',
        'bdc_rate_per_km',
        'bdc_rate_per_minute',
        'bdc_per_visit',
        // Calculation fields from phar_findings migration
        'bdc_annual',
        'bdc_monthly',
        'frlc_annual',
        'frlc_monthly',
        'labour_hourly_rate',
        'fmc_annual',
        'fmc_monthly',
        'trc_annual',
        'trc_monthly',
        'trc_per_visit',
        'arp_monthly',
        'condition_score',
        'tier_score',
        'tier_arp',
        'tier_final',
        'multiplier_final',
        'arp_equivalent_final',
        'base_package_price_snapshot',
        'scientific_final_monthly',
        'scientific_final_annual',
        'units_for_calculation',
        'bdc_per_unit_annual',
        'frlc_per_unit_annual',
        'fmc_per_unit_annual',
        'trc_per_unit_annual',
        'final_monthly_per_unit',
        'recommendations',
        'risk_summary',
        'overall_condition',
        'inspector_notes',
        // CPI / ASI scoring
        'cpi_total_score',
        'system_scores',
        'cpi_rating',
        'tus_score',
        'asi_score',
        'asi_rating',
        // Property snapshot fields (captured at inspection time)
        'work_schedule',
        'owner_name',
        'owner_email',
        'owner_phone',
        'property_code',
        'property_name',
        'property_address_snapshot',
        'property_type_snapshot',
        'residential_units_snapshot',
        'commercial_sqft_snapshot',
        'mixed_use_weight_snapshot',
        'property_year_built',
    ];

    protected $casts = [
        'scheduled_date'  => 'datetime',
        'completed_date'  => 'datetime',
        'findings'        => 'array',
        'system_scores'   => 'array',
        'photos'          => 'array',
        'approved_by_client' => 'boolean',
        'client_approved_at' => 'datetime',
        'active_quotation_id' => 'integer',
        'quotation_shared_at' => 'datetime',
        'quotation_approved_at' => 'datetime',
        'etogo_signed_by' => 'integer',
        'etogo_signed_at' => 'datetime',
        'planned_start_date' => 'date',
        'estimated_duration_days' => 'integer',
        'target_completion_date' => 'date',
        'inspection_fee_amount' => 'decimal:2',
        'inspection_fee_paid_at' => 'datetime',
        'work_payment_amount' => 'decimal:2',
        'work_payment_paid_at' => 'datetime',
        'arp_total_locked' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'arp_fully_paid_at' => 'datetime',
        'next_installment_due_date' => 'date',
        'installment_months' => 'integer',
        'installments_paid' => 'integer',
        'property_size_psf' => 'decimal:2',
        'estimated_task_hours' => 'decimal:2',
        'minimum_required_hours' => 'decimal:2',
        'work_schedule'       => 'array',
        'bdc_visits_per_year' => 'decimal:2',
        // Travel BDC numeric fields
        'bdc_distance_km'        => 'decimal:2',
        'bdc_time_minutes'       => 'decimal:2',
        'bdc_rate_per_km'        => 'decimal:2',
        'bdc_rate_per_minute'    => 'decimal:2',
        'bdc_per_visit'          => 'decimal:2',
        'bdc_annual'             => 'decimal:2',
        'bdc_monthly'            => 'decimal:2',
        'bdc_per_unit_annual'    => 'decimal:2',
        // FRLC / FMC
        'frlc_annual'            => 'decimal:2',
        'frlc_monthly'           => 'decimal:2',
        'frlc_per_unit_annual'   => 'decimal:2',
        'fmc_annual'             => 'decimal:2',
        'fmc_monthly'            => 'decimal:2',
        'fmc_per_unit_annual'    => 'decimal:2',
        // TRC / ARP
        'trc_annual'             => 'decimal:2',
        'trc_monthly'            => 'decimal:2',
        'trc_per_visit'          => 'decimal:2',
        'trc_per_unit_annual'    => 'decimal:2',
        'arp_monthly'            => 'decimal:2',
        'labour_hourly_rate'     => 'decimal:2',
        // Scoring
        'condition_score'        => 'decimal:2',
        'tier_score'             => 'decimal:2',
        'tier_arp'               => 'decimal:2',
        'tier_final'             => 'decimal:2',
        'multiplier_final'       => 'decimal:2',
        'cpi_total_score'        => 'decimal:2',
        'tus_score'              => 'decimal:2',
        'asi_score'              => 'decimal:2',
        // Snapshot / computed totals
        'arp_equivalent_final'        => 'decimal:2',
        'base_package_price_snapshot' => 'decimal:2',
        'scientific_final_monthly'    => 'decimal:2',
        'scientific_final_annual'     => 'decimal:2',
        // Per-unit
        'units_for_calculation'  => 'integer',
        'final_monthly_per_unit' => 'decimal:2',
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function etogoRepresentative(): BelongsTo
    {
        return $this->belongsTo(User::class, 'etogo_signed_by');
    }

    public function scopeOfWorks(): HasMany
    {
        return $this->hasMany(ScopeOfWork::class);
    }

    public function pharFindings(): HasMany
    {
        return $this->hasMany(PHARFinding::class);
    }

    public function maintenanceVisitLogs(): HasMany
    {
        return $this->hasMany(MaintenanceVisitLog::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(InspectionQuotation::class);
    }

    public function activeQuotation(): BelongsTo
    {
        return $this->belongsTo(InspectionQuotation::class, 'active_quotation_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(InspectionMaterial::class);
    }

    public function toolAssignments(): HasMany
    {
        return $this->hasMany(InspectionToolAssignment::class, 'inspection_id');
    }

    // Helper methods
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isApproved(): bool
    {
        return $this->approved_by_client && $this->status === 'approved';
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByInspector($query, $inspectorId)
    {
        return $query->where('inspector_id', $inspectorId);
    }

    /**
     * Generate a URL for a stored file — uses signed temporary URL on S3 (private bucket),
     * falls back to plain URL on local/public disk. Mirrors Property::getStorageUrl().
     */
    public function getStorageUrl(string $path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '#';
        }

        // Already a complete URL (or data URI) — return as-is.
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//') || str_starts_with($path, 'data:')) {
            return $path;
        }

        // Legacy local-storage path formats.
        if (str_starts_with($path, '/storage/')) {
            return url($path);
        }
        if (str_starts_with($path, 'storage/')) {
            return url('/' . $path);
        }
        if (str_starts_with($path, 'public/')) {
            $path = ltrim(substr($path, 7), '/');
        }

        $disk = config('filesystems.default', 'public');
        $storage = Storage::disk($disk);

        // Backward compatibility: some historical uploads were stored on the
        // local public disk; prefer default disk, but transparently fallback.
        if ($disk !== 'public') {
            try {
                if (!$storage->exists($path) && Storage::disk('public')->exists($path)) {
                    $disk = 'public';
                    $storage = Storage::disk($disk);
                }
            } catch (\Throwable $e) {
                // If existence checks fail, continue with configured default disk.
            }
        }

        $driver = config("filesystems.disks.{$disk}.driver");

        if ($driver !== 'local' && method_exists($storage, 'temporaryUrl')) {
            try {
                return $storage->temporaryUrl($path, now()->addMinutes(30));
            } catch (\Throwable $e) {
                // If temporary URL generation fails, fall back to plain storage URL.
            }
        }

        return $storage->url($path);
    }
}
