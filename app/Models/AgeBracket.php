<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgeBracket extends Model
{
    protected $fillable = [
        'bracket_name',
        'min_age',
        'max_age',
        'score_points',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'min_age' => 'integer',
        'max_age' => 'integer',
        'score_points' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public static function getScoreForAge(int $age): int
    {
        $bracket = self::active()
            ->where('min_age', '<=', $age)
            ->where(function ($query) use ($age) {
                $query->where('max_age', '>=', $age)
                    ->orWhereNull('max_age');
            })
            ->first();

        return $bracket ? $bracket->score_points : 0;
    }
}
