# PHAR INSPECTION FORM - COMPLETE SPECIFICATION

## 📋 OVERVIEW

This document defines the complete PHAR (Property Health Assessment Report) inspection form structure, including all database entities, attributes, field types, and dropdown options.

---

## 🗄️ DATABASE ENTITIES & RELATIONSHIPS

### Entity Relationship Diagram

```
properties (1) ──→ (1) inspections ──→ (1) phar_assessments
                        ↓
                        │
                        ├──→ (many) phar_findings
                        ├──→ (many) phar_photos
                        └──→ (1) cpi_calculation
```

---

## 1️⃣ ENTITY: `inspections` (Existing - Extensions)

**Purpose**: Main inspection record
**Extends existing table with CPI-related fields**

### New Fields to Add:

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `phar_completed_at` | TIMESTAMP | No | When PHAR assessment was completed |
| `phar_status` | ENUM | Yes | Values: 'draft', 'in_progress', 'completed', 'approved' |
| `phar_submitted_by` | BIGINT (FK) | Yes | Inspector who submitted PHAR |
| `phar_approved_by` | BIGINT (FK) | No | Admin/PM who approved PHAR |
| `phar_approved_at` | TIMESTAMP | No | When PHAR was approved |

---

## 2️⃣ ENTITY: `phar_assessments`

**Purpose**: Stores all CPI domain data collected during PHAR inspection

### Table Structure:

```sql
CREATE TABLE phar_assessments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    inspection_id BIGINT UNSIGNED NOT NULL UNIQUE,
    property_id BIGINT UNSIGNED NOT NULL,
    
    -- === DOMAIN 1: SYSTEM DESIGN & PRESSURE ===
    unit_level_shutoffs ENUM('yes', 'no', 'partial', 'unknown') NOT NULL,
    unit_level_shutoffs_notes TEXT,
    
    shared_risers ENUM('yes', 'no', 'unknown') NOT NULL,
    shared_risers_count INT,
    shared_risers_notes TEXT,
    
    water_pressure_psi INT,
    water_pressure_location VARCHAR(255),
    water_pressure_notes TEXT,
    
    isolation_zones_present ENUM('yes', 'no', 'unknown') NOT NULL,
    isolation_zones_count INT,
    isolation_zones_notes TEXT,
    
    -- === DOMAIN 2: MATERIAL RISK ===
    supply_line_material ENUM('copper', 'pex', 'cpvc', 'galvanized', 'poly-b', 'mixed', 'unknown') NOT NULL,
    supply_line_material_notes TEXT,
    supply_line_condition ENUM('excellent', 'good', 'fair', 'poor', 'critical'),
    
    drain_waste_material VARCHAR(255),
    drain_waste_unknown BOOLEAN DEFAULT FALSE,
    drain_waste_notes TEXT,
    
    -- === DOMAIN 3: AGE & LIFECYCLE ===
    building_age_years INT,
    building_construction_year INT,
    building_age_notes TEXT,
    
    fixture_age_years INT,
    fixture_last_replacement_year INT,
    fixture_age_notes TEXT,
    
    systems_documented ENUM('yes', 'no', 'partial') NOT NULL,
    documentation_quality ENUM('excellent', 'good', 'fair', 'poor', 'none'),
    systems_documentation_notes TEXT,
    
    -- === DOMAIN 4: ACCESS & CONTAINMENT ===
    containment_category ENUM('accessible_isolation', 'partial_isolation', 'poor_isolation', 'no_isolation') NOT NULL,
    containment_notes TEXT,
    emergency_shutoff_locations TEXT,
    
    -- === DOMAIN 5: ACCESSIBILITY & SAFETY ===
    crawl_access_category ENUM('no_crawl_full_basement', 'crawl_with_clearance', 'low_clearance_crawl', 'damp_crawl', 'hazardous_crawl', 'not_applicable') NOT NULL,
    crawl_access_notes TEXT,
    crawl_height_inches INT,
    
    roof_access_category ENUM('flat_low_pitch', 'moderate_pitch', 'high_pitch', 'high_pitch_brittle', 'not_applicable') NOT NULL,
    roof_access_notes TEXT,
    roof_pitch_ratio VARCHAR(20),
    
    equipment_requirement ENUM('standard_ladder', 'extended_ladder_anchors', 'scissor_lift', 'boom_lift_crane', 'confined_space_protocol') NOT NULL,
    equipment_notes TEXT,
    
    access_time_minutes INT NOT NULL,
    access_time_notes TEXT,
    
    -- === DOMAIN 6: OPERATIONAL COMPLEXITY ===
    operational_complexity ENUM('low_density_simple', 'medium_density', 'high_density', 'business_critical') NOT NULL,
    tenant_count INT,
    business_type VARCHAR(255),
    operational_hours VARCHAR(100),
    complexity_notes TEXT,
    
    -- === SAFETY & ACCESS SUMMARY ===
    ppe_requirements JSON,
    safety_concerns TEXT,
    access_restrictions TEXT,
    
    -- === INSPECTOR NOTES ===
    general_observations TEXT,
    recommendations TEXT,
    urgent_items TEXT,
    
    -- === METADATA ===
    inspection_duration_minutes INT,
    weather_conditions VARCHAR(255),
    site_contact_name VARCHAR(255),
    site_contact_phone VARCHAR(50),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    INDEX idx_property (property_id),
    INDEX idx_inspection (inspection_id)
);
```

---

## 3️⃣ ENTITY: `phar_findings`

**Purpose**: Individual issues/findings discovered during PHAR

### Table Structure:

```sql
CREATE TABLE phar_findings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    inspection_id BIGINT UNSIGNED NOT NULL,
    phar_assessment_id BIGINT UNSIGNED NOT NULL,
    property_id BIGINT UNSIGNED NOT NULL,
    
    -- Finding Details
    system_category ENUM(
        'plumbing_supply',
        'plumbing_waste',
        'electrical',
        'hvac',
        'structural',
        'roofing',
        'exterior',
        'interior',
        'safety',
        'accessibility',
        'other'
    ) NOT NULL,
    
    location VARCHAR(255) NOT NULL,
    specific_spot VARCHAR(255),
    
    issue_description TEXT NOT NULL,
    severity ENUM('critical', 'high', 'medium', 'low', 'informational') NOT NULL,
    
    -- Recommendations (Multiple Options)
    recommendation_option_1 TEXT,
    recommendation_option_1_cost_estimate DECIMAL(10,2),
    
    recommendation_option_2 TEXT,
    recommendation_option_2_cost_estimate DECIMAL(10,2),
    
    recommendation_option_3 TEXT,
    recommendation_option_3_cost_estimate DECIMAL(10,2),
    
    recommended_option INT DEFAULT 1,
    
    -- Risk & Impact
    risk_if_deferred TEXT,
    estimated_repair_urgency ENUM('immediate', 'within_week', 'within_month', 'within_quarter', 'annual', 'future'),
    
    -- Work Classification
    included_in_care_package BOOLEAN DEFAULT FALSE,
    requires_approval BOOLEAN DEFAULT FALSE,
    approval_threshold ENUM('pm', 'owner', 'board') DEFAULT 'pm',
    
    -- Status
    status ENUM('open', 'in_progress', 'resolved', 'deferred', 'no_action') DEFAULT 'open',
    resolution_notes TEXT,
    resolved_at TIMESTAMP,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE CASCADE,
    FOREIGN KEY (phar_assessment_id) REFERENCES phar_assessments(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    INDEX idx_severity (severity),
    INDEX idx_status (status),
    INDEX idx_system (system_category)
);
```

---

## 4️⃣ ENTITY: `phar_photos`

**Purpose**: Photo evidence for PHAR assessment and findings

### Table Structure:

```sql
CREATE TABLE phar_photos (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    inspection_id BIGINT UNSIGNED NOT NULL,
    phar_assessment_id BIGINT UNSIGNED,
    phar_finding_id BIGINT UNSIGNED,
    
    photo_path VARCHAR(500) NOT NULL,
    photo_type ENUM('general', 'issue', 'system', 'measurement', 'before', 'after') NOT NULL,
    
    caption TEXT,
    location VARCHAR(255),
    
    cpi_domain VARCHAR(50),
    system_category VARCHAR(50),
    
    taken_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE CASCADE,
    FOREIGN KEY (phar_assessment_id) REFERENCES phar_assessments(id) ON DELETE SET NULL,
    FOREIGN KEY (phar_finding_id) REFERENCES phar_findings(id) ON DELETE SET NULL,
    INDEX idx_type (photo_type),
    INDEX idx_inspection (inspection_id)
);
```

---

## 5️⃣ ENTITY: `cpi_calculations`

**Purpose**: Stores calculated CPI scores and breakdown

### Table Structure:

```sql
CREATE TABLE cpi_calculations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    inspection_id BIGINT UNSIGNED NOT NULL UNIQUE,
    phar_assessment_id BIGINT UNSIGNED NOT NULL UNIQUE,
    property_id BIGINT UNSIGNED NOT NULL,
    
    -- Domain Scores
    domain_1_system_design_score INT DEFAULT 0,
    domain_2_material_risk_score INT DEFAULT 0,
    domain_3_age_lifecycle_score INT DEFAULT 0,
    domain_4_containment_score INT DEFAULT 0,
    domain_5_accessibility_score INT DEFAULT 0,
    domain_6_complexity_score INT DEFAULT 0,
    
    -- Totals
    total_cpi_score INT NOT NULL,
    cpi_band ENUM('CPI-0', 'CPI-1', 'CPI-2', 'CPI-3', 'CPI-4') NOT NULL,
    cpi_multiplier DECIMAL(4,2) NOT NULL,
    
    -- Score Breakdown Details (JSON)
    score_breakdown JSON,
    risk_drivers JSON,
    
    -- Price Impact
    base_price_monthly DECIMAL(10,2),
    size_factor DECIMAL(4,2),
    final_price_monthly DECIMAL(10,2),
    
    -- Explainability
    top_risk_factors TEXT,
    improvement_opportunities TEXT,
    
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    recalculated_at TIMESTAMP,
    
    FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE CASCADE,
    FOREIGN KEY (phar_assessment_id) REFERENCES phar_assessments(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    INDEX idx_cpi_band (cpi_band),
    INDEX idx_total_score (total_cpi_score)
);
```

---

## 📝 INSPECTION FORM STRUCTURE

### Section 1: Pre-Inspection Information

| Field | Input Type | Options/Validation |
|-------|-----------|-------------------|
| **Property Details** | Read-only | Auto-populated from property record |
| **Inspection Date** | Date picker | Required |
| **Inspector Name** | Read-only | Auto-populated from auth user |
| **Weather Conditions** | Text input | Optional |
| **Site Contact Name** | Text input | Required |
| **Site Contact Phone** | Phone input | Required |
| **Inspection Start Time** | Time picker | Auto-captured |

---

### Section 2: Domain 1 - System Design & Pressure

#### 2.1 Unit-Level Water Shut-offs
| Field | Input Type | Options | Score Impact |
|-------|-----------|---------|--------------|
| **Shut-offs Present** | Radio buttons | • Yes<br>• No<br>• Partial<br>• Unknown | No = +3 pts |
| **Notes** | Textarea | Free text | - |
| **Photo** | File upload | Multiple images | - |

#### 2.2 Shared Risers
| Field | Input Type | Options | Score Impact |
|-------|-----------|---------|--------------|
| **Shared Risers** | Radio buttons | • Yes<br>• No<br>• Unknown | Yes = +2 pts |
| **Number of Units Impacted** | Number input | Min: 0 | - |
| **Notes** | Textarea | Free text | - |
| **Photo** | File upload | Multiple images | - |

#### 2.3 Water Pressure
| Field | Input Type | Options | Score Impact |
|-------|-----------|---------|--------------|
| **Static Pressure (PSI)** | Number input | Min: 0, Max: 200 | >80 = +2 pts |
| **Measurement Location** | Text input | - | - |
| **Notes** | Textarea | Free text | - |
| **Photo** | File upload | Pressure gauge reading | - |

#### 2.4 Isolation Zones
| Field | Input Type | Options | Score Impact |
|-------|-----------|---------|--------------|
| **Isolation Zones Present** | Radio buttons | • Yes<br>• No<br>• Unknown | No = +2 pts |
| **Number of Zones** | Number input | Min: 0 | - |
| **Notes** | Textarea | Free text | - |
| **Photo** | File upload | Valve locations | - |

**Domain 1 Calculated Score**: Auto-calculated (0-7 points)

---

### Section 3: Domain 2 - Material Risk

#### 3.1 Supply Line Material
| Field | Input Type | Options | Score Impact |
|-------|-----------|---------|--------------|
| **Primary Material** | Dropdown | • Copper (0 pts)<br>• PEX (+1 pt)<br>• CPVC (+2 pts)<br>• Galvanized (+3 pts)<br>• Poly-B (+4 pts)<br>• Mixed/Unknown (+2 pts) | As shown |
| **Condition** | Radio buttons | • Excellent<br>• Good<br>• Fair<br>• Poor<br>• Critical | For notes only |
| **Notes** | Textarea | Free text | - |
| **Photos** | File upload | Pipe examples, labels | - |

#### 3.2 Drain/Waste System
| Field | Input Type | Options | Score Impact |
|-------|-----------|---------|--------------|
| **Material Known** | Radio buttons | • Yes<br>• No | No = +1 pt |
| **Material Type** | Text input | If known | - |
| **Notes** | Textarea | Free text | - |
| **Photos** | File upload | Drain system | - |

**Domain 2 Calculated Score**: Auto-calculated (0-5 points)

---

### Section 4: Domain 3 - Age & Lifecycle

#### 4.1 Building Age
| Field | Input Type | Options | Score Impact |
|-------|-----------|---------|--------------|
| **Construction Year** | Number input | YYYY format | Auto-calculate age |
| **Building Age (Years)** | Auto-calculated | - | 0-10 yrs = 0<br>11-25 = +1<br>26-40 = +2<br>41-60 = +3<br>61+ = +4 |
| **Notes** | Textarea | Renovations, additions | - |

#### 4.2 Fixture/System Age
| Field | Input Type | Options | Score Impact |
|-------|-----------|---------|--------------|
| **Last Replacement Year** | Number input | YYYY format | Auto-calculate age |
| **System Age (Years)** | Auto-calculated | - | Same brackets as building |
| **Key Systems** | Checkboxes | • Water heater<br>• Valves<br>• Pumps<br>• Fixtures<br>• Other | - |
| **Notes** | Textarea | Replacement history | - |

#### 4.3 Documentation
| Field | Input Type | Options | Score Impact |
|-------|-----------|---------|--------------|
| **Systems Documented** | Radio buttons | • Yes<br>• Partial<br>• No | No = +1 pt |
| **Documentation Quality** | Dropdown | • Excellent<br>• Good<br>• Fair<br>• Poor<br>• None | For notes only |
| **Notes** | Textarea | What exists | - |

**Domain 3 Calculated Score**: Auto-calculated (0-5 points)
*Uses HIGHER of building age or fixture age, plus documentation modifier*

---

### Section 5: Domain 4 - Access & Containment

#### 5.1 Containment Category
| Field | Input Type | Options | Score Impact |
|-------|-----------|---------|--------------|
| **Containment Level** | Dropdown | • Accessible isolation (0 pts)<br>• Partial isolation (+1 pt)<br>• Poor isolation (+2 pts)<br>• No isolation (+3 pts) | As shown |
| **Emergency Shutoff Locations** | Textarea | Describe locations | - |
| **Notes** | Textarea | Access challenges | - |
| **Photos** | File upload | Shutoff locations | - |

**Domain 4 Calculated Score**: 0-3 points

---

### Section 6: Domain 5 - Accessibility & Safety

#### 6.1 Crawl Space Access
| Field | Input Type | Options | Score Impact |
|-------|-----------|---------|--------------|
| **Crawl Access Type** | Dropdown | • No crawl / full basement (0 pts)<br>• Crawl with clearance (+1 pt)<br>• Low clearance < 3 ft (+2 pts)<br>• Damp / poorly ventilated (+3 pts)<br>• Hazardous (mold/pests) (+4 pts)<br>• Not applicable (0 pts) | As shown |
| **Height (inches)** | Number input | If applicable | - |
| **Notes** | Textarea | Conditions observed | - |
| **Photos** | File upload | Crawl space conditions | - |

#### 6.2 Roof Access
| Field | Input Type | Options | Score Impact |
|-------|-----------|---------|--------------|
| **Roof Access Type** | Dropdown | • Flat / low pitch <4:12 (0 pts)<br>• Moderate pitch 4:12-7:12 (+1 pt)<br>• High pitch >7:12 (+2 pts)<br>• High pitch + brittle roofing (+3 pts)<br>• Not applicable (0 pts) | As shown |
| **Pitch Ratio** | Text input | e.g., "6:12" | - |
| **Notes** | Textarea | Roof condition, safety | - |
| **Photos** | File upload | Roof access points | - |

#### 6.3 Equipment Requirements
| Field | Input Type | Options | Score Impact |
|-------|-----------|---------|--------------|
| **Equipment Needed** | Dropdown | • Standard ladder only (0 pts)<br>• Extended ladder / roof anchors (+1 pt)<br>• Scissor lift required (+2 pts)<br>• Boom lift / crane / confined space (+3 pts) | As shown |
| **Notes** | Textarea | Special requirements | - |

#### 6.4 Access Time
| Field | Input Type | Options | Score Impact |
|-------|-----------|---------|--------------|
| **Time to Critical Systems (minutes)** | Number input | Minutes | ≤10 = 0<br>11-30 = +1<br>31-60 = +2<br>>60 = +3 |
| **Notes** | Textarea | Access challenges | - |

#### 6.5 PPE & Safety Requirements
| Field | Input Type | Options |
|-------|-----------|---------|
| **PPE Required** | Checkboxes | • Hard hat<br>• Safety harness<br>• Respirator<br>• Confined space gear<br>• Eye protection<br>• Gloves<br>• Steel-toed boots |
| **Safety Concerns** | Textarea | Hazards identified |
| **Access Restrictions** | Textarea | Timing, permissions |

**Domain 5 Calculated Score**: Auto-calculated (0-4 points, capped)
*Takes WORST score from crawl, roof, equipment, and access time*

---

### Section 7: Domain 6 - Operational Complexity

#### 7.1 Complexity Assessment
| Field | Input Type | Options | Score Impact |
|-------|-----------|---------|--------------|
| **Complexity Level** | Dropdown | • Low density / simple (0 pts)<br>• Medium density (+1 pt)<br>• High density (+2 pts)<br>• Business-critical (+3 pts) | As shown |
| **Tenant Count** | Number input | Total tenants/businesses | - |
| **Business Type(s)** | Text input | If commercial | - |
| **Operating Hours** | Text input | When occupied | - |
| **Notes** | Textarea | Complexity factors | - |

**Domain 6 Calculated Score**: 0-3 points

---

### Section 8: Issues & Findings

**Add Finding Button** - Opens modal/form for each issue discovered

#### Finding Entry Form:
| Field | Input Type | Options |
|-------|-----------|---------|
| **System Category** | Dropdown | • Plumbing - Supply<br>• Plumbing - Waste<br>• Electrical<br>• HVAC<br>• Structural<br>• Roofing<br>• Exterior<br>• Interior<br>• Safety<br>• Accessibility<br>• Other |
| **Location** | Text input | Building/unit location |
| **Specific Spot** | Text input | Exact location |
| **Issue Description** | Textarea | Detailed description |
| **Severity** | Radio buttons | • Critical<br>• High<br>• Medium<br>• Low<br>• Informational |
| **Photos** | File upload | Multiple images |
| **Recommendation Option 1** | Textarea | Solution 1 |
| **Option 1 Est. Cost** | Currency input | Dollar amount |
| **Recommendation Option 2** | Textarea | Solution 2 (optional) |
| **Option 2 Est. Cost** | Currency input | Dollar amount |
| **Recommendation Option 3** | Textarea | Solution 3 (optional) |
| **Option 3 Est. Cost** | Currency input | Dollar amount |
| **Recommended Option** | Radio buttons | 1, 2, or 3 |
| **Risk if Deferred** | Textarea | Consequences |
| **Urgency** | Dropdown | • Immediate<br>• Within week<br>• Within month<br>• Within quarter<br>• Annual<br>• Future |
| **Included in Care Package** | Checkbox | Auto-determined based on scope |
| **Requires Approval** | Checkbox | Auto-determined based on cost |
| **Approval Level** | Auto-set | PM / Owner / Board |

---

### Section 9: Inspector Summary

| Field | Input Type | Notes |
|-------|-----------|-------|
| **General Observations** | Textarea | Overall property condition |
| **Key Recommendations** | Textarea | Priority actions |
| **Urgent Items** | Textarea | Immediate attention needed |
| **Inspection Duration** | Auto-calculated | Start to submit time |

---

### Section 10: CPI Score Summary (Auto-Generated)

**Real-time Score Display:**

```
┌────────────────────────────────────────────┐
│  CPI SCORE BREAKDOWN                       │
├────────────────────────────────────────────┤
│  Domain 1: System Design         [3 pts]  │
│  Domain 2: Material Risk          [4 pts]  │
│  Domain 3: Age & Lifecycle        [2 pts]  │
│  Domain 4: Containment            [1 pt ]  │
│  Domain 5: Accessibility          [2 pts]  │
│  Domain 6: Complexity             [1 pt ]  │
├────────────────────────────────────────────┤
│  TOTAL CPI SCORE:                13 pts    │
│  CPI BAND:                       CPI-4     │
│  RISK MULTIPLIER:                1.55x     │
└────────────────────────────────────────────┘

Top Risk Factors:
• Poly-B supply lines (4 points)
• No unit-level shutoffs (3 points)
• Building age 45 years (2 points)
```

---

## 🔄 FORM WORKFLOW

### Inspector Flow:
1. **Start Inspection** → Status: 'in_progress'
2. **Complete all 6 domains** → Real-time CPI calculation
3. **Add findings** → Link photos to issues
4. **Review summary** → CPI score preview
5. **Submit PHAR** → Status: 'completed'

### Validation Rules:
- All domain questions must be answered
- At least 1 photo per domain required
- Findings with "Critical" severity require immediate action plan
- Cannot submit if required fields incomplete

### Auto-Save:
- Save draft every 5 minutes
- Save on section completion
- Resume from last saved state

---

## 📊 OUTPUT DOCUMENTS

After PHAR submission, system generates:

1. **CPI Calculation Report** (PDF)
2. **Findings Summary** (PDF)
3. **Photo Evidence Package** (ZIP)
4. **Care Package Pricing Options** (Client-facing)
5. **Board-Ready Report** (Executive summary)

---

## 🎨 UI/UX NOTES

### Mobile-Friendly:
- Large touch targets for dropdowns/radios
- Photo upload with camera access
- Offline capability with sync
- GPS tagging for photos

### Inspector Experience:
- Progress bar showing completion %
- Section-by-section navigation
- Quick-add finding button always visible
- Real-time CPI score updates
- Photo annotation tools

### Validation Feedback:
- Inline errors on blur
- Required field indicators
- Score impact preview on hover
- "What does this mean?" tooltips

---

## 🔐 DATA VALIDATION & BUSINESS RULES

### Field Validation:
- PSI: 0-200 range
- Ages: 0-200 years
- Access time: 0-999 minutes
- Phone: Format validation
- Costs: Max $999,999.99

### Business Rules:
1. If Poly-B detected → Flag for urgent review
2. If CPI-4 → Require management approval before presenting to client
3. If critical findings → Send alert to PM immediately
4. If >10 findings → Suggest breaking into multiple work orders
5. If access time >60 min → Flag for emergency response planning

---

## ✅ NEXT STEPS FOR IMPLEMENTATION

1. Create database migrations for all 5 entities
2. Build PricingEngine service class for CPI calculation
3. Create Livewire components for multi-step form
4. Implement photo upload with compression
5. Build PDF report generators
6. Create care package presentation page
7. Integrate with notification system

---

**Document Version**: 1.0
**Last Updated**: January 23, 2026
**Status**: Ready for Implementation
