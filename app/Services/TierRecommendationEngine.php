<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyComplexityScore;
use App\Models\TierRecommendationRule;
use App\Models\Inspection;

class TierRecommendationEngine
{
    /**
     * Calculate complexity score and recommend tier for a property
     * 
     * @param Property $property
     * @param Inspection|null $inspection
     * @return PropertyComplexityScore
     */
    public function calculateRecommendation(Property $property, ?Inspection $inspection = null): PropertyComplexityScore
    {
        // Gather property data
        $propertyData = $this->gatherPropertyData($property, $inspection);
        
        // Get all active recommendation rules
        $rules = TierRecommendationRule::active()->get();
        
        // Initialize score object
        $score = new PropertyComplexityScore([
            'property_id' => $property->id,
            'inspection_id' => $inspection?->id,
            'calculated_at' => now(),
            'calculated_by' => auth()->id(),
        ]);
        
        // Calculate individual factor scores
        $score->issue_severity_score = $this->calculateIssueSeverityScore($propertyData, $rules);
        $score->lifestyle_score = $this->calculateLifestyleScore($propertyData, $rules);
        $score->complexity_score = $this->calculateComplexityScore($propertyData, $rules);
        $score->access_difficulty_score = $this->calculateAccessScore($propertyData, $rules);
        $score->age_score = $this->calculateAgeScore($propertyData, $rules);
        $score->system_score = $this->calculateSystemScore($propertyData, $rules);
        $score->environmental_score = $this->calculateEnvironmentalScore($propertyData, $rules);
        
        // Calculate total weighted score
        $score->calculateTotal();
        
        // Generate recommendations
        $score->recommended_tier = $score->getTierRecommendation();
        $score->recommended_visit_frequency = $score->getVisitFrequency();
        $score->recommended_skill_level = $score->getSkillLevel();
        $score->recommended_base_price = $this->calculateBasePrice($score);
        
        // Store breakdown and applied rules
        $score->score_breakdown = $this->generateScoreBreakdown($score);
        $score->applied_rules = $this->getAppliedRules($propertyData, $rules);
        
        // Save the score
        $score->save();
        
        // Update property with current score
        $property->update([
            'current_complexity_score' => $score->total_complexity_score,
            'recommended_tier' => $score->recommended_tier,
        ]);
        
        return $score;
    }

    /**
     * Gather all relevant property data
     */
    protected function gatherPropertyData(Property $property, ?Inspection $inspection): array
    {
        $data = [
            // Basic property info
            'type' => $property->type,
            'year_built' => $property->year_built,
            'total_square_footage' => $property->total_square_footage,
            'occupied_by' => $property->occupied_by,
            'has_pets' => $property->has_pets,
            'has_kids' => $property->has_kids,
            'personality' => $property->personality,
            'known_problems' => $property->known_problems,
            'province' => $property->province,
            'city' => $property->city,
            
            // Lifestyle factors
            'care_goals' => $property->care_goals,
            'sensitivities' => $property->sensitivities,
            
            // Location
            'address' => $property->property_address,
        ];
        
        // Add inspection data if available
        if ($inspection) {
            $findings = $inspection->findings ?? [];
            $data['issue_severity'] = $this->determineIssueSeverity($findings);
            $data['system_complexity'] = $this->determineSystemComplexity($findings);
            $data['inspection_findings'] = $findings;
        } else {
            // Estimate from known problems
            $data['issue_severity'] = $this->estimateIssueSeverity($property->known_problems);
            $data['system_complexity'] = 'basic';
        }
        
        return $data;
    }

    /**
     * Calculate issue severity score (0-100)
     */
    protected function calculateIssueSeverityScore(array $data, $rules): int
    {
        $score = 0;
        $matchedRules = $rules->where('input_category', 'issue_severity');
        
        foreach ($matchedRules as $rule) {
            if ($rule->appliesTo($data)) {
                $score += $rule->getWeightedScore();
            }
        }
        
        return min(100, $score); // Cap at 100
    }

    /**
     * Calculate lifestyle score (0-100)
     */
    protected function calculateLifestyleScore(array $data, $rules): int
    {
        $score = 0;
        $matchedRules = $rules->where('input_category', 'property_use_lifestyle');
        
        foreach ($matchedRules as $rule) {
            if ($rule->appliesTo($data)) {
                $score += $rule->getWeightedScore();
            }
        }
        
        // Additional lifestyle factors
        if ($data['has_pets']) $score += 10;
        if ($data['has_kids']) $score += 10;
        if ($data['occupied_by'] === 'tenants') $score += 15;
        if (in_array($data['personality'], ['busy', 'high-use'])) $score += 15;
        
        return min(100, $score);
    }

    /**
     * Calculate property complexity score (0-100)
     */
    protected function calculateComplexityScore(array $data, $rules): int
    {
        $score = 0;
        $matchedRules = $rules->where('input_category', 'property_type_complexity');
        
        foreach ($matchedRules as $rule) {
            if ($rule->appliesTo($data)) {
                $score += $rule->getWeightedScore();
            }
        }
        
        // Size factor
        $sqft = $data['total_square_footage'] ?? 0;
        if ($sqft > 5000) $score += 20;
        elseif ($sqft > 3000) $score += 15;
        elseif ($sqft > 2000) $score += 10;
        
        // Type factor
        if (in_array($data['type'], ['multi-unit', 'duplex'])) $score += 15;
        
        return min(100, $score);
    }

    /**
     * Calculate access difficulty score (0-100)
     */
    protected function calculateAccessScore(array $data, $rules): int
    {
        $score = 0;
        $matchedRules = $rules->where('input_category', 'structural_access');
        
        foreach ($matchedRules as $rule) {
            if ($rule->appliesTo($data)) {
                $score += $rule->getWeightedScore();
            }
        }
        
        return min(100, $score);
    }

    /**
     * Calculate property age score (0-100)
     */
    protected function calculateAgeScore(array $data, $rules): int
    {
        $yearBuilt = $data['year_built'] ?? date('Y');
        $age = date('Y') - $yearBuilt;
        
        $score = 0;
        
        // Age scoring
        if ($age > 50) $score = 80;
        elseif ($age > 30) $score = 60;
        elseif ($age > 20) $score = 40;
        elseif ($age > 10) $score = 20;
        else $score = 10;
        
        // Apply rules
        $matchedRules = $rules->where('input_category', 'property_age');
        foreach ($matchedRules as $rule) {
            if ($rule->appliesTo($data)) {
                $score += $rule->getWeightedScore();
            }
        }
        
        return min(100, $score);
    }

    /**
     * Calculate system complexity score (0-100)
     */
    protected function calculateSystemScore(array $data, $rules): int
    {
        $score = 0;
        $matchedRules = $rules->where('input_category', 'system_complexity');
        
        foreach ($matchedRules as $rule) {
            if ($rule->appliesTo($data)) {
                $score += $rule->getWeightedScore();
            }
        }
        
        return min(100, $score);
    }

    /**
     * Calculate environmental factors score (0-100)
     */
    protected function calculateEnvironmentalScore(array $data, $rules): int
    {
        $score = 0;
        $matchedRules = $rules->where('input_category', 'environmental_factors');
        
        foreach ($matchedRules as $rule) {
            if ($rule->appliesTo($data)) {
                $score += $rule->getWeightedScore();
            }
        }
        
        return min(100, $score);
    }

    /**
     * Calculate recommended base price based on complexity score
     */
    protected function calculateBasePrice(PropertyComplexityScore $score): float
    {
        $complexity = $score->total_complexity_score;
        
        // Base pricing tiers (can be customized)
        if ($complexity >= 80) {
            return 1499.00; // Elite
        } elseif ($complexity >= 60) {
            return 849.00; // Premium
        } elseif ($complexity >= 40) {
            return 549.00; // Enhanced
        } elseif ($complexity >= 20) {
            return 349.00; // Essential
        } else {
            return 199.00; // Basic
        }
    }

    /**
     * Generate detailed score breakdown
     */
    protected function generateScoreBreakdown(PropertyComplexityScore $score): array
    {
        return [
            'factors' => [
                'issue_severity' => [
                    'score' => $score->issue_severity_score,
                    'weight' => 30,
                    'contribution' => ($score->issue_severity_score * 30) / 100,
                ],
                'lifestyle' => [
                    'score' => $score->lifestyle_score,
                    'weight' => 20,
                    'contribution' => ($score->lifestyle_score * 20) / 100,
                ],
                'complexity' => [
                    'score' => $score->complexity_score,
                    'weight' => 15,
                    'contribution' => ($score->complexity_score * 15) / 100,
                ],
                'access_difficulty' => [
                    'score' => $score->access_difficulty_score,
                    'weight' => 15,
                    'contribution' => ($score->access_difficulty_score * 15) / 100,
                ],
                'age' => [
                    'score' => $score->age_score,
                    'weight' => 10,
                    'contribution' => ($score->age_score * 10) / 100,
                ],
                'system' => [
                    'score' => $score->system_score,
                    'weight' => 5,
                    'contribution' => ($score->system_score * 5) / 100,
                ],
                'environmental' => [
                    'score' => $score->environmental_score,
                    'weight' => 5,
                    'contribution' => ($score->environmental_score * 5) / 100,
                ],
            ],
            'total' => $score->total_complexity_score,
            'grade' => $score->getComplexityGrade(),
        ];
    }

    /**
     * Get list of applied rules
     */
    protected function getAppliedRules(array $data, $rules): array
    {
        $applied = [];
        
        foreach ($rules as $rule) {
            if ($rule->appliesTo($data)) {
                $applied[] = [
                    'id' => $rule->id,
                    'name' => $rule->rule_name,
                    'category' => $rule->input_category,
                    'score' => $rule->complexity_score,
                    'weight' => $rule->priority_weight,
                ];
            }
        }
        
        return $applied;
    }

    /**
     * Determine issue severity from inspection findings
     */
    protected function determineIssueSeverity($findings): string
    {
        if (!is_array($findings)) return 'low';
        
        $criticalCount = 0;
        $highCount = 0;
        
        foreach ($findings as $finding) {
            $severity = $finding['severity'] ?? 'low';
            if ($severity === 'critical') $criticalCount++;
            if ($severity === 'high') $highCount++;
        }
        
        if ($criticalCount > 0) return 'critical';
        if ($highCount > 2) return 'high';
        if ($highCount > 0) return 'medium';
        return 'low';
    }

    /**
     * Estimate issue severity from known problems text
     */
    protected function estimateIssueSeverity(?string $knownProblems): string
    {
        if (!$knownProblems) return 'low';
        
        $critical = ['structural', 'foundation', 'roof leak', 'electrical hazard', 'gas leak'];
        $high = ['water damage', 'mold', 'pest', 'hvac failure'];
        
        $lower = strtolower($knownProblems);
        
        foreach ($critical as $keyword) {
            if (str_contains($lower, $keyword)) return 'critical';
        }
        
        foreach ($high as $keyword) {
            if (str_contains($lower, $keyword)) return 'high';
        }
        
        return 'medium';
    }

    /**
     * Determine system complexity from inspection
     */
    protected function determineSystemComplexity($findings): string
    {
        if (!is_array($findings)) return 'basic';
        
        $systemCount = count(array_filter($findings, function($f) {
            return in_array($f['type'] ?? '', ['hvac', 'plumbing', 'electrical', 'mechanical']);
        }));
        
        if ($systemCount > 10) return 'premium';
        if ($systemCount > 5) return 'advanced';
        if ($systemCount > 2) return 'standard';
        return 'basic';
    }
}
