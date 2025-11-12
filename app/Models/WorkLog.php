<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkLog extends Model
{
    protected $fillable = [
        'project_id', 'user_id', 'log_date', 'activity', 'hours_worked',
        'materials_used', 'photos', 'remarks'
    ];

    protected $casts = [
        'log_date' => 'date',
        'materials_used' => 'array',
        'photos' => 'array',
        'hours_worked' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTotalMaterialsCost(): float
    {
        $total = 0;
        foreach ($this->materials_used ?? [] as $material) {
            $total += $material['cost'] ?? 0;
        }
        return $total;
    }
}
