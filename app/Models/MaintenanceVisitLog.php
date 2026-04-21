<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceVisitLog extends Model
{
    protected $fillable = [
        'inspection_id',
        'visit_date',
        'finding_id',
        'logged_by',
        'work_description',
        'after_photos',
        'hours_worked',
        'status',
        'notes',
        'tools_used',
    ];

    protected $casts = [
        'visit_date'   => 'date',
        'after_photos' => 'array',
        'tools_used'   => 'array',
        'hours_worked' => 'decimal:2',
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(PHARFinding::class, 'finding_id');
    }

    public function loggedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}
