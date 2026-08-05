<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TwinSourceFile extends Model
{
    public const SOURCE_TYPES = [
        'glb' => 'GLB model',
        'gltf' => 'glTF model',
        'obj_bundle' => 'OBJ bundle',
        'e57' => 'E57 point cloud',
        'las' => 'LAS point cloud',
        'laz' => 'LAZ point cloud',
        'image' => 'Image',
        'panorama' => '360 panorama',
        'pdf' => 'PDF document',
        'other' => 'Other source',
    ];

    public const PROCESSING_STATUSES = [
        'uploading' => 'Uploading',
        'uploaded' => 'Uploaded',
        'awaiting_processing' => 'Awaiting processing',
        'queued' => 'Queued',
        'processing' => 'Processing',
        'ready' => 'Ready',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'parent_source_file_id',
        'property_id',
        'inspection_id',
        'capture_session_id',
        'spatial_model_id',
        'uploaded_by',
        'storage_disk',
        'storage_path',
        'original_filename',
        'stored_filename',
        'relative_path',
        'extension',
        'mime_type',
        'file_size',
        'checksum_sha256',
        'source_type',
        'file_role',
        'processing_status',
        'processing_error',
        'metadata',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'metadata' => 'array',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function parentSourceFile(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_source_file_id');
    }

    public function childSourceFiles(): HasMany
    {
        return $this->hasMany(self::class, 'parent_source_file_id')
            ->orderBy('file_role')
            ->orderBy('relative_path');
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function captureSession(): BelongsTo
    {
        return $this->belongsTo(CaptureSession::class);
    }

    public function spatialModel(): BelongsTo
    {
        return $this->belongsTo(SpatialModel::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function processingJobs(): HasMany
    {
        return $this->hasMany(TwinProcessingJob::class, 'source_file_id');
    }

    public function getSourceTypeLabelAttribute(): string
    {
        return self::SOURCE_TYPES[$this->source_type] ?? ucfirst(str_replace('_', ' ', (string) $this->source_type));
    }

    public function getProcessingStatusLabelAttribute(): string
    {
        return self::PROCESSING_STATUSES[$this->processing_status] ?? ucfirst(str_replace('_', ' ', (string) $this->processing_status));
    }
}
