# Integrated CPI + BDC/PHAR Calculator Flow

**Last Updated:** February 26, 2026  
**Purpose:** Unified pricing system integrating CPI scoring with BDC/PHAR calculator methodology

---

## 🎯 OVERVIEW

This system combines:
- **CPI Scoring** (6 domains, 0-27 points) → Condition assessment
- **BDC Calculator** → Base deployment costs from operational parameters
- **PHAR Findings** → Remediation labour & material costs
- **Tier Assignment** → Dual-gate system (condition + cost pressure)
- **Final Pricing** → Per-unit breakdown with multipliers

---

## 📐 COMPLETE FLOW

### **STEP 1: Property Registration & BDC Calibration**

#### 1.1 Client Registers Property (FREE)
```
- Property Name, Address, Type
- Property Code Auto-Generated (e.g., PROP-2025-001)
- Status: pending_approval → approved
```

#### 1.2 BDC (Base Deployment Cost) Calculation

**BDC represents the baseline operational cost to service a property annually.**

```php
// BDC Calibration Engine Inputs
$loadedHours = 165;                    // Annual hours
$visitsPerYear = 8;                    // Based on property tier
$hoursPerVisit = 4.5;                  // Average visit duration
$hourlyRate = 165;                     // Loaded hourly labour rate
$infrastructureRate = 0.3;             // 30% overhead
$administrationRate = 0.12;            // 12% admin overhead

// BDC Calculation
$labourCost = $loadedHours * $hourlyRate;           // $5,940
$infrastructureCost = $labourCost * $infrastructureRate;  // $1,782
$administrationCost = $labourCost * $administrationRate;  // $712.80

$BDC = $labourCost + $infrastructureCost + $administrationCost;  // $8,434.80/year
```

**Database Storage:**
```sql
ALTER TABLE properties ADD COLUMN bdc_annual DECIMAL(10,2) DEFAULT 0;
ALTER TABLE properties ADD COLUMN bdc_loaded_hours INT DEFAULT 165;
ALTER TABLE properties ADD COLUMN bdc_visits_per_year INT DEFAULT 8;
ALTER TABLE properties ADD COLUMN bdc_hours_per_visit DECIMAL(4,2) DEFAULT 4.5;
```

---

### **STEP 2: PHAR Intake (Property Sizing)**

**Captures property-specific sizing data for calculations.**

```php
// Property Type determines sizing approach
if ($propertyType === 'residential') {
    $residentialUnitCount = 1;        // e.g., 1 single-family home
    $residentialFinishedSqft = 2500;  // Total livable square footage
    $propertySize = $residentialUnitCount;  // Used for per-unit calcs
    
} elseif ($propertyType === 'commercial') {
    $commercialUnitCount = 20;        // e.g., 20 office suites
    $avgCommercialUnitSqft = 700;     // Average unit size
    $commercialArea = $commercialUnitCount * $avgCommercialUnitSqft;  // 14,000 sqft
    $propertySize = $commercialArea;  // Used in BDC calculations
    
} elseif ($propertyType === 'mixed_use') {
    $residentialUnitCount = 10;
    $commercialUnitCount = 5;
    $mixedUseCommercialWeight = 50;   // 50% commercial, 50% residential
    
    // Weighted calculation
    $residentialComponent = $residentialUnitCount * (1 - $mixedUseCommercialWeight/100);
    $commercialComponent = $commercialUnitCount * ($mixedUseCommercialWeight/100);
    $propertySize = $residentialComponent + $commercialComponent;
}

// Computed PHAR Property Size (PSF)
$propertyPSF = $propertySize;  // Used by pricing engine
```

**Already exists in database:**
- `properties.type` (residential/commercial/mixed_use)
- `properties.residential_units`
- `properties.square_footage_interior`
- `properties.mixed_use_commercial_weight`

---

### **STEP 3: PHAR Assessment (Dual-Track Inspection)**

Inspector fills CPI inspection form with **two parallel data streams:**

#### **Track A: CPI Scoring (Condition Assessment)**

**Purpose:** Assess property condition (0-27 scale) → Maps to Condition Score (0-100)

```
Domain 1: System Design & Pressure (0-7 pts)
  - Unit shutoffs, shared risers, static pressure, isolation zones
  
Domain 2: Material Risk (0-5 pts)
  - Supply line material, drain system unknown
  
Domain 3: Age & Lifecycle (0-5 pts)
  - Building age, fixture age, systems documented
  
Domain 4: Access & Containment (0-3 pts)
  - Containment category (accessible/partial/poor/none)
  
Domain 5: Accessibility & Safety (0-4 pts, MAX not sum)
  - Crawlspace access, roof access, equipment requirements
  
Domain 6: Operational Complexity (0-3 pts)
  - Complexity category (low/medium/high/business-critical)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
CPI Total Score: 0-27 points
```

**CPI to Condition Score Mapping:**
```php
function mapCPItoConditionScore($cpiTotalScore) {
    if ($cpiTotalScore <= 2) return 95;  // CPI-0: Excellent (90-100)
    if ($cpiTotalScore <= 5) return 82;  // CPI-1: Good (75-89)
    if ($cpiTotalScore <= 8) return 67;  // CPI-2: Fair (60-74)
    if ($cpiTotalScore <= 11) return 50; // CPI-3: Poor (40-59)
    return 30;                            // CPI-4: Critical (0-39)
}

$conditionScore = mapCPItoConditionScore($cpiTotalScore);  // 0-100
```

**Database Fields:**
- Already exists: `inspections.cpi_total_score`, `inspections.cpi_band`, `inspections.cpi_multiplier`
- **NEW FIELD NEEDED:** `inspections.condition_score` (0-100)

---

#### **Track B: Findings Entry (Remediation Costs)**

**Purpose:** Capture specific issues with estimated labour & material costs

```
For each finding:
┌─────────────────────────────────────────┐
│ System Category: Plumbing - Supply      │
│ Location: Second Floor Bathroom         │
│ Specific Spot: Behind toilet            │
│ Issue: Pinhole leak in copper supply    │
│ Severity: High                          │
│                                         │
│ Recommendation Option 1:                │
│   Patch leak temporarily                │
│   Labour Cost: $200                     │
│   Material Cost: $25                    │
│                                         │
│ Recommendation Option 2:                │
│   Replace 6ft section of pipe           │
│   Labour Cost: $450                     │
│   Material Cost: $75                    │
│                                         │
│ Recommendation Option 3:                │
│   Repipe entire bathroom                │
│   Labour Cost: $1,200                   │
│   Material Cost: $300                   │
│                                         │
│ Recommended Option: [2] ✓               │
│ Risk if Deferred: Water damage risk    │
│ Urgency: Within week                    │
└─────────────────────────────────────────┘
```

**System auto-calculates from ALL findings:**
```php
$FRLC = 0;  // Findings Remediation Labour Cost
$FMC = 0;   // Findings Material Cost

foreach ($findings as $finding) {
    // Use recommended option for each finding
    $option = $finding['recommended_option'];  // 1, 2, or 3
    
    $FRLC += $finding["option_{$option}_labour_cost"];
    $FMC += $finding["option_{$option}_material_cost"];
}

// Example totals:
// FRLC = $9,405.00 (annual)
// FMC = $1,081.00 (annual)
```

**Database Schema Needed:**

```sql
CREATE TABLE phar_findings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    inspection_id BIGINT UNSIGNED NOT NULL,
    property_id BIGINT UNSIGNED NOT NULL,
    
    -- Finding Details
    system_category VARCHAR(50) NOT NULL,  -- plumbing_supply, electrical, etc.
    location VARCHAR(255) NOT NULL,
    specific_spot VARCHAR(255),
    issue_description TEXT NOT NULL,
    severity ENUM('critical', 'high', 'medium', 'low', 'informational') NOT NULL,
    
    -- Recommendation Option 1
    recommendation_option_1 TEXT,
    option_1_labour_cost DECIMAL(10,2) DEFAULT 0,
    option_1_material_cost DECIMAL(10,2) DEFAULT 0,
    
    -- Recommendation Option 2
    recommendation_option_2 TEXT,
    option_2_labour_cost DECIMAL(10,2) DEFAULT 0,
    option_2_material_cost DECIMAL(10,2) DEFAULT 0,
    
    -- Recommendation Option 3
    recommendation_option_3 TEXT,
    option_3_labour_cost DECIMAL(10,2) DEFAULT 0,
    option_3_material_cost DECIMAL(10,2) DEFAULT 0,
    
    -- Selected Option
    recommended_option INT DEFAULT 1,  -- Which option: 1, 2, or 3
    
    -- Additional Info
    risk_if_deferred TEXT,
    urgency ENUM('immediate', 'within_week', 'within_month', 'within_quarter', 'annual', 'future'),
    
    -- Status
    status ENUM('open', 'in_progress', 'resolved', 'deferred') DEFAULT 'open',
    
    -- Photos & Evidence
    photos JSON,
    
    FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

### **STEP 4: Merge Bridge (TRC Calculation)**

**Purpose:** Combine all cost components into Total Remediation Cost

```php
// Components
$BDC = 8434.80;   // Base Deployment Cost (from Step 1)
$FRLC = 9405.00;  // Findings Remediation Labour Cost (from Step 3B)
$FMC = 1081.00;   // Findings Material Cost (from Step 3B)

// Total Remediation Cost (Annual)
$TRC = $BDC + $FRLC + $FMC;  // $18,920.80/year

// ARP (Annual Recurring Price) - Monthly
$ARP_monthly = $TRC / 12;  // $1,576.73/month
```

**Database Storage:**
```sql
ALTER TABLE inspections ADD COLUMN bdc_snapshot DECIMAL(10,2) DEFAULT 0;
ALTER TABLE inspections ADD COLUMN frlc_total DECIMAL(10,2) DEFAULT 0;
ALTER TABLE inspections ADD COLUMN fmc_total DECIMAL(10,2) DEFAULT 0;
ALTER TABLE inspections ADD COLUMN trc_annual DECIMAL(10,2) DEFAULT 0;
ALTER TABLE inspections ADD COLUMN arp_monthly DECIMAL(10,2) DEFAULT 0;
```

---

### **STEP 5: Tier Assignment (Dual-Gate System)**

**Purpose:** Determine service tier using two independent gates

#### Gate 1: Condition-Based Tier (Tier_Score)
```php
function getTierFromConditionScore($conditionScore) {
    if ($conditionScore >= 90) return 'Essentials';      // Excellent condition
    if ($conditionScore >= 75) return 'Essentials';      // Good condition
    if ($conditionScore >= 60) return 'White-Glove';     // Fair condition
    if ($conditionScore >= 40) return 'White-Glove';     // Poor condition
    return 'Critical Care';                               // Critical condition
}

$tierScore = getTierFromConditionScore($conditionScore);
```

#### Gate 2: Cost-Pressure Tier (Tier_ARP)
```php
// Base package pricing (from pricing_packages table)
$packagePrices = [
    'Essentials' => 381,
    'Premium' => 703,
    'White-Glove' => 1200,
    'Critical Care' => 1800,
];

function getTierFromARP($arp_monthly, $packagePrices) {
    // ARP pushes up if it exceeds base tier cost
    if ($arp_monthly >= $packagePrices['Critical Care']) return 'Critical Care';
    if ($arp_monthly >= $packagePrices['White-Glove']) return 'White-Glove';
    if ($arp_monthly >= $packagePrices['Premium']) return 'Premium';
    return 'Essentials';
}

$tierARP = getTierFromARP($ARP_monthly, $packagePrices);
```

#### Final Tier Selection
```php
$tierFinal = max($tierScore, $tierARP);  // Take higher tier

// Example:
// $tierScore = 'Essentials' (condition is good)
// $tierARP = 'White-Glove' (ARP is $1,576 > $703)
// $tierFinal = 'White-Glove' ✓ (cost pressure bumps up tier)
```

**Business Logic:**
- **"Pressure from below"** - if remediation costs are high, tier must reflect that
- **Condition score** provides baseline expectation
- **Final tier** is always the more conservative (higher) of the two

---

### **STEP 6: Multiplier Application**

**Purpose:** Apply tier-based pricing multiplier

```php
// Tier Multipliers (from your CPI multipliers table)
$multipliers = [
    'Essentials' => 1.00,
    'Premium' => 1.15,
    'White-Glove' => 1.35,
    'Critical Care' => 1.55,
];

$multiplierFinal = $multipliers[$tierFinal];  // 1.35 for White-Glove

// Apply multiplier to ARP
$ARP_Equivalent_Final = $ARP_monthly * $multiplierFinal;
// $1,576.73 × 1.35 = $2,128.59/month
```

**Database Storage:**
```sql
ALTER TABLE inspections ADD COLUMN tier_score VARCHAR(50);
ALTER TABLE inspections ADD COLUMN tier_arp VARCHAR(50);
ALTER TABLE inspections ADD COLUMN tier_final VARCHAR(50);
ALTER TABLE inspections ADD COLUMN multiplier_final DECIMAL(4,2) DEFAULT 1.00;
ALTER TABLE inspections ADD COLUMN arp_equivalent_final DECIMAL(10,2) DEFAULT 0;
```

---

### **STEP 7: Scientific Final Monthly (Floor Adjustment)**

**Purpose:** Ensure price never drops below base package price

```php
// Get base package price for the final tier
$basePackagePrice = $packagePrices[$tierFinal];  // $1,200 for White-Glove

// Scientific Final Monthly = max(calculated, base)
$scientificFinalMonthly = max($ARP_Equivalent_Final, $basePackagePrice);

// Example:
// $ARP_Equivalent_Final = $2,128.59
// $basePackagePrice = $1,200
// $scientificFinalMonthly = $2,128.59 ✓ (use calculated since higher)

// Alternative example where floor kicks in:
// $ARP_Equivalent_Final = $950
// $basePackagePrice = $1,200
// $scientificFinalMonthly = $1,200 ✓ (use base package floor)
```

**Database Storage:**
```sql
ALTER TABLE inspections ADD COLUMN base_package_price DECIMAL(10,2) DEFAULT 0;
ALTER TABLE inspections ADD COLUMN scientific_final_monthly DECIMAL(10,2) DEFAULT 0;
ALTER TABLE inspections ADD COLUMN scientific_final_annual DECIMAL(10,2) DEFAULT 0;
```

---

### **STEP 8: Per-Unit Breakdown (Multi-Unit Properties)**

**Purpose:** Calculate per-unit costs for commercial/multi-residential properties

```php
// Determine unit count for breakdown
$unitsForCalc = max($property->residential_units, $property->commercial_units);

if ($unitsForCalc > 1) {
    // Annual breakdowns
    $bdcPerUnit = $BDC / $unitsForCalc;
    $frlcPerUnit = ($FRLC * 12) / $unitsForCalc;  // Convert monthly to annual
    $fmcPerUnit = ($FMC * 12) / $unitsForCalc;
    $trcPerUnitAnnual = ($TRC * 12) / $unitsForCalc;
    
    // Monthly breakdowns
    $arpPerUnit = $ARP_monthly / $unitsForCalc;
    $finalMonthlyPerUnit = $scientificFinalMonthly / $unitsForCalc;
    
    // Example (20 commercial units):
    // BDC: $8,434.80 / 20 = $421.74/unit/year
    // FRLC: $2,970 / 20 = $148.50/unit/year
    // FMC: $131.50 / 20 = $6.58/unit/year
    // TRC: $11,536.30 / 20 = $576.82/unit/year
    // Final Monthly: $1,297.83 / 20 = $64.89/unit/month
}
```

**Database Storage:**
```sql
ALTER TABLE inspections ADD COLUMN units_for_calculation INT DEFAULT 1;
ALTER TABLE inspections ADD COLUMN bdc_per_unit_annual DECIMAL(10,2) DEFAULT 0;
ALTER TABLE inspections ADD COLUMN frlc_per_unit_annual DECIMAL(10,2) DEFAULT 0;
ALTER TABLE inspections ADD COLUMN fmc_per_unit_annual DECIMAL(10,2) DEFAULT 0;
ALTER TABLE inspections ADD COLUMN trc_per_unit_annual DECIMAL(10,2) DEFAULT 0;
ALTER TABLE inspections ADD COLUMN final_monthly_per_unit DECIMAL(10,2) DEFAULT 0;
```

---

## 🔄 COMPLETE FLOW SUMMARY

```
┌─────────────────────────────────────────────────────────────┐
│ STEP 1: Property Registration & BDC Calibration            │
│ → Calculate baseline operational cost                       │
│ → BDC = $8,434.80/year                                      │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ STEP 2: PHAR Intake (Property Sizing)                      │
│ → Capture units, sqft, property type                        │
│ → Property PSF = 14,000 sqft (20 commercial units)         │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ STEP 3A: CPI Scoring (Condition Assessment)                │
│ → 6 domains scored (0-27 total)                            │
│ → CPI Total = 10 points → CPI-3 (Poor)                     │
│ → Condition Score = 50/100                                  │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ STEP 3B: Findings Entry (Remediation Costs)                │
│ → Inspector logs findings with cost estimates               │
│ → FRLC = $9,405 (labour)                                   │
│ → FMC = $1,081 (materials)                                 │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ STEP 4: Merge Bridge (TRC Calculation)                     │
│ → TRC = BDC + FRLC + FMC                                    │
│ → TRC = $18,920.80/year                                     │
│ → ARP = $1,576.73/month                                     │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ STEP 5: Tier Assignment (Dual-Gate)                        │
│ → Gate 1: Condition (50) → White-Glove                     │
│ → Gate 2: ARP ($1,577) → White-Glove                       │
│ → Tier_Final = White-Glove                                  │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ STEP 6: Multiplier Application                             │
│ → White-Glove Multiplier = 1.35x                            │
│ → ARP_Equivalent_Final = $1,577 × 1.35 = $2,128.59         │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ STEP 7: Scientific Final Monthly (Floor Adjustment)        │
│ → Base Package Price = $1,200                               │
│ → Scientific Final = max($2,128.59, $1,200) = $2,128.59   │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ STEP 8: Per-Unit Breakdown                                 │
│ → 20 commercial units                                       │
│ → Final Monthly Per Unit = $2,128.59 / 20 = $106.43        │
└─────────────────────────────────────────────────────────────┘
```

---

## 📊 SUMMARY: What Changes From Current System

### WHAT STAYS THE SAME ✅
- CPI 6-domain scoring (0-27 points)
- CPI bands (CPI-0 through CPI-4)
- Database-driven domain/factor configuration
- Real-time JavaScript calculations on form
- Inspection form UI/UX

### WHAT'S NEW ✨
1. **BDC Calculation** - Baseline operational cost calculator
2. **Findings with Costs** - Inspector enters labour & material costs per finding
3. **FRLC & FMC Tracking** - Auto-sum from all findings
4. **TRC Formula** - BDC + FRLC + FMC = Total Remediation Cost
5. **ARP Calculation** - TRC / 12 = Annual Recurring Price (monthly)
6. **Condition Score** - CPI score mapped to 0-100 scale
7. **Dual-Gate Tier Assignment** - Both condition AND cost determine tier
8. **Multiplier System** - Tier-based pricing multipliers (not just CPI bands)
9. **Scientific Final Monthly** - Floor adjustment to base package price
10. **Per-Unit Breakdown** - Detailed per-unit cost breakdown

### INTEGRATION POINTS 🔗
- **CPI Score** feeds into **Condition Score**
- **Condition Score** feeds into **Tier_Score** (Gate 1)
- **ARP** feeds into **Tier_ARP** (Gate 2)
- **Tier_Final** determines **Multiplier_Final**
- **Multiplier_Final** adjusts **ARP** to get **ARP_Equivalent_Final**
- **Base Package Price** provides **floor** for **Scientific Final Monthly**

---

## 🛠️ IMPLEMENTATION CHECKLIST

- [ ] **Database Migrations**
  - [ ] Add BDC fields to `properties` table
  - [ ] Add condition_score, FRLC, FMC, TRC, ARP to `inspections` table
  - [ ] Add tier tracking fields (tier_score, tier_arp, tier_final)
  - [ ] Add multiplier and final pricing fields
  - [ ] Add per-unit breakdown fields
  - [ ] Create `phar_findings` table

- [ ] **BDC Calculator**
  - [ ] Create `BDCCalculator` service class
  - [ ] Admin interface to set BDC parameters (loaded hours, rates)
  - [ ] Auto-calculate BDC on property approval

- [ ] **PHAR Intake**
  - [ ] Already exists (property registration form)
  - [ ] Ensure all sizing fields captured

- [ ] **Findings Entry Form**
  - [ ] Extend inspection form with findings section
  - [ ] Support 3 recommendation options per finding
  - [ ] Labour cost + material cost inputs
  - [ ] Auto-calculate FRLC & FMC totals
  - [ ] Real-time preview of findings impact

- [ ] **Merge Bridge Calculator**
  - [ ] Create `MergeBridgeCalculator` service class
  - [ ] Calculate TRC from BDC + FRLC + FMC
  - [ ] Calculate ARP (monthly)

- [ ] **Tier Assignment Engine**
  - [ ] Create `TierAssignmentEngine` service class
  - [ ] Implement dual-gate logic (condition + ARP)
  - [ ] Map CPI score to condition score (0-100)
  - [ ] Determine tier_score from condition
  - [ ] Determine tier_arp from ARP
  - [ ] Select tier_final = max(tier_score, tier_arp)

- [ ] **Multiplier & Final Pricing**
  - [ ] Update `TierRecommendationEngine` to use new tier system
  - [ ] Apply tier multipliers (not just CPI multipliers)
  - [ ] Calculate ARP_Equivalent_Final
  - [ ] Apply floor adjustment (scientific final monthly)

- [ ] **Per-Unit Breakdown**
  - [ ] Create `PerUnitCalculator` service class
  - [ ] Calculate all per-unit metrics
  - [ ] Display in inspection report

- [ ] **UI Updates**
  - [ ] Update inspection form with findings section
  - [ ] Real-time calculations for all new metrics
  - [ ] Display all breakdown components
  - [ ] Show tier assignment reasoning (dual gates)
  - [ ] Per-unit breakdown table

- [ ] **Testing**
  - [ ] Test with residential property (1 unit)
  - [ ] Test with commercial property (20 units)
  - [ ] Test with mixed-use property
  - [ ] Verify calculations match Excel examples
  - [ ] Test edge cases (no findings, critical findings, etc.)

---

## 🎯 KEY BUSINESS RULES

1. **BDC is property-specific** - Based on size, visits, complexity
2. **FRLC & FMC come from inspection findings** - Inspector-driven
3. **TRC represents total annual cost** - All-in cost basis
4. **ARP is monthly** - TRC / 12 for client-facing pricing
5. **Condition score drives baseline tier** - Poor condition → higher tier
6. **Cost pressure can bump up tier** - High ARP forces higher tier
7. **Multiplier reflects tier** - Not just CPI band
8. **Scientific final has floor** - Never below base package price
9. **Per-unit breakdown** - Transparency for multi-unit properties
10. **All data immutable after inspection** - Snapshot in time

---

## 📈 EXAMPLE WALKTHROUGH

**Property:** 20-unit commercial building  
**Inspector:** Conducts PHAR assessment

```
Step 1: BDC = $8,434.80/year
Step 2: Property Size = 14,000 sqft (20 units × 700 sqft)
Step 3A: CPI Score = 10 pts → Condition Score = 50/100
Step 3B: 15 findings logged → FRLC = $9,405, FMC = $1,081
Step 4: TRC = $18,920.80, ARP = $1,576.73/month
Step 5: Tier_Score = White-Glove, Tier_ARP = White-Glove → Tier_Final = White-Glove
Step 6: Multiplier = 1.35x → ARP_Equivalent = $2,128.59
Step 7: Base = $1,200 → Scientific Final = $2,128.59 (higher, so use it)
Step 8: Per Unit = $2,128.59 / 20 = $106.43/unit/month
```

**Client sees:**
- Monthly Price: $2,128.59 ($106.43 per unit)
- Annual Price: $25,543.08
- Tier: White-Glove Service
- Visit Frequency: 8 visits/year
- Cost Breakdown: BDC + Findings Remediation + Materials

---

**End of Document**
