<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaptureSession extends Model
{
    public const PROVIDERS = [
        'manual_upload' => 'Manual Upload',
        'matterport' => 'Matterport',
        'resolv' => 'RESOLV',
        'phone_camera' => 'Phone Camera',
        'dslr' => 'DSLR / Mirrorless',
        'camera_360' => '360 Camera',
        'drone' => 'Drone',
        'lidar' => 'LiDAR Scanner',
        'thermal' => 'Thermal Camera',
        'bim_cad' => 'BIM / CAD',
    ];

    public const CAPTURE_TYPES = [
        'hosted_tour' => 'Hosted Tour',
        'glb_model' => 'GLB / glTF Model',
        'obj_mesh' => 'OBJ / Mesh Package',
        'point_cloud' => 'Point Cloud',
        'panorama' => '360 Panorama',
        'photo_set' => 'Photo Set',
        'video_walkthrough' => 'Video Walkthrough',
        'thermal_scan' => 'Thermal Scan',
        'wall_scan' => 'Wall Scan',
        'bim_model' => 'BIM / CAD Model',
        'document' => 'Document / Report',
    ];

    protected $fillable = [
        'property_id',
        'inspection_id',
        'captured_by',
        'provider',
        'capture_type',
        'device_name',
        'device_serial',
        'status',
        'accuracy_class',
        'captured_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
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

    public function capturedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captured_by');
    }

    public function spatialModels(): HasMany
    {
        return $this->hasMany(SpatialModel::class);
    }

    public function twinSourceFiles(): HasMany
    {
        return $this->hasMany(TwinSourceFile::class);
    }

    public function twinProcessingJobs(): HasMany
    {
        return $this->hasMany(TwinProcessingJob::class);
    }

    public function issueMarkers(): HasMany
    {
        return $this->hasMany(IssueMarker::class);
    }

    public function getProviderLabelAttribute(): string
    {
        return self::PROVIDERS[$this->provider] ?? ucfirst(str_replace('_', ' ', (string) $this->provider));
    }

    public function getCaptureTypeLabelAttribute(): string
    {
        return self::CAPTURE_TYPES[$this->capture_type] ?? ucfirst(str_replace('_', ' ', (string) $this->capture_type));
    }
}
