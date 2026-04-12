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
        'assigned_by',
        'scheduled_date',
        'completed_date',
        'summary',
        'findings',
        'notes',
        'report_file',
        'photos',
        'status',
        'approved_by_client',
        'client_approved_at',
        'inspection_fee_amount',
        'inspection_fee_status',
        'inspection_fee_paid_at',
        'work_payment_amount',
        'work_payment_status',
        'work_payment_cadence',
        'work_payment_paid_at',
        'work_stripe_payment_intent_id',
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
    ];

    protected $casts = [
        'scheduled_date'  => 'datetime',
        'completed_date'  => 'datetime',
        'findings'        => 'array',
        'system_scores'   => 'array',
        'photos'          => 'array',
        'approved_by_client' => 'boolean',
        'client_approved_at' => 'datetime',
        'inspection_fee_amount' => 'decimal:2',
        'inspection_fee_paid_at' => 'datetime',
        'work_payment_amount' => 'decimal:2',
        'work_payment_paid_at' => 'datetime',
        'property_size_psf' => 'decimal:2',
        'estimated_task_hours' => 'decimal:2',
        'minimum_required_hours' => 'decimal:2',
        'bdc_visits_per_year' => 'decimal:2',
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

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function scopeOfWorks(): HasMany
    {
        return $this->hasMany(ScopeOfWork::class);
    }

    public function pharFindings(): HasMany
    {
        return $this->hasMany(PHARFinding::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(InspectionMaterial::class);
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
        $disk = config('filesystems.default', 'public');
        $storage = Storage::disk($disk);
        $driver = config("filesystems.disks.{$disk}.driver");

        if ($driver !== 'local' && method_exists($storage, 'temporaryUrl')) {
            return $storage->temporaryUrl($path, now()->addMinutes(30));
        }

        return $storage->url($path);
    }
}
