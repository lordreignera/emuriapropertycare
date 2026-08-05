<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwinProcessingJob extends Model
{
    public const STATUSES = [
        'queued' => 'Queued',
        'processing' => 'Processing',
        'ready' => 'Ready',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'property_id',
        'inspection_id',
        'capture_session_id',
        'source_file_id',
        'spatial_model_id',
        'created_by',
        'processor',
        'job_type',
        'queue_name',
        'status',
        'input_storage_disk',
        'input_storage_path',
        'output_storage_disk',
        'output_storage_path',
        'timeout_seconds',
        'attempts',
        'started_at',
        'completed_at',
        'processing_error',
        'metadata',
    ];

    protected $casts = [
        'timeout_seconds' => 'integer',
        'attempts' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
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

    public function sourceFile(): BelongsTo
    {
        return $this->belongsTo(TwinSourceFile::class, 'source_file_id');
    }

    public function spatialModel(): BelongsTo
    {
        return $this->belongsTo(SpatialModel::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }
}
