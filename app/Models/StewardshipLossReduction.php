<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StewardshipLossReduction extends Model
{
    protected $fillable = [
        'cpi_band',
        'loss_reduction',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'loss_reduction' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public static function getReductionByBand(string $band)
    {
        return self::where('cpi_band', $band)->first();
    }

    public function getFormattedReductionAttribute(): string
    {
        return number_format($this->loss_reduction * 100, 0) . '%';
    }
}
