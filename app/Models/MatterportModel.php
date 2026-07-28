<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatterportModel extends Model
{
    protected $fillable = [
        'property_id',
        'inspection_id',
        'spatial_model_id',
        'created_by',
        'model_sid',
        'model_name',
        'model_url',
        'thumbnail_url',
        'status',
        'scanned_at',
        'notes',
    ];

    protected $casts = [
        'scanned_at' => 'date',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function showcaseUrl(?string $sdkKey = null): string
    {
        $query = [
            'm' => $this->model_sid,
            'play' => '1',
        ];

        if ($sdkKey) {
            $query['applicationKey'] = $sdkKey;
        }

        return 'https://my.matterport.com/show/?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }
}
