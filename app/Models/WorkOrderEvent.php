<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderEvent extends Model
{
    protected $fillable = [
        'remediation_work_order_id',
        'logged_by',
        'event_type',
        'occurred_at',
        'description',
        'payload',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'payload' => 'array',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(RemediationWorkOrder::class, 'remediation_work_order_id');
    }

    public function loggedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}
