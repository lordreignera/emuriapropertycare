<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContainmentCategory extends Model
{
    protected $fillable = [
        'category_code',
        'category_name',
        'score_points',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'score_points' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
