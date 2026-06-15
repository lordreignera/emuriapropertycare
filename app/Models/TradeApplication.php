<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TradeApplication extends Model
{
    use HasFactory;

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_READY_FOR_REVIEW = 'ready_for_review';
    public const STATUS_NEEDS_MORE_INFORMATION = 'needs_more_information';
    public const STATUS_CONDITIONALLY_APPROVED = 'conditionally_approved';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'application_number',
        'company_name',
        'contact_person',
        'phone',
        'email',
        'service_area',
        'years_in_business',
        'technicians_count',
        'company_description',
        'system_ids',
        'subsystem_ids',
        'system_pricing',
        'availability',
        'pricing_units',
        'minimum_service_charge',
        'emergency_premium',
        'travel_charge_policy',
        'travel_policy_document',
        'material_policy',
        'material_policy_document',
        'equipment_policy',
        'equipment_policy_document',
        'disposal_policy',
        'disposal_policy_document',
        'standard_warranty',
        'warranty_document',
        'pricing_notes',
        'pricing_policy_document',
        'sample_activity_prices',
        'business_licence_status',
        'business_licence_number',
        'business_licence_expiry',
        'business_licence_document',
        'liability_insurance_status',
        'liability_insurance_provider',
        'liability_insurance_policy_number',
        'liability_insurance_expiry',
        'liability_insurance_document',
        'worksafebc_status',
        'worksafebc_number',
        'worksafebc_expiry',
        'worksafebc_document',
        'gst_status',
        'gst_number',
        'gst_document',
        'references',
        'additional_documents',
        'status',
        'admin_notes',
        'reviewed_by',
        'submitted_at',
        'reviewed_at',
    ];

    protected $casts = [
        'system_ids' => 'array',
        'subsystem_ids' => 'array',
        'system_pricing' => 'array',
        'availability' => 'array',
        'pricing_units' => 'array',
        'minimum_service_charge' => 'decimal:2',
        'sample_activity_prices' => 'array',
        'references' => 'array',
        'additional_documents' => 'array',
        'business_licence_expiry' => 'date',
        'liability_insurance_expiry' => 'date',
        'worksafebc_expiry' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (TradeApplication $application) {
            if (!empty($application->application_number)) {
                return;
            }

            do {
                $candidate = 'TA-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
            } while (self::query()->where('application_number', $candidate)->exists());

            $application->application_number = $candidate;
        });
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
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

    public function statusLabel(): string
    {
        return ucwords(str_replace('_', ' ', (string) $this->status));
    }

    public function getStorageUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//') || str_starts_with($path, 'data:')) {
            return $path;
        }

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

        if ($disk !== 'public') {
            try {
                if (!$storage->exists($path) && Storage::disk('public')->exists($path)) {
                    $disk = 'public';
                    $storage = Storage::disk($disk);
                }
            } catch (\Throwable $e) {
                // Continue with configured default disk when existence checks fail.
            }
        }

        $driver = config("filesystems.disks.{$disk}.driver");

        if ($driver !== 'local' && method_exists($storage, 'temporaryUrl')) {
            try {
                return $storage->temporaryUrl($path, now()->addMinutes(30));
            } catch (\Throwable $e) {
                // Fall through to plain disk URL if signed URLs are unavailable.
            }
        }

        return $storage->url($path);
    }
}
