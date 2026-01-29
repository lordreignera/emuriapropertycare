# Inspection Form - CPI Scoring Inputs Specification

## Overview
The inspection form MUST capture these specific inputs to calculate the client's pricing tier. Each input maps to a CPI domain and contributes to the final CPI score (0-27 range), which determines the CPI band (CPI-0 to CPI-4) and pricing multiplier (1.00x to 1.55x).

---

## 🎯 DOMAIN 1: System Design & Pressure (Max 7 points)

### Input 1.1: Unit-Level Water Shut-offs Present?
- **Field Type**: Radio buttons (Yes/No)
- **Field Name**: `cpi_unit_shutoffs`
- **Scoring**:
  - **No** → 3 points (high risk - cascades across units)
  - **Yes** → 0 points (good - units can be isolated)
- **Notes**: "If No, cascades risk across units"

```html
<div class="form-group">
    <label>Unit-level water shut-offs present? <span class="text-danger">*</span></label>
    <div class="form-check">
        <input type="radio" name="cpi_unit_shutoffs" value="no" id="shutoffs_no" required>
        <label for="shutoffs_no">No (3 points)</label>
    </div>
    <div class="form-check">
        <input type="radio" name="cpi_unit_shutoffs" value="yes" id="shutoffs_yes">
        <label for="shutoffs_yes">Yes (0 points)</label>
    </div>
    <small class="text-muted">If No, cascades risk across units</small>
</div>
```

---

### Input 1.2: Shared Risers Impacting Multiple Units?
- **Field Type**: Radio buttons (Yes/No)
- **Field Name**: `cpi_shared_risers`
- **Scoring**:
  - **Yes** → 2 points (vertical dependency elevates severity)
  - **No** → 0 points
- **Notes**: "Vertical dependency elevates severity"

```html
<div class="form-group">
    <label>Shared risers impacting multiple units? <span class="text-danger">*</span></label>
    <div class="form-check">
        <input type="radio" name="cpi_shared_risers" value="yes" id="risers_yes" required>
        <label for="risers_yes">Yes (2 points)</label>
    </div>
    <div class="form-check">
        <input type="radio" name="cpi_shared_risers" value="no" id="risers_no">
        <label for="risers_no">No (0 points)</label>
    </div>
    <small class="text-muted">Vertical dependency elevates severity</small>
</div>
```

---

### Input 1.3: Static Water Pressure (PSI)
- **Field Type**: Number input
- **Field Name**: `cpi_static_pressure`
- **Scoring**:
  - **> 90 PSI** → 2 points (serves >3 at >90 PSI)
  - **≤ 90 PSI** → 0 points
- **Notes**: "Serves >3 at >90 PSI"

```html
<div class="form-group">
    <label>Static water pressure (PSI) <span class="text-danger">*</span></label>
    <input type="number" name="cpi_static_pressure" class="form-control" 
           placeholder="e.g., 120" required>
    <small class="text-muted">If pressure >90 PSI → 2 points</small>
</div>
```

---

### Input 1.4: Isolation Zones Present?
- **Field Type**: Radio buttons (Yes/No)
- **Field Name**: `cpi_isolation_zones`
- **Scoring**:
  - **No** → 2 points (harder containment)
  - **Yes** → 0 points
- **Notes**: "If No, harder containment"

```html
<div class="form-group">
    <label>Isolation zones present? <span class="text-danger">*</span></label>
    <div class="form-check">
        <input type="radio" name="cpi_isolation_zones" value="no" id="zones_no" required>
        <label for="zones_no">No (2 points)</label>
    </div>
    <div class="form-check">
        <input type="radio" name="cpi_isolation_zones" value="yes" id="zones_yes">
        <label for="zones_yes">Yes (0 points)</label>
    </div>
    <small class="text-muted">If No, harder containment</small>
</div>
```

**Domain 1 Total**: Sum of all above (Max 7 points)

---

## 🎯 DOMAIN 2: Material Risk - Supply Lines (Max 5 points)

### Input 2.1: Primary Supply-Line Material
- **Field Type**: Dropdown (from `supply_line_materials` table)
- **Field Name**: `cpi_supply_material`
- **Scoring**: Based on lookup table
  - **Copper** → 0 points (Poly-B automatically drives high CPI)
  - **PEX** → 1 point
  - **CPVC** → 2 points
  - **Galvanized** → 3 points
  - **Poly-B** → 4 points (automatically drives high CPI)
  - **Mixed/Unknown** → 2 points

```html
<div class="form-group">
    <label>Primary supply-line material (select from Lookups list) <span class="text-danger">*</span></label>
    <select name="cpi_supply_material" class="form-control" required>
        <option value="">-- Select Material --</option>
        @foreach($supplyMaterials as $material)
            <option value="{{ $material->id }}" data-score="{{ $material->score_points }}">
                {{ $material->material_name }} ({{ $material->score_points }} points - {{ $material->risk_level }})
            </option>
        @endforeach
    </select>
    <small class="text-muted">Poly-B automatically drives high CPI</small>
</div>
```

---

### Input 2.2: Drain/Waste Material Unknown?
- **Field Type**: Radio buttons (Yes/No)
- **Field Name**: `cpi_drain_material_unknown`
- **Scoring**:
  - **Yes** → 0 points (optional +1 uncertainty modifier for drains/waste)
  - **No** → 0 points
- **Notes**: "Optional +1 uncertainty modifier for drains/waste"

```html
<div class="form-group">
    <label>Drain/Waste material unknown? <span class="text-danger">*</span></label>
    <div class="form-check">
        <input type="radio" name="cpi_drain_material_unknown" value="yes" id="drain_yes" required>
        <label for="drain_yes">Yes</label>
    </div>
    <div class="form-check">
        <input type="radio" name="cpi_drain_material_unknown" value="no" id="drain_no">
        <label for="drain_no">No</label>
    </div>
    <small class="text-muted">Optional +1 uncertainty modifier for drains/waste</small>
</div>
```

**Domain 2 Total**: Primary material score + drain modifier (Max 5 points)

---

## 🎯 DOMAIN 3: Age & Lifecycle (Max 5 points)

### Input 3.1: Building Age (Years)
- **Field Type**: Number input
- **Field Name**: `cpi_building_age`
- **Scoring**: Based on `age_brackets` table
  - **0-10 years** → 0 points
  - **11-25 years** → 1 point (score bracketed by age)
  - **26-40 years** → 2 points
  - **41-60 years** → 3 points
  - **61+ years** → 4 points

```html
<div class="form-group">
    <label>Building age (years) <span class="text-danger">*</span></label>
    <input type="number" name="cpi_building_age" class="form-control" 
           placeholder="e.g., 35" min="0" required>
    <small class="text-muted">Score bracketed by age (0-10: 0pts, 11-25: 1pt, 26-40: 2pts, 41-60: 3pts, 61+: 4pts)</small>
</div>
```

---

### Input 3.2: Fixture/System Age (Years)
- **Field Type**: Number input
- **Field Name**: `cpi_fixture_age`
- **Scoring**: Based on age
  - **0-10 years** → 0 points (valves, heaters, pumps, etc fixtures)
  - **11+ years** → Age-based scoring

```html
<div class="form-group">
    <label>Fixture/system age (years) <span class="text-danger">*</span></label>
    <input type="number" name="cpi_fixture_age" class="form-control" 
           placeholder="e.g., 8" min="0" required>
    <small class="text-muted">Valves, heaters, pumps, etc. fixtures</small>
</div>
```

---

### Input 3.3: Systems Documented?
- **Field Type**: Radio buttons (Yes/No)
- **Field Name**: `cpi_systems_documented`
- **Scoring**:
  - **Yes** → 0 points (+1 if No - uncertainty modifier)
  - **No** → +1 point (uncertainty modifier)

```html
<div class="form-group">
    <label>Systems documented? <span class="text-danger">*</span></label>
    <div class="form-check">
        <input type="radio" name="cpi_systems_documented" value="yes" id="documented_yes" required>
        <label for="documented_yes">Yes (0 points)</label>
    </div>
    <div class="form-check">
        <input type="radio" name="cpi_systems_documented" value="no" id="documented_no">
        <label for="documented_no">No (+1 uncertainty modifier)</label>
    </div>
</div>
```

---

### Input 3.4: Age Score (Harmonised)
- **Field Type**: Calculated field (auto-filled)
- **Field Name**: `cpi_age_score_harmonised`
- **Calculation**: Higher of building vs fixtures, plus documentation modifier
- **Display**: Read-only, shows calculated value

```html
<div class="form-group">
    <label>Age Score (harmonised)</label>
    <input type="number" name="cpi_age_score_harmonised" class="form-control" readonly 
           style="background: #f0f0f0;">
    <small class="text-muted">Higher of building vs fixtures, plus documentation modifier</small>
</div>
```

**Domain 3 Total**: Age score + documentation modifier (Max 5 points)

---

## 🎯 DOMAIN 4: Access & Containment (Max 3 points)

### Input 4.1: Containment Category
- **Field Type**: Dropdown (from `containment_categories` table)
- **Field Name**: `cpi_containment_category`
- **Scoring**: Based on lookup table
  - **Accessible isolation** → 0 points (how quickly damage can be isolated)
  - **Partial isolation** → 1 point
  - **Poor isolation** → 2 points
  - **No isolation** → 3 points

```html
<div class="form-group">
    <label>Containment category (use Lookups list) <span class="text-danger">*</span></label>
    <select name="cpi_containment_category" class="form-control" required>
        <option value="">-- Select Category --</option>
        @foreach($containmentCategories as $category)
            <option value="{{ $category->id }}" data-score="{{ $category->score_points }}">
                {{ $category->category_name }} ({{ $category->score_points }} points)
            </option>
        @endforeach
    </select>
    <small class="text-muted">How quickly damage can be isolated</small>
</div>
```

**Domain 4 Total**: Containment score (Max 3 points)

---

## 🎯 DOMAIN 5: Accessibility & Safety (MAX of sub-scores, capped at 4)

**IMPORTANT**: This domain uses **MAX** (worst-case) instead of SUM. Take the highest score from the 4 factors below, capped at 4 points maximum.

### Input 5.1: Crawl/Confined Access Category
- **Field Type**: Dropdown (from `crawl_access_categories` table)
- **Field Name**: `cpi_crawl_access`
- **Scoring**: Based on lookup table
  - **No crawl / full basement** → 0 points
  - **Crawl w/ clearance & lighting** → 1 point
  - **Low-clearance crawl (<3 ft)** → 2 points
  - **Damp / poorly ventilated crawl** → 3 points
  - **Hazardous crawl (mold/pests/structural)** → 4 points

```html
<div class="form-group">
    <label>Crawl/Confined access category (Lookups list) <span class="text-danger">*</span></label>
    <select name="cpi_crawl_access" class="form-control" required>
        <option value="">-- Select Category --</option>
        @foreach($crawlAccessCategories as $category)
            <option value="{{ $category->id }}" data-score="{{ $category->score_points }}">
                {{ $category->category_name }} ({{ $category->score_points }} points)
            </option>
        @endforeach
    </select>
</div>
```

---

### Input 5.2: Roof Access Category
- **Field Type**: Dropdown (from `roof_access_categories` table)
- **Field Name**: `cpi_roof_access`
- **Scoring**: Based on lookup table
  - **Flat/low pitch (<4:12) safe access** → 0 points
  - **Moderate pitch (4:12–7:12)** → 1 point
  - **High pitch (>7:12)** → 2 points
  - **High pitch + brittle/specialty roofing** → 3 points

```html
<div class="form-group">
    <label>Roof access category (Lookups list) <span class="text-danger">*</span></label>
    <select name="cpi_roof_access" class="form-control" required>
        <option value="">-- Select Category --</option>
        @foreach($roofAccessCategories as $category)
            <option value="{{ $category->id }}" data-score="{{ $category->score_points }}">
                {{ $category->category_name }} ({{ $category->score_points }} points)
            </option>
        @endforeach
    </select>
</div>
```

---

### Input 5.3: Equipment Requirement
- **Field Type**: Dropdown (from `equipment_requirements` table)
- **Field Name**: `cpi_equipment_requirement`
- **Scoring**: Based on lookup table
  - **Standard ladder only** → 0 points
  - **Extended ladder / roof anchors** → 1 point
  - **Scissor lift required** → 2 points
  - **Boom lift / crane / confined-space protocol** → 3 points

```html
<div class="form-group">
    <label>Equipment requirement (Lookups list) <span class="text-danger">*</span></label>
    <select name="cpi_equipment_requirement" class="form-control" required>
        <option value="">-- Select Requirement --</option>
        @foreach($equipmentRequirements as $requirement)
            <option value="{{ $requirement->id }}" data-score="{{ $requirement->score_points }}">
                {{ $requirement->requirement_name }} ({{ $requirement->score_points }} points)
            </option>
        @endforeach
    </select>
</div>
```

---

### Input 5.4: Time to Access Critical Systems (Minutes)
- **Field Type**: Number input
- **Field Name**: `cpi_time_to_access`
- **Scoring**: Time-based
  - **0-10 minutes** → 0 points
  - **>10 minutes** → Score increases

```html
<div class="form-group">
    <label>Time to access critical systems (minutes) <span class="text-danger">*</span></label>
    <input type="number" name="cpi_time_to_access" class="form-control" 
           placeholder="e.g., 30" min="0" required>
</div>
```

---

### Input 5.5: Accessibility Score (Capped)
- **Field Type**: Calculated field (auto-filled)
- **Field Name**: `cpi_accessibility_score`
- **Calculation**: MAX of the 4 sub-scores above, capped at 4
- **Display**: Read-only
- **Notes**: "Takes the worst-case access risk and caps at 4"

```html
<div class="form-group">
    <label>Accessibility Score (=capped)</label>
    <input type="number" name="cpi_accessibility_score" class="form-control" readonly 
           style="background: #f0f0f0;">
    <small class="text-muted">Takes the worst-case access risk and caps at 4</small>
</div>
```

**Domain 5 Total**: MAX of 4 sub-scores, capped at 4 points

---

## 🎯 DOMAIN 6: Operational Complexity (Max 3 points)

### Input 6.1: Complexity Category
- **Field Type**: Dropdown (from `complexity_categories` table)
- **Field Name**: `cpi_complexity_category`
- **Scoring**: Based on lookup table
  - **Low density / simple** → 0 points (tenant density, mixed-use, business interruption exposure)
  - **Medium density** → 1 point
  - **High density** → 2 points
  - **Business-critical** → 3 points

```html
<div class="form-group">
    <label>Complexity category (Lookups list) <span class="text-danger">*</span></label>
    <select name="cpi_complexity_category" class="form-control" required>
        <option value="">-- Select Category --</option>
        @foreach($complexityCategories as $category)
            <option value="{{ $category->id }}" data-score="{{ $category->score_points }}">
                {{ $category->category_name }} ({{ $category->score_points }} points)
            </option>
        @endforeach
    </select>
    <small class="text-muted">Tenant density, mixed-use, business interruption exposure</small>
</div>
```

**Domain 6 Total**: Complexity score (Max 3 points)

---

## 📊 CPI OUTPUTS (Auto-Calculated)

These fields are **calculated automatically** based on the inputs above:

### Output 1: CPI Total Score
- **Field Name**: `cpi_total_score`
- **Calculation**: Sum of all 6 domain scores
- **Display**: Prominent badge/alert showing the score
- **Range**: 0-27 (typically 0-15 in practice)

```html
<div class="alert alert-info">
    <h5>CPI Total Score: <span id="cpiTotalScore">0</span> points</h5>
</div>
```

---

### Output 2: CPI Band
- **Field Name**: `cpi_band`
- **Calculation**: Based on `cpi_band_ranges` table
  - **0-2 points** → CPI-0 (Excellent)
  - **3-5 points** → CPI-1 (Good)
  - **6-8 points** → CPI-2 (Fair)
  - **9-11 points** → CPI-3 (Poor)
  - **12+ points** → CPI-4 (Critical)

```html
<div class="alert alert-warning">
    <h5>CPI Band: <span id="cpiBand" class="badge bg-warning">CPI-0</span></h5>
</div>
```

---

### Output 3: CPI Multiplier
- **Field Name**: `cpi_multiplier`
- **Calculation**: Based on `cpi_multipliers` table linked to CPI band
  - **CPI-0** → 1.00x
  - **CPI-1** → 1.08x
  - **CPI-2** → 1.18x
  - **CPI-3** → 1.35x
  - **CPI-4** → 1.55x

```html
<div class="alert alert-danger">
    <h5>CPI Multiplier: <span id="cpiMultiplier">1.00</span>x</h5>
</div>
```

---

## 🎨 Form Layout Recommendation

```
┌─────────────────────────────────────────────────────────┐
│  PROPERTY INSPECTION - CPI SCORING FORM                 │
│  Property: PROP-2025-001 | Type: Residential            │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│  📋 Inspection Overview                                 │
│  ├─ Inspection Date & Time                              │
│  ├─ Inspector Name                                      │
│  ├─ Weather Conditions                                  │
│  └─ General Summary                                     │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│  ⚙️ DOMAIN 1: System Design & Pressure (Max 7 pts)     │
│  ├─ Unit shutoffs present? [Yes/No]                     │
│  ├─ Shared risers? [Yes/No]                             │
│  ├─ Static pressure (PSI): [_____]                      │
│  └─ Isolation zones? [Yes/No]                           │
│  DOMAIN 1 SCORE: [3] points                             │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│  🔧 DOMAIN 2: Material Risk (Max 5 pts)                 │
│  ├─ Supply material: [Dropdown]                         │
│  └─ Drain material unknown? [Yes/No]                    │
│  DOMAIN 2 SCORE: [4] points                             │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│  📅 DOMAIN 3: Age & Lifecycle (Max 5 pts)               │
│  ├─ Building age: [_____] years                         │
│  ├─ Fixture age: [_____] years                          │
│  ├─ Systems documented? [Yes/No]                        │
│  └─ Age Score (harmonised): [auto] points               │
│  DOMAIN 3 SCORE: [2] points                             │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│  🛡️ DOMAIN 4: Access & Containment (Max 3 pts)         │
│  └─ Containment category: [Dropdown]                    │
│  DOMAIN 4 SCORE: [1] point                              │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│  🪜 DOMAIN 5: Accessibility (MAX, capped at 4)          │
│  ├─ Crawl access: [Dropdown]                            │
│  ├─ Roof access: [Dropdown]                             │
│  ├─ Equipment required: [Dropdown]                      │
│  ├─ Time to access: [_____] minutes                     │
│  └─ Accessibility Score (capped): [auto] points         │
│  DOMAIN 5 SCORE: [0] points                             │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│  🏢 DOMAIN 6: Operational Complexity (Max 3 pts)        │
│  └─ Complexity category: [Dropdown]                     │
│  DOMAIN 6 SCORE: [0] points                             │
└─────────────────────────────────────────────────────────┘

┌═════════════════════════════════════════════════════════┐
║  📊 CPI OUTPUTS (AUTO-CALCULATED)                       ║
║                                                         ║
║  CPI Total Score:    10 points                          ║
║  CPI Band:          CPI-3 (Poor)                        ║
║  CPI Multiplier:    1.35x                               ║
║                                                         ║
║  PRICING IMPACT:                                        ║
║  Base Price:        $399                                ║
║  Size Factor:       1.25x                               ║
║  CPI Multiplier:    1.35x                               ║
║  ═══════════════════════════════                        ║
║  FINAL MONTHLY:     $673                                ║
║  FINAL ANNUAL:      $8,080                              ║
╚═════════════════════════════════════════════════════════╛

[Cancel]  [Save as Draft]  [Complete Inspection]
```

---

## 💾 Database Storage

All CPI inputs and outputs should be stored in the `inspections` table with these JSON columns:

```php
// inspections table columns
'cpi_inputs' => json // All input values from the form
'cpi_scores' => json // Domain scores and breakdown
'cpi_total_score' => integer // Total CPI score
'cpi_band' => string // CPI-0, CPI-1, CPI-2, CPI-3, CPI-4
'cpi_multiplier' => decimal(4,2) // 1.00, 1.08, 1.18, 1.35, 1.55
```

### Example JSON Storage:

```json
// cpi_inputs
{
    "domain_1": {
        "unit_shutoffs": "no",
        "shared_risers": "yes",
        "static_pressure": 120,
        "isolation_zones": "no"
    },
    "domain_2": {
        "supply_material_id": 5,
        "supply_material_name": "Copper",
        "drain_material_unknown": "no"
    },
    "domain_3": {
        "building_age": 35,
        "fixture_age": 8,
        "systems_documented": "yes",
        "age_score_harmonised": 2
    },
    "domain_4": {
        "containment_category_id": 2,
        "containment_category_name": "Accessible isolation"
    },
    "domain_5": {
        "crawl_access_id": 1,
        "roof_access_id": 3,
        "equipment_requirement_id": 1,
        "time_to_access": 30,
        "accessibility_score_capped": 3
    },
    "domain_6": {
        "complexity_category_id": 1,
        "complexity_category_name": "Low density / simple"
    }
}

// cpi_scores
{
    "domain_1_score": 7,
    "domain_2_score": 0,
    "domain_3_score": 2,
    "domain_4_score": 0,
    "domain_5_score": 3,
    "domain_6_score": 0,
    "total_score": 12
}
```

---

## 🔄 JavaScript Auto-Calculation

The form should include JavaScript that:

1. **Listens to all input changes**
2. **Calculates domain scores in real-time**
3. **Sums to get total CPI score**
4. **Determines CPI band from score ranges**
5. **Fetches multiplier from database/config**
6. **Updates the output badges live**

```javascript
function calculateCPIScore() {
    // Domain 1
    let domain1 = 0;
    if ($('input[name="cpi_unit_shutoffs"]:checked').val() === 'no') domain1 += 3;
    if ($('input[name="cpi_shared_risers"]:checked').val() === 'yes') domain1 += 2;
    if (parseInt($('input[name="cpi_static_pressure"]').val()) > 90) domain1 += 2;
    if ($('input[name="cpi_isolation_zones"]:checked').val() === 'no') domain1 += 2;
    
    // Domain 2
    let domain2 = parseInt($('select[name="cpi_supply_material"] option:selected').data('score')) || 0;
    
    // Domain 3
    let buildingAge = parseInt($('input[name="cpi_building_age"]').val()) || 0;
    let domain3 = 0;
    if (buildingAge >= 61) domain3 = 4;
    else if (buildingAge >= 41) domain3 = 3;
    else if (buildingAge >= 26) domain3 = 2;
    else if (buildingAge >= 11) domain3 = 1;
    else domain3 = 0;
    if ($('input[name="cpi_systems_documented"]:checked').val() === 'no') domain3 += 1;
    
    // Domain 4
    let domain4 = parseInt($('select[name="cpi_containment_category"] option:selected').data('score')) || 0;
    
    // Domain 5 - MAX of sub-scores, capped at 4
    let crawl = parseInt($('select[name="cpi_crawl_access"] option:selected').data('score')) || 0;
    let roof = parseInt($('select[name="cpi_roof_access"] option:selected').data('score')) || 0;
    let equipment = parseInt($('select[name="cpi_equipment_requirement"] option:selected').data('score')) || 0;
    let domain5 = Math.min(Math.max(crawl, roof, equipment), 4);
    
    // Domain 6
    let domain6 = parseInt($('select[name="cpi_complexity_category"] option:selected').data('score')) || 0;
    
    // Total
    let totalScore = domain1 + domain2 + domain3 + domain4 + domain5 + domain6;
    
    // Determine band
    let band = 'CPI-0';
    let multiplier = 1.00;
    if (totalScore >= 12) { band = 'CPI-4'; multiplier = 1.55; }
    else if (totalScore >= 9) { band = 'CPI-3'; multiplier = 1.35; }
    else if (totalScore >= 6) { band = 'CPI-2'; multiplier = 1.18; }
    else if (totalScore >= 3) { band = 'CPI-1'; multiplier = 1.08; }
    
    // Update display
    $('#cpiTotalScore').text(totalScore);
    $('#cpiBand').text(band);
    $('#cpiMultiplier').text(multiplier.toFixed(2));
}

// Attach to all input changes
$(document).on('change', 'input[name^="cpi_"], select[name^="cpi_"]', calculateCPIScore);
```

---

## ✅ Implementation Checklist

- [ ] Create models for all lookup tables (SupplyLineMaterial, AgeBracket, etc.)
- [ ] Seed lookup tables with data from your Excel screenshots
- [ ] Update InspectionController to pass lookup data to view
- [ ] Modify inspection form blade to include all CPI input fields
- [ ] Add JavaScript for real-time calculation
- [ ] Add validation rules for all CPI inputs (required fields)
- [ ] Store CPI data in JSON columns (cpi_inputs, cpi_scores)
- [ ] Store calculated values in dedicated columns (cpi_total_score, cpi_band, cpi_multiplier)
- [ ] Display CPI outputs prominently on inspection summary
- [ ] Use CPI multiplier in pricing calculation when creating custom products

---

## 🎯 Next Steps

1. **Update the inspection form** to include these CPI-specific inputs instead of generic property inspection categories
2. **Seed the lookup tables** with the exact values from your Excel calculator
3. **Implement the JavaScript calculation** for real-time feedback
4. **Test the calculation** matches your Excel calculator results
5. **Link CPI score to pricing** when creating custom products/subscriptions

This CPI scoring is the **foundation of your entire pricing system** - get these inputs right and the rest flows automatically!
