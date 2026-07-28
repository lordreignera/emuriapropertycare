<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class SpatialModel extends Model
{
    public const SOURCE_TYPES = [
        'hosted_tour' => 'Hosted Tour',
        'runtime_3d_model' => 'Runtime 3D Model',
        'master_point_cloud' => 'Master Point Cloud',
        'point_cloud_tiles' => 'Point Cloud Tiles',
        'panorama_set' => 'Panorama Set',
        'thermal_evidence' => 'Thermal Evidence',
        'wall_scan_evidence' => 'Wall Scan Evidence',
        'bim_cad_model' => 'BIM / CAD Model',
        'document_reference' => 'Document Reference',
    ];

    protected $fillable = [
        'property_id',
        'inspection_id',
        'capture_session_id',
        'created_by',
        'provider',
        'source_type',
        'display_name',
        'runtime_format',
        'original_format',
        'provider_model_id',
        'external_url',
        'file_path',
        'thumbnail_path',
        'status',
        'processing_status',
        'is_primary',
        'accuracy_class',
        'coordinate_transform',
        'metadata',
        'processed_at',
    ];

    protected $casts = [
        'coordinate_transform' => 'array',
        'metadata' => 'array',
        'is_primary' => 'boolean',
        'processed_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function captureSession(): BelongsTo
    {
        return $this->belongsTo(CaptureSession::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function matterportModel(): HasOne
    {
        return $this->hasOne(MatterportModel::class);
    }

    public function issueMarkers(): HasMany
    {
        return $this->hasMany(IssueMarker::class);
    }

    public function getProviderLabelAttribute(): string
    {
        return CaptureSession::PROVIDERS[$this->provider] ?? ucfirst(str_replace('_', ' ', (string) $this->provider));
    }

    public function getSourceTypeLabelAttribute(): string
    {
        return self::SOURCE_TYPES[$this->source_type] ?? ucfirst(str_replace('_', ' ', (string) $this->source_type));
    }

    public function getViewerLabelAttribute(): string
    {
        if ($this->provider === 'matterport') {
            return 'Matterport hosted walkthrough';
        }

        if ($this->runtime_format) {
            return strtoupper($this->runtime_format) . ' model';
        }

        return $this->source_type_label;
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? $this->storageUrl($this->file_path) : null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail_path ? $this->storageUrl($this->thumbnail_path) : null;
    }

    public function getDetectedExtensionAttribute(): ?string
    {
        $path = $this->file_path ?: parse_url((string) $this->external_url, PHP_URL_PATH);
        $extension = strtolower((string) pathinfo((string) $path, PATHINFO_EXTENSION));

        return $extension !== '' ? $extension : null;
    }

    public function getViewerTypeAttribute(): string
    {
        $extension = $this->detected_extension;
        $runtimeFormat = strtolower((string) $this->runtime_format);
        $originalFormat = strtolower((string) $this->original_format);
        $formatTokens = array_filter([$extension, $runtimeFormat, $originalFormat]);

        if ($this->provider === 'matterport' && ($this->provider_model_id || $this->external_url)) {
            return 'hosted_tour';
        }

        if (in_array('glb', $formatTokens, true) || in_array('gltf', $formatTokens, true)) {
            return 'three_model';
        }

        if ($this->source_type === 'point_cloud_tiles' || in_array('potree', $formatTokens, true)) {
            return 'potree';
        }

        if ($this->source_type === 'panorama_set' && in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return 'panorama';
        }

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif'], true)) {
            return 'image';
        }

        if ($extension === 'pdf' || in_array('pdf', $formatTokens, true)) {
            return 'pdf';
        }

        if (in_array($extension, ['obj', 'fbx', 'dae', 'ply', 'e57', 'las', 'laz', 'pts', 'ptx', 'xyz', 'zip'], true)) {
            return 'conversion_needed';
        }

        if ($this->external_url) {
            return 'external_link';
        }

        return 'stored_evidence';
    }

    public function isRawPointCloud(): bool
    {
        return $this->source_type === 'master_point_cloud'
            || in_array($this->detected_extension, ['e57', 'las', 'laz', 'pts', 'ptx', 'xyz'], true);
    }

    public function needsPointCloudConversion(): bool
    {
        return $this->isRawPointCloud()
            && $this->status === 'active'
            && in_array($this->processing_status, ['queued', 'failed'], true)
            && filled($this->file_path);
    }

    private function storageUrl(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            return '#';
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
            } catch (\Throwable) {
                // Continue with configured storage if existence checks are unavailable.
            }
        }

        $driver = config("filesystems.disks.{$disk}.driver");

        if ($driver !== 'local' && method_exists($storage, 'temporaryUrl')) {
            try {
                return $storage->temporaryUrl($path, now()->addMinutes(30));
            } catch (\Throwable) {
                // Fall back to plain URL below.
            }
        }

        return $storage->url($path);
    }
}
