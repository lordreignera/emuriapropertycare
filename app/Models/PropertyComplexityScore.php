<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyComplexityScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'inspection_id',
        'issue_severity_score',
        'lifestyle_score',
        'complexity_score',
        'access_difficulty_score',
        'age_score',
        'system_score',
        'environmental_score',
        'total_complexity_score',
        'recommended_tier',
        'recommended_visit_frequency',
        'recommended_skill_level',
        'recommended_base_price',
        'score_breakdown',
        'applied_rules',
        'calculated_at',
        'calculated_by',
    ];

    protected $casts = [
        'recommended_base_price' => 'decimal:2',
        'score_breakdown' => 'array',
        'applied_rules' => 'array',
        'calculated_at' => 'datetime',
    ];

    /**
     * Get the property this score belongs to.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the inspection that generated this score.
     */
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    /**
     * Get the user who calculated this score.
     */
    public function calculator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    /**
     * Calculate total complexity score from all factors
     */
    public function calculateTotal(): int
    {
        // Weighted calculation
        $weights = [
            'issue_severity' => 30,    // 30% weight - most important
            'lifestyle' => 20,         // 20% weight
            'complexity' => 15,        // 15% weight
            'access_difficulty' => 15, // 15% weight
            'age' => 10,              // 10% weight
            'system' => 5,            // 5% weight
            'environmental' => 5,     // 5% weight
        ];

        $total = 0;
        $total += ($this->issue_severity_score * $weights['issue_severity']) / 100;
        $total += ($this->lifestyle_score * $weights['lifestyle']) / 100;
        $total += ($this->complexity_score * $weights['complexity']) / 100;
        $total += ($this->access_difficulty_score * $weights['access_difficulty']) / 100;
        $total += ($this->age_score * $weights['age']) / 100;
        $total += ($this->system_score * $weights['system']) / 100;
        $total += ($this->environmental_score * $weights['environmental']) / 100;

        $this->total_complexity_score = (int) round($total);
        return $this->total_complexity_score;
    }

    /**
     * Get tier recommendation based on complexity score
     */
    public function getTierRecommendation(): string
    {
        $score = $this->total_complexity_score;

        if ($score >= 80) {
            return 'Elite Estate Care';
        } elseif ($score >= 60) {
            return 'Premium Protection';
        } elseif ($score >= 40) {
            return 'Enhanced Care';
        } elseif ($score >= 20) {
            return 'Essential Care';
        } else {
            return 'Basic Care';
        }
    }

    /**
     * Get recommended visit frequency based on score
     */
    public function getVisitFrequency(): int
    {
        $score = $this->total_complexity_score;

        if ($score >= 80) {
            return 24; // Weekly visits
        } elseif ($score >= 60) {
            return 12; // Monthly visits
        } elseif ($score >= 40) {
            return 6;  // Bi-monthly
        } elseif ($score >= 20) {
            return 4;  // Quarterly
        } else {
            return 2;  // Semi-annual
        }
    }

    /**
     * Get recommended skill level
     */
    public function getSkillLevel(): string
    {
        $score = $this->total_complexity_score;

        if ($score >= 80) {
            return 'expert';
        } elseif ($score >= 60) {
            return 'advanced';
        } elseif ($score >= 40) {
            return 'intermediate';
        } else {
            return 'basic';
        }
    }

    /**
     * Get complexity grade (A-F)
     */
    public function getComplexityGrade(): string
    {
        $score = $this->total_complexity_score;

        if ($score >= 90) return 'A+';
        if ($score >= 80) return 'A';
        if ($score >= 70) return 'B';
        if ($score >= 60) return 'C';
        if ($score >= 50) return 'D';
        return 'F';
    }
}
