<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TierRecommendationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_name',
        'description',
        'input_category',
        'condition_criteria',
        'complexity_score',
        'priority_weight',
        'recommended_adjustments',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'condition_criteria' => 'array',
        'recommended_adjustments' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Check if this rule applies to given property data
     * 
     * @param array $propertyData
     * @return bool
     */
    public function appliesTo(array $propertyData): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $criteria = $this->condition_criteria;
        $category = $this->input_category;

        // Check if property data matches this rule's criteria
        switch ($category) {
            case 'issue_severity':
                return $this->checkIssueSeverity($propertyData, $criteria);
            
            case 'property_use_lifestyle':
                return $this->checkLifestyle($propertyData, $criteria);
            
            case 'property_type_complexity':
                return $this->checkPropertyType($propertyData, $criteria);
            
            case 'structural_access':
                return $this->checkAccessDifficulty($propertyData, $criteria);
            
            case 'property_age':
                return $this->checkPropertyAge($propertyData, $criteria);
            
            case 'system_complexity':
                return $this->checkSystemComplexity($propertyData, $criteria);
            
            case 'environmental_factors':
                return $this->checkEnvironmental($propertyData, $criteria);
            
            default:
                return false;
        }
    }

    /**
     * Calculate weighted score contribution
     * 
     * @return int
     */
    public function getWeightedScore(): int
    {
        return $this->complexity_score * $this->priority_weight;
    }

    // Individual checker methods
    
    protected function checkIssueSeverity(array $data, array $criteria): bool
    {
        $severity = $data['issue_severity'] ?? 'low';
        return in_array($severity, $criteria['severity_levels'] ?? []);
    }

    protected function checkLifestyle(array $data, array $criteria): bool
    {
        $occupancy = $data['occupied_by'] ?? null;
        $hasPets = $data['has_pets'] ?? false;
        $hasKids = $data['has_kids'] ?? false;
        $personality = $data['personality'] ?? null;

        // Check if any criteria match
        if (isset($criteria['occupancy_types']) && in_array($occupancy, $criteria['occupancy_types'])) {
            return true;
        }
        if (isset($criteria['requires_pets']) && $hasPets === $criteria['requires_pets']) {
            return true;
        }
        if (isset($criteria['requires_kids']) && $hasKids === $criteria['requires_kids']) {
            return true;
        }
        if (isset($criteria['personality_types']) && in_array($personality, $criteria['personality_types'])) {
            return true;
        }

        return false;
    }

    protected function checkPropertyType(array $data, array $criteria): bool
    {
        $type = $data['type'] ?? null;
        $sqft = $data['total_square_footage'] ?? 0;

        if (isset($criteria['property_types']) && in_array($type, $criteria['property_types'])) {
            return true;
        }
        if (isset($criteria['min_sqft']) && $sqft >= $criteria['min_sqft']) {
            return true;
        }
        if (isset($criteria['max_sqft']) && $sqft <= $criteria['max_sqft']) {
            return true;
        }

        return false;
    }

    protected function checkAccessDifficulty(array $data, array $criteria): bool
    {
        // Check for difficult access indicators
        $knownProblems = strtolower($data['known_problems'] ?? '');
        $keywords = $criteria['difficulty_keywords'] ?? ['crawlspace', 'roofline', 'drainage', 'steep', 'narrow'];

        foreach ($keywords as $keyword) {
            if (str_contains($knownProblems, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    protected function checkPropertyAge(array $data, array $criteria): bool
    {
        $yearBuilt = $data['year_built'] ?? null;
        if (!$yearBuilt) return false;

        $age = date('Y') - $yearBuilt;

        if (isset($criteria['min_age']) && $age >= $criteria['min_age']) {
            return true;
        }
        if (isset($criteria['max_age']) && $age <= $criteria['max_age']) {
            return true;
        }

        return false;
    }

    protected function checkSystemComplexity(array $data, array $criteria): bool
    {
        // Could check for HVAC, plumbing, electrical complexity
        // This would come from inspection data
        return isset($data['system_complexity']) && 
               in_array($data['system_complexity'], $criteria['complexity_levels'] ?? []);
    }

    protected function checkEnvironmental(array $data, array $criteria): bool
    {
        // Check climate zone, terrain, exposure
        $province = $data['province'] ?? null;
        $city = $data['city'] ?? null;

        if (isset($criteria['provinces']) && in_array($province, $criteria['provinces'])) {
            return true;
        }
        if (isset($criteria['cities']) && in_array($city, $criteria['cities'])) {
            return true;
        }

        return false;
    }

    /**
     * Scope for active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Scope by category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('input_category', $category);
    }
}
