<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueMarker extends Model
{
    protected $fillable = [
        'property_id',
        'inspection_id',
        'spatial_model_id',
        'capture_session_id',
        'phar_finding_id',
        'created_by',
        'source_provider',
        'marker_type',
        'title',
        'severity',
        'status',
        'position_x',
        'position_y',
        'position_z',
        'normal_x',
        'normal_y',
        'normal_z',
        'room_name',
        'surface_label',
        'source_reference',
        'confidence',
        'attachments',
        'metadata',
        'description',
    ];

    protected $casts = [
        'position_x' => 'decimal:4',
        'position_y' => 'decimal:4',
        'position_z' => 'decimal:4',
        'normal_x' => 'decimal:6',
        'normal_y' => 'decimal:6',
        'normal_z' => 'decimal:6',
        'confidence' => 'decimal:2',
        'attachments' => 'array',
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

    public function spatialModel(): BelongsTo
    {
        return $this->belongsTo(SpatialModel::class);
    }

    public function captureSession(): BelongsTo
    {
        return $this->belongsTo(CaptureSession::class);
    }

    public function pharFinding(): BelongsTo
    {
        return $this->belongsTo(PHARFinding::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
