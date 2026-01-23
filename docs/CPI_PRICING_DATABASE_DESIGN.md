# CPI Pricing System - Complete Database Design

## Overview
This document outlines all database tables needed for a fully editable, data-driven pricing system. Admins can modify any scoring rule, range, or multiplier without touching code.

---

## Table Categories

### A. PACKAGE & PROPERTY MANAGEMENT (3 tables)
### B. CPI BAND SYSTEM (2 tables)
### C. CPI DOMAINS & FACTORS (2 tables)
### D. LOOKUP TABLES - Individual per Category (7 tables)
### E. SIZE FACTOR TABLES (2 tables)
### F. SYSTEM CONFIGURATION (1 table)

**Total: 17 Tables**

---

# A. PACKAGE & PROPERTY MANAGEMENT

## 1. `pricing_packages`
**Purpose:** Base monthly prices for each service package

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| package_name | varchar(50) | Essentials, Premium, White-Glove |
| residential_base_price | decimal(10,2) | Base price for residential properties |
| commercial_base_price | decimal(10,2) | Base price for commercial properties |
| description | text | Package features description |
| is_active | boolean | Enable/disable package |
| sort_order | integer | Display order |
| created_at | timestamp | |
| updated_at | timestamp | |

**Sample Data:**
```
| package_name | residential_base_price | commercial_base_price |
|--------------|------------------------|----------------------|
| Essentials   | 350.00                 | 650.00               |
| Premium      | 650.00                 | 1200.00              |
| White-Glove  | 1100.00                | 2000.00              |
```

---

## 2. `property_types`
**Purpose:** Define available property types (3 types)

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| type_code | varchar(20) | residential, commercial, mixed_use |
| type_name | varchar(50) | Display name |
| uses_unit_count | boolean | TRUE for residential/mixed |
| uses_square_footage | boolean | TRUE for commercial/mixed |
| is_active | boolean | |
| created_at | timestamp | |
| updated_at | timestamp | |

**Sample Data:**
```
| type_code    | type_name    | uses_unit_count | uses_square_footage |
|--------------|--------------|-----------------|---------------------|
| residential  | Residential  | TRUE            | FALSE               |
| commercial   | Commercial   | FALSE           | TRUE                |
| mixed_use    | Mixed-Use    | TRUE            | TRUE                |
```

---

## 3. `mixed_use_calculation_settings`
**Purpose:** Settings for mixed-use property calculations

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| setting_name | varchar(100) | default_commercial_weight |
| setting_value | decimal(5,2) | 50.00 (percentage) |
| description | text | How mixed-use blending works |
| updated_at | timestamp | |

**Mixed-Use Formula:**
```
Base Price = (Residential Base × (100 - Commercial%)/100) + (Commercial Base × Commercial%/100)
Size Factor = (Residential Factor × (100 - Commercial%)/100) + (Commercial Factor × Commercial%/100)
```

---

# B. CPI BAND SYSTEM

## 4. `cpi_band_ranges`
**Purpose:** Define score ranges for each CPI band (EDITABLE!)

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| band_code | varchar(10) | CPI-0, CPI-1, CPI-2, CPI-3, CPI-4 |
| band_name | varchar(50) | Display name (Excellent, Good, Fair, etc.) |
| min_score | integer | Minimum total CPI score (0, 3, 6, 9, 12) |
| max_score | integer | Maximum score (2, 5, 8, 11, NULL=unlimited) |
| sort_order | integer | 0, 1, 2, 3, 4 |
| is_active | boolean | |
| created_at | timestamp | |
| updated_at | timestamp | |

**Sample Data:**
```
| band_code | band_name         | min_score | max_score | sort_order |
|-----------|-------------------|-----------|-----------|------------|
| CPI-0     | Excellent         | 0         | 2         | 0          |
| CPI-1     | Good              | 3         | 5         | 1          |
| CPI-2     | Fair              | 6         | 8         | 2          |
| CPI-3     | Poor              | 9         | 11        | 3          |
| CPI-4     | Critical          | 12        | NULL      | 4          |
```

---

## 5. `cpi_multipliers`
**Purpose:** Price multipliers for each CPI band (EDITABLE!)

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| band_code | varchar(10) | Foreign key to cpi_band_ranges |
| multiplier | decimal(4,2) | 1.00, 1.08, 1.18, 1.35, 1.55 |
| description | text | Impact description |
| is_active | boolean | |
| created_at | timestamp | |
| updated_at | timestamp | |

**Sample Data:**
```
| band_code | multiplier | description |
|-----------|------------|-------------|
| CPI-0     | 1.00       | Base price - excellent condition |
| CPI-1     | 1.08       | +8% - minor maintenance needs |
| CPI-2     | 1.18       | +18% - moderate maintenance needs |
| CPI-3     | 1.35       | +35% - significant maintenance needs |
| CPI-4     | 1.55       | +55% - critical maintenance needs |
```

---

# C. CPI DOMAINS & FACTORS

## 6. `cpi_domains`
**Purpose:** The 6 main inspection domains

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| domain_number | integer | 1, 2, 3, 4, 5, 6 |
| domain_name | varchar(100) | System Design & Pressure, Material Risk, etc. |
| domain_code | varchar(50) | system_design, materials, age, containment, accessibility, complexity |
| max_possible_points | integer | 7, 5, 5, 3, 4, 3 |
| description | text | Domain explanation |
| calculation_method | varchar(50) | sum, max, formula |
| is_active | boolean | |
| sort_order | integer | |
| created_at | timestamp | |
| updated_at | timestamp | |

**Sample Data:**
```
| domain_number | domain_name                    | domain_code   | max_points | calculation_method |
|---------------|--------------------------------|---------------|------------|--------------------|
| 1             | System Design & Pressure       | system_design | 7          | sum                |
| 2             | Material Risk (Supply Lines)   | materials     | 5          | lookup             |
| 3             | Age & Lifecycle                | age           | 5          | formula            |
| 4             | Access & Containment           | containment   | 3          | lookup             |
| 5             | Accessibility & Safety         | accessibility | 4          | max                |
| 6             | Operational Complexity         | complexity    | 3          | lookup             |
```

---

## 7. `cpi_scoring_factors`
**Purpose:** Individual questions/factors within each domain

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| domain_id | bigint | Foreign key to cpi_domains |
| factor_code | varchar(50) | unit_shutoffs, supply_material, building_age, etc. |
| factor_label | varchar(200) | Question text for inspector |
| field_type | varchar(30) | yes_no, dropdown, numeric, calculated |
| lookup_table | varchar(50) | supply_line_materials, age_brackets, etc. |
| max_points | integer | Maximum points this factor can contribute |
| calculation_rule | text | Formula or logic (JSON or SQL expression) |
| is_required | boolean | |
| is_active | boolean | |
| sort_order | integer | |
| help_text | text | Inspector guidance |
| created_at | timestamp | |
| updated_at | timestamp | |

**Sample Data:**
```
| domain_id | factor_code          | factor_label                                    | field_type | lookup_table            | max_points |
|-----------|----------------------|-------------------------------------------------|------------|-------------------------|------------|
| 1         | unit_shutoffs        | Unit-level water shut-offs present?             | yes_no     | NULL                    | 3          |
| 1         | shared_risers        | Shared risers impacting multiple units?         | yes_no     | NULL                    | 2          |
| 1         | static_pressure      | Static water pressure (PSI)                     | numeric    | NULL                    | 2          |
| 2         | supply_material      | Primary supply-line material                    | dropdown   | supply_line_materials   | 4          |
| 3         | building_age         | Building age (years)                            | numeric    | age_brackets            | 4          |
| 4         | containment_category | Containment category                            | dropdown   | containment_categories  | 3          |
| 5         | crawl_access         | Crawl/Confined access category                  | dropdown   | crawl_access_categories | 4          |
| 5         | roof_access          | Roof access category                            | dropdown   | roof_access_categories  | 3          |
| 5         | equipment_required   | Equipment requirement                           | dropdown   | equipment_requirements  | 3          |
| 6         | complexity_category  | Operational complexity category                 | dropdown   | complexity_categories   | 3          |
```

---

# D. LOOKUP TABLES (Individual per Category)

## 8. `supply_line_materials`
**Purpose:** Supply line material types and their risk scores (EDITABLE!)

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| material_code | varchar(50) | copper, pex, cpvc, galvanized, poly_b, mixed |
| material_name | varchar(100) | Display name |
| score_points | integer | 0, 1, 2, 3, 4 |
| risk_level | varchar(20) | Low, Medium, High, Critical |
| description | text | Material characteristics |
| is_active | boolean | |
| sort_order | integer | |
| created_at | timestamp | |
| updated_at | timestamp | |

**Sample Data:**
```
| material_code | material_name  | score_points | risk_level |
|---------------|----------------|--------------|------------|
| copper        | Copper         | 0            | Low        |
| pex           | PEX            | 1            | Low        |
| cpvc          | CPVC           | 2            | Medium     |
| galvanized    | Galvanized     | 3            | High       |
| poly_b        | Poly-B         | 4            | Critical   |
| mixed         | Mixed/Unknown  | 2            | Medium     |
```

---

## 9. `age_brackets`
**Purpose:** Age ranges and corresponding scores (EDITABLE!)

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| bracket_name | varchar(50) | 0-10 years, 11-25 years, etc. |
| min_age | integer | 0, 11, 26, 41, 61 |
| max_age | integer | 10, 25, 40, 60, NULL |
| score_points | integer | 0, 1, 2, 3, 4 |
| description | text | |
| is_active | boolean | |
| sort_order | integer | |
| created_at | timestamp | |
| updated_at | timestamp | |

**Sample Data:**
```
| bracket_name  | min_age | max_age | score_points |
|---------------|---------|---------|--------------|
| 0-10 years    | 0       | 10      | 0            |
| 11-25 years   | 11      | 25      | 1            |
| 26-40 years   | 26      | 40      | 2            |
| 41-60 years   | 41      | 60      | 3            |
| 61+ years     | 61      | NULL    | 4            |
```

---

## 10. `containment_categories`
**Purpose:** Leak containment/isolation capabilities (EDITABLE!)

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| category_code | varchar(50) | accessible_isolation, partial_isolation, etc. |
| category_name | varchar(100) | Display name |
| score_points | integer | 0, 1, 2, 3 |
| description | text | What this means |
| is_active | boolean | |
| sort_order | integer | |
| created_at | timestamp | |
| updated_at | timestamp | |

**Sample Data:**
```
| category_code         | category_name         | score_points |
|-----------------------|-----------------------|--------------|
| accessible_isolation  | Accessible isolation  | 0            |
| partial_isolation     | Partial isolation     | 1            |
| poor_isolation        | Poor isolation        | 2            |
| no_isolation          | No isolation          | 3            |
```

---

## 11. `crawl_access_categories`
**Purpose:** Crawl space/confined access difficulty (EDITABLE!)

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| category_code | varchar(50) | full_basement, crawl_clearance, etc. |
| category_name | varchar(100) | Display name |
| score_points | integer | 0, 1, 2, 3, 4 |
| description | text | |
| is_active | boolean | |
| sort_order | integer | |
| created_at | timestamp | |
| updated_at | timestamp | |

**Sample Data:**
```
| category_code            | category_name                           | score_points |
|--------------------------|-----------------------------------------|--------------|
| full_basement            | No crawl / full basement                | 0            |
| crawl_clearance          | Crawl w/ clearance & lighting           | 1            |
| low_clearance            | Low-clearance crawl (<3 ft)             | 2            |
| damp_crawl               | Damp / poorly ventilated crawl          | 3            |
| hazardous_crawl          | Hazardous crawl (mold/pests/structural) | 4            |
```

---

## 12. `roof_access_categories`
**Purpose:** Roof access difficulty (EDITABLE!)

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| category_code | varchar(50) | flat_low_pitch, moderate_pitch, etc. |
| category_name | varchar(100) | Display name |
| score_points | integer | 0, 1, 2, 3 |
| description | text | |
| is_active | boolean | |
| sort_order | integer | |
| created_at | timestamp | |
| updated_at | timestamp | |

**Sample Data:**
```
| category_code     | category_name                              | score_points |
|-------------------|--------------------------------------------|--------------|
| flat_low_pitch    | Flat/low pitch (<4:12) safe access         | 0            |
| moderate_pitch    | Moderate pitch (4:12–7:12)                 | 1            |
| high_pitch        | High pitch (>7:12)                         | 2            |
| high_specialty    | High pitch + brittle/specialty roofing     | 3            |
```

---

## 13. `equipment_requirements`
**Purpose:** Special equipment needed for access (EDITABLE!)

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| requirement_code | varchar(50) | standard_ladder, extended_ladder, etc. |
| requirement_name | varchar(100) | Display name |
| score_points | integer | 0, 1, 2, 3 |
| description | text | |
| is_active | boolean | |
| sort_order | integer | |
| created_at | timestamp | |
| updated_at | timestamp | |

**Sample Data:**
```
| requirement_code      | requirement_name                          | score_points |
|-----------------------|-------------------------------------------|--------------|
| standard_ladder       | Standard ladder only                      | 0            |
| extended_ladder       | Extended ladder / roof anchors            | 1            |
| scissor_lift          | Scissor lift required                     | 2            |
| boom_lift             | Boom lift / crane / confined-space protocol | 3          |
```

---

## 14. `complexity_categories`
**Purpose:** Operational complexity levels (EDITABLE!)

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| category_code | varchar(50) | low_density, medium_density, etc. |
| category_name | varchar(100) | Display name |
| score_points | integer | 0, 1, 2, 3 |
| description | text | |
| is_active | boolean | |
| sort_order | integer | |
| created_at | timestamp | |
| updated_at | timestamp | |

**Sample Data:**
```
| category_code    | category_name         | score_points |
|------------------|-----------------------|--------------|
| low_density      | Low density / simple  | 0            |
| medium_density   | Medium density        | 1            |
| high_density     | High density          | 2            |
| business_critical| Business-critical     | 3            |
```

---

# E. SIZE FACTOR TABLES

## 15. `residential_size_tiers`
**Purpose:** Unit count → Size factor mapping (EDITABLE!)

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| tier_name | varchar(50) | 1-5 units, 6-20 units, etc. |
| min_units | integer | 1, 6, 21, 51 |
| max_units | integer | 5, 20, 50, NULL |
| size_factor | decimal(4,2) | 1.00, 1.25, 1.50, 1.75 |
| description | text | |
| is_active | boolean | |
| sort_order | integer | |
| created_at | timestamp | |
| updated_at | timestamp | |

**Sample Data:**
```
| tier_name    | min_units | max_units | size_factor |
|--------------|-----------|-----------|-------------|
| 1-5 units    | 1         | 5         | 1.00        |
| 6-20 units   | 6         | 20        | 1.25        |
| 21-50 units  | 21        | 50        | 1.50        |
| 51+ units    | 51        | NULL      | 1.75        |
```

---

## 16. `commercial_size_settings`
**Purpose:** Commercial size factor calculation parameters (EDITABLE!)

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| setting_name | varchar(100) | base_sqft_divisor, min_factor, max_factor |
| setting_value | decimal(10,2) | 10000.00, 1.00, NULL |
| data_type | varchar(20) | decimal, integer |
| description | text | |
| updated_at | timestamp | |

**Sample Data:**
```
| setting_name       | setting_value | description |
|--------------------|---------------|-------------|
| base_sqft_divisor  | 10000.00      | SqFt ÷ this = size factor |
| min_factor         | 1.00          | Minimum size factor |
| max_factor         | NULL          | Maximum size factor (NULL = no cap) |
```

**Formula:** `Size Factor = MAX(min_factor, SqFt / base_sqft_divisor)`

---

# F. SYSTEM CONFIGURATION

## 17. `pricing_system_config`
**Purpose:** Global system settings

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| config_key | varchar(100) | Unique setting identifier |
| config_value | text | Setting value (can be JSON) |
| data_type | varchar(20) | boolean, integer, decimal, json, text |
| config_group | varchar(50) | general, pricing, inspection, etc. |
| description | text | |
| is_public | boolean | Can clients see this? |
| updated_at | timestamp | |

**Sample Data:**
```
| config_key                   | config_value | data_type | config_group |
|------------------------------|--------------|-----------|--------------|
| cpi_system_enabled           | true         | boolean   | pricing      |
| inspection_fee_amount        | 299.00       | decimal   | inspection   |
| allow_mixed_use_properties   | true         | boolean   | general      |
| default_currency             | CAD          | text      | pricing      |
| cpi_recalculation_frequency  | 12           | integer   | pricing      |
```

---

# CALCULATION FLOW

## Step 1: Property Registration
```
Client registers property → Stores:
- property_type (residential/commercial/mixed_use)
- residential_units (if applicable)
- square_footage_interior (if applicable)
- mixed_use_commercial_weight (if mixed-use)
```

## Step 2: Inspector Does PHAR Assessment
```
Inspector fills form → For each cpi_scoring_factor:
1. System loads dropdown options from corresponding lookup table
2. Inspector selects option
3. System stores answer + score in phar_assessment_responses table
```

## Step 3: CPI Score Calculation
```
1. Sum up all factor scores from each domain
2. Domain 5 uses MAX (worst case) instead of SUM
3. Total CPI Score = Sum of all domain scores
```

## Step 4: Determine CPI Band
```
SELECT band_code FROM cpi_band_ranges
WHERE min_score <= {total_score}
  AND (max_score >= {total_score} OR max_score IS NULL)
ORDER BY sort_order LIMIT 1
```

## Step 5: Get Multiplier
```
SELECT multiplier FROM cpi_multipliers
WHERE band_code = {band_from_step_4}
```

## Step 6: Get Base Price
```
IF property_type = 'residential':
    base_price = SELECT residential_base_price FROM pricing_packages WHERE package_name = {selected_package}
    
IF property_type = 'commercial':
    base_price = SELECT commercial_base_price FROM pricing_packages WHERE package_name = {selected_package}
    
IF property_type = 'mixed_use':
    res_base = SELECT residential_base_price FROM pricing_packages WHERE package_name = {selected_package}
    com_base = SELECT commercial_base_price FROM pricing_packages WHERE package_name = {selected_package}
    weight = mixed_use_commercial_weight / 100
    base_price = (res_base × (1 - weight)) + (com_base × weight)
```

## Step 7: Calculate Size Factor
```
IF property_type = 'residential' OR (mixed_use AND uses residential component):
    SELECT size_factor FROM residential_size_tiers
    WHERE min_units <= {residential_units}
      AND (max_units >= {residential_units} OR max_units IS NULL)
    
IF property_type = 'commercial' OR (mixed_use AND uses commercial component):
    base_divisor = SELECT setting_value FROM commercial_size_settings WHERE setting_name = 'base_sqft_divisor'
    min_factor = SELECT setting_value FROM commercial_size_settings WHERE setting_name = 'min_factor'
    size_factor = MAX(min_factor, square_footage / base_divisor)
    
IF property_type = 'mixed_use':
    weight = mixed_use_commercial_weight / 100
    size_factor = (residential_size_factor × (1 - weight)) + (commercial_size_factor × weight)
```

## Step 8: Final Price Calculation
```
Final Monthly Price = Base Price × Size Factor × CPI Multiplier
Final Annual Price = Final Monthly Price × 12
```

---

# ADMIN MANAGEMENT UI (Future Implementation)

Admin will have settings pages to manage:

1. **Package Pricing** → CRUD for pricing_packages
2. **CPI Band Ranges** → CRUD for cpi_band_ranges (edit score ranges)
3. **CPI Multipliers** → CRUD for cpi_multipliers (edit multipliers)
4. **Scoring Factors** → CRUD for cpi_scoring_factors (add/edit questions)
5. **Supply Materials** → CRUD for supply_line_materials (add materials)
6. **Age Brackets** → CRUD for age_brackets (edit age ranges)
7. **Containment Types** → CRUD for containment_categories
8. **Access Categories** → CRUD for crawl/roof/equipment tables
9. **Complexity Levels** → CRUD for complexity_categories
10. **Size Tiers** → CRUD for residential_size_tiers
11. **Commercial Settings** → Edit commercial_size_settings
12. **System Config** → Edit pricing_system_config

---

# RELATIONSHIP DIAGRAM

```
pricing_packages
    ↓
properties (has package_id)
    ↓
phar_assessments (has property_id)
    ↓
phar_assessment_responses (has assessment_id + factor_id)
    ↓ (lookup)
cpi_scoring_factors (defines what to ask)
    ↓ (belongs to)
cpi_domains (6 domains)
    ↓ (uses lookup tables)
supply_line_materials
age_brackets
containment_categories
crawl_access_categories
roof_access_categories
equipment_requirements
complexity_categories
    ↓ (calculates total score)
cpi_band_ranges (determines band)
    ↓ (gets multiplier)
cpi_multipliers
    ↓ (combines with size factor)
residential_size_tiers OR commercial_size_settings
    ↓ (final calculation)
FINAL MONTHLY PRICE
```

---

# NEXT STEPS

1. ✅ Review this document
2. ⏳ Create database migrations for all 17 tables
3. ⏳ Seed initial data from Excel file
4. ⏳ Build PricingEngine service class
5. ⏳ Create admin CRUD interfaces
6. ⏳ Build inspector PHAR form (loads dropdowns dynamically)
7. ⏳ Implement client care package presentation page

---

**End of Database Design Document**
