<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressTracker extends Model
{
    protected $fillable = [
        'project_id', 'updated_by', 'percent_complete', 'phase', 'remarks',
        'qc_items', 'before_photos', 'after_photos'
    ];

    protected $casts = [
        'percent_complete' => 'integer',
        'qc_items' => 'array',
        'before_photos' => 'array',
        'after_photos' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isComplete(): bool
    {
        return $this->percent_complete >= 100;
    }

    public function getPhaseStatus(): string
    {
        return match($this->percent_complete) {
            0 => 'Not Started',
            100 => 'Complete',
            default => 'In Progress - ' . $this->phase
        };
    }
}
