# PHAR Engine — Full Calculation Reference

This document traces every formula in the system from inspector input to final monthly price, with exact file and method references for each step.

---

## Overview of the Pipeline

```
Inspector enters findings (Step 1)
        │
        ▼
computeWeightedCPI()          ← app/Http/Controllers/InspectionController.php
        │
        ▼
computeASI()                  ← app/Http/Controllers/InspectionController.php
        │
        ▼
Inspector enters PHAR data (Step 2): TUS, labour hours, materials
        │
        ▼
storePharData()               ← app/Http/Controllers/InspectionController.php
        │
        ▼
MergeBridgeCalculator::calculate()   ← app/Services/MergeBridgeCalculator.php
   ├── BDCCalculator::calculate()    ← app/Services/BDCCalculator.php
   ├── calculateFRLC()
   ├── calculateFMC()
   ├── getTierFromConditionScore()   (Gate 1)
   ├── getTierFromARP()              (Gate 2)
   ├── selectFinalTier()
   ├── getMultiplierForTier()
   └── getBasePackagePrice()         (floor price)
        │
        ▼
saveToInspection()            ← app/Services/MergeBridgeCalculator.php
        │
        ▼
Final monthly price stored on inspections table
```

---

## Settings / Constants

All runtime settings are read via `BDCSetting::getValue(key, default)`.

| Setting Key | Default | Unit | Description |
|---|---|---|---|
| `loaded_hourly_rate` | 165.00 | $/hr | All-in labour rate |
| `visits_per_year` | 8 | visits | Default visits (overridden per inspection) |
| `hours_per_visit` | 4.5 | hrs | Default hours (overridden per inspection) |
| `infrastructure_percentage` | 0.30 | decimal | 30% overhead on labour |
| `administration_percentage` | 0.12 | decimal | 12% overhead on labour |
| `cpi_weight` | 0.60 | decimal | CPI contribution in ASI |
| `tus_weight` | 0.40 | decimal | TUS contribution in ASI |
| `tus_input_default` | 75.0 | score | Default TUS when none entered |

**Seeded in:** `database/seeders/BDCSettingsSeeder.php`
**Read by:** `app/Models/BDCSetting.php` → `getValue()`

---

## Phase 1 — CPI Scoring

### Step 1 — Inspector Submits Findings

**Where:** `app/Http/Controllers/InspectionController.php` → `store()` (line ~186)

Inspector records up to N findings across 23 building systems. Each finding has:
- `system_id` — which of the 23 systems it belongs to
- `severity` — one of: `critical`, `high`, `noi_protection`, `medium`, `low`

---

### Step 2 — Priority Score Mapping

**Where:** `InspectionController::store()` — `$priorityScores` array (line ~430)

| Severity Key | Label | Priority Score |
|---|---|---|
| `critical` | Safety & Health | 100 |
| `high` | Urgent | 80 |
| `noi_protection` | NOI Protection | 60 |
| `medium` | Value Depreciation | 40 |
| `low` | Non-Urgent | 0 |

---

### Step 3 — Weighted CPI Computation

**Where:** `InspectionController::computeWeightedCPI()` (line ~594)

```
For each building system:
    For each finding in that system:
        Deduction = (SystemWeight × PriorityScore × 9) / (20 × 100)

    SystemScore = max(0, 100 − ΣDeductions)

CPI = Σ(SystemScore × SystemWeight) / Σ(all SystemWeights)
    = Σ(SystemScore × SystemWeight) / 197
```

**Constants:**
- `$maxSystemWeight = 20` (Structural — highest weight in the catalogue)
- `$scalingFactor = 9` (caps worst-case deduction per finding)
- `$totalWeight = 197` (sum of all 23 system weights, from `InspectionSystem::sum('weight')`)

**System weights catalogued in:** `app/Support/PharCatalog.php` → `systemWeights()`
**Seeded into table by:** `database/seeders/InspectionSystemsSeeder.php`

| System | Weight | System | Weight |
|---|---|---|---|
| Structural | 20 | Windows | 8 |
| Foundation | 15 | Doors | 8 |
| Basement | 15 | Site Drainage | 8 |
| Roof | 15 | Gutters | 6 |
| Electrical | 10 | Kitchen | 6 |
| Plumbing | 10 | Exterior | 6 |
| HVAC | 10 | Stairs | 6 |
| Exterior Wall | 10 | Floor | 5 |
| Crawlspace | 10 | Walls | 5 |
| | | Ceilings | 5 |
| | | Safety | 5 |
| | | Accessibility | 5 |
| | | Pest | 5 |
| | | Garage | 4 |
| **Total** | | | **197** |

**Saved to:** `inspections.cpi_total_score` (decimal 5,1) and `inspections.system_scores` (JSON per-system breakdown)

---

### Step 4 — ASI (Asset Stability Index)

**Where:** `InspectionController::computeASI()` (below `computeWeightedCPI`)

```
ASI = (CPI × cpi_weight) + (TUS × tus_weight)
    = (CPI × 0.60) + (TUS × 0.40)
```

- `CPI` = `inspections.cpi_total_score` just computed
- `TUS` = `inspections.tus_score` (inspector enters on Step 2, default 75)
- Weights from `BDCSetting`: `cpi_weight = 0.60`, `tus_weight = 0.40`

**CPI Rating bands:**

| CPI | Rating |
|---|---|
| ≥ 90 | Excellent |
| ≥ 75 | Good |
| ≥ 60 | Fair |
| ≥ 40 | Poor |
| < 40 | Critical |

**ASI Rating bands:**

| ASI | Rating |
|---|---|
| ≥ 90 | Highly stable asset |
| ≥ 80 | Stable asset |
| ≥ 70 | Moderate stability |
| ≥ 60 | Vulnerable stability |
| ≥ 50 | Unstable asset |
| < 50 | Severe instability |

**Saved to:** `inspections.asi_score`, `inspections.cpi_rating`, `inspections.asi_rating`

---

## Phase 2 — PHAR Data Collection

**Where:** `InspectionController::storePharData()` (line ~957)

Inspector enters (Step 2 form — `resources/views/admin/inspections/form-phar-data.blade.php`):

| Field | Table Column | Notes |
|---|---|---|
| Property size (PSF) | `property_size_psf` | Drives size-factor pricing |
| Visits per year | `bdc_visits_per_year` | Overrides BDCSetting default |
| Hours per visit | `estimated_task_hours` | Overrides BDCSetting default |
| Minimum required hours | `minimum_required_hours` | Default 3 |
| TUS score | `tus_score` | 0-100, default 75 |
| Per-finding labour hours | `phar_findings.labour_hours` | Feeds FRLC |
| Per-finding materials | `inspection_materials.line_total` | Feeds FMC |

Labour hourly rate is **not** entered by the inspector — it is snapshotted from `BDCSetting.loaded_hourly_rate` at save time into `inspections.labour_hourly_rate`.

Findings are persisted to two relational tables:
- `phar_findings` — one row per finding
- `inspection_materials` — one row per material line item (`line_total = quantity × unit_cost`)

---

## Phase 3 — MergeBridgeCalculator

**File:** `app/Services/MergeBridgeCalculator.php`
**Entry point:** `calculate(Inspection $inspection)` (line ~26)
**Invoked from:** `InspectionController::storePharData()` final-save path

---

### Step 5 — BDC (Base Deployment Cost)

**Where:** `app/Services/BDCCalculator.php` → `calculate()` or `calculateWithParams()`

If the inspection has `bdc_visits_per_year` or `estimated_task_hours` set, `calculateWithParams()` is called with those values; otherwise `calculate()` uses the BDCSetting defaults.

```
LabourHours/year   = visits_per_year × hours_per_visit
LabourCost/year    = LabourHours/year × loaded_hourly_rate
InfrastructureCost = LabourCost/year × 0.30
AdministrationCost = LabourCost/year × 0.12

BDC_annual  = LabourCost/year + InfrastructureCost + AdministrationCost
            = LabourCost/year × (1 + 0.30 + 0.12)
            = LabourCost/year × 1.42

BDC_monthly = BDC_annual / 12
```

**Example (seeded defaults):**
```
LabourHours/year   = 8 × 4.5         = 36.00 hrs
LabourCost/year    = 36 × $165        = $5,940.00
InfrastructureCost = $5,940 × 0.30   = $1,782.00
AdministrationCost = $5,940 × 0.12   =   $712.80

BDC_annual  = $5,940 + $1,782 + $712.80 = $8,434.80
BDC_monthly = $8,434.80 / 12            =   $702.90
```

---

### Step 6 — FRLC (Findings Remediation Labour Cost)

**Where:** `MergeBridgeCalculator::calculateFRLC()` (reads `phar_findings` table)

```
TotalLabourHours = SUM(phar_findings.labour_hours)  [for this inspection]
FRLC_annual      = TotalLabourHours × labour_hourly_rate
FRLC_monthly     = FRLC_annual / 12
```

Rate used = `inspections.labour_hourly_rate` (snapshotted at Step 2 save).

---

### Step 7 — FMC (Findings Material Cost)

**Where:** `MergeBridgeCalculator::calculateFMC()` (reads `inspection_materials` table)

```
FMC_annual  = SUM(inspection_materials.line_total)  [for this inspection]
FMC_monthly = FMC_annual / 12
```

Each `line_total` = `quantity × unit_cost` (entered by inspector in the materials sub-form).

---

### Step 8 — TRC (Total Remediation Cost)

**Where:** `MergeBridgeCalculator::calculate()` (line ~65)

```
TRC_annual  = BDC_annual + FRLC_annual + FMC_annual
TRC_monthly = TRC_annual / 12

ARP_monthly = TRC_monthly          ← Annual Recurring Price expressed monthly
```

---

### Step 9 — Condition Score (CPI passthrough)

**Where:** `MergeBridgeCalculator::mapCPItoConditionScore()` (line ~180)

```
condition_score = clamp(cpi_total_score, 0, 100)
```

CPI is already 0–100 so this is a direct passthrough with a safety clamp.

---

### Step 10 — Dual-Gate Tier Assignment

#### Gate 1: Condition Score → Tier

**Where:** `MergeBridgeCalculator::getTierFromConditionScore()` (line ~192)

| condition_score | Tier assigned |
|---|---|
| ≥ 75 | Essentials |
| ≥ 40 | White-Glove |
| < 40 | Critical Care |

#### Gate 2: ARP Cost Pressure → Tier

**Where:** `MergeBridgeCalculator::getTierFromARP()` (line ~201)

Loads all active pricing packages ordered by `sort_order`. For each package, looks up its base monthly price via `BaseServicePricingService::getPackageBasePrice(name, propertyTypeCode)`. Packages are sorted ascending by price.

The tier escalates to the highest package whose floor the ARP meets or exceeds:
```
tier = lowest package (start)
foreach package (ascending price):
    if ARP_monthly >= package.base_price:
        tier = package.name
```

If a specific package was selected for the inspection and ARP is below that package's floor, the selected package name is returned as the minimum (field: `inspections.service_package_name`).

Property type code is resolved from `inspections.property_type_snapshot` or `properties.type`.

#### Final Tier (Max of Both Gates)

**Where:** `MergeBridgeCalculator::selectFinalTier()` (line ~238)

Tier ranking: Essentials=1, Premium=2, White-Glove=3, Critical Care=4.

```
final_rank = max(Gate1_rank, Gate2_rank)
tier_final = name matching final_rank
```

---

### Step 11 — Tier Multiplier

**Where:** `MergeBridgeCalculator::getMultiplierForTier()` (line ~264)

| Tier | Multiplier |
|---|---|
| Essentials | 1.00× |
| Premium | 1.15× |
| White-Glove | 1.35× |
| Critical Care | 1.55× |

```
ARP_equivalent_final = ARP_monthly × multiplier_final
```

---

### Step 12 — Floor Price (Base Package Price)

**Where:** `MergeBridgeCalculator::getBasePackagePrice()` (line ~277)

Priority order for resolving the floor:
1. `inspections.base_price_snapshot` — if already set, use it directly
2. Look up the selected `service_package_id` → `PricingPackage` → `BaseServicePricingService::getPackageBasePrice()`
3. Fallback: `package_pricing.base_monthly_price` (first active record on the package)

---

### Step 13 — Scientific Final Monthly (Apply Floor)

**Where:** `MergeBridgeCalculator::calculate()` (line ~96)

```
scientific_final_monthly = max(ARP_equivalent_final, base_package_price)
scientific_final_annual  = scientific_final_monthly × 12
```

The floor ensures that even a perfectly-conditioned property with zero remediation findings still pays at least the minimum for its service package.

---

### Step 14 — Per-Unit Breakdown

**Where:** `MergeBridgeCalculator::calculatePerUnitBreakdown()` (line ~340)

```
units = property.residential_units  (if residential)
      = property.commercial_units   (if commercial)
      = 1                           (if single-unit or unspecified)

bdc_per_unit_annual      = BDC_annual / units
frlc_per_unit_annual     = FRLC_annual / units
fmc_per_unit_annual      = FMC_annual / units
trc_per_unit_annual      = TRC_annual / units
final_monthly_per_unit   = scientific_final_monthly / units
```

---

### Step 15 — Persist to inspections Table

**Where:** `MergeBridgeCalculator::saveToInspection()` (line ~370)

All results are written back to the `inspections` table in one `update()` call:

| Column | Value |
|---|---|
| `bdc_annual` / `bdc_monthly` | Step 5 |
| `labour_hourly_rate` | Snapshot |
| `frlc_annual` / `frlc_monthly` | Step 6 |
| `fmc_annual` / `fmc_monthly` | Step 7 |
| `trc_annual` / `trc_monthly` | Step 8 |
| `arp_monthly` | Step 8 |
| `condition_score` | Step 9 |
| `tier_score` | Gate 1 |
| `tier_arp` | Gate 2 |
| `tier_final` | Step 10 |
| `multiplier_final` | Step 11 |
| `arp_equivalent_final` | Step 11 |
| `base_package_price_snapshot` | Step 12 |
| `units_for_calculation` | Step 14 |
| `bdc_per_unit_annual` | Step 14 |
| `frlc_per_unit_annual` | Step 14 |
| `fmc_per_unit_annual` | Step 14 |
| `trc_per_unit_annual` | Step 14 |
| `final_monthly_per_unit` | Step 14 |

> **Note:** `scientific_final_monthly` / `scientific_final_annual` are **not** persisted — they are computed on-the-fly in reports and the show view from: `max(arp_equivalent_final, base_package_price_snapshot)`.

---

## End-to-End Worked Example

**Scenario:** Single-family residential. No findings except one Structural (W20) Safety & Health issue.

### CPI
```
Finding: system=Structural (weight=20), severity=critical (score=100)
Deduction = (20 × 100 × 9) / (20 × 100) = 9.0

Structural SystemScore = max(0, 100 − 9.0) = 91.0
All other 22 systems score = 100.0 (no findings)

Other 22 systems total weight = 197 − 20 = 177
Weighted sum = (91.0 × 20) + (100.0 × 177) = 1,820 + 17,700 = 19,520

CPI = 19,520 / 197 = 99.1
```

### ASI
```
TUS = 75 (default)
ASI = (99.1 × 0.60) + (75 × 0.40) = 59.46 + 30.0 = 89.5

cpi_rating = "Excellent"   (CPI ≥ 90 → Excellent, but 99.1 ≥ 90 ✓)
asi_rating = "Stable asset" (ASI ≥ 80)
```

### BDC (defaults)
```
8 visits × 4.5 hrs × $165 × 1.42 = $8,434.80/yr = $702.90/month
```

### FRLC (say inspector logged 2 hours for that finding)
```
2 hrs × $165 = $330/yr = $27.50/month
```

### FMC (say $450 in materials)
```
$450/yr = $37.50/month
```

### TRC
```
$8,434.80 + $330.00 + $450.00 = $9,214.80/yr = $768.00/month
ARP_monthly = $768.00
```

### Tier
```
Gate 1 (CPI=99.1 ≥ 75):  Essentials
Gate 2 (ARP=$768, above Essentials floor, below Premium): Essentials
tier_final = Essentials → multiplier = 1.00×
ARP_equivalent = $768.00 × 1.00 = $768.00
```

### Final
```
base_package_price (Essentials, residential) = e.g. $650.00
scientific_final_monthly = max($768.00, $650.00) = $768.00/month
scientific_final_annual  = $9,216.00/year
```

---

## Key File Map

| Calculation | File | Method |
|---|---|---|
| Priority scores | `app/Http/Controllers/InspectionController.php` | `store()` — `$priorityScores` array |
| CPI weighted formula | `app/Http/Controllers/InspectionController.php` | `computeWeightedCPI()` |
| ASI formula | `app/Http/Controllers/InspectionController.php` | `computeASI()` |
| System weights catalogue | `app/Support/PharCatalog.php` | `systemWeights()` |
| System weights seeded | `database/seeders/InspectionSystemsSeeder.php` | `run()` |
| BDC formula | `app/Services/BDCCalculator.php` | `calculate()` / `calculateWithParams()` |
| FRLC formula | `app/Services/MergeBridgeCalculator.php` | `calculateFRLC()` |
| FMC formula | `app/Services/MergeBridgeCalculator.php` | `calculateFMC()` |
| TRC / ARP | `app/Services/MergeBridgeCalculator.php` | `calculate()` steps 3–4 |
| Gate 1 tier | `app/Services/MergeBridgeCalculator.php` | `getTierFromConditionScore()` |
| Gate 2 tier | `app/Services/MergeBridgeCalculator.php` | `getTierFromARP()` |
| Final tier | `app/Services/MergeBridgeCalculator.php` | `selectFinalTier()` |
| Multipliers | `app/Services/MergeBridgeCalculator.php` | `getMultiplierForTier()` |
| Floor price | `app/Services/MergeBridgeCalculator.php` | `getBasePackagePrice()` |
| Package base prices | `app/Services/BaseServicePricingService.php` | `getPackageBasePrice()` |
| Persist all results | `app/Services/MergeBridgeCalculator.php` | `saveToInspection()` |
| BDC settings | `database/seeders/BDCSettingsSeeder.php` | `run()` |
| PHAR Step 2 form | `resources/views/admin/inspections/form-phar-data.blade.php` | Section 1 inputs |
| Results display | `resources/views/admin/inspections/show.blade.php` | ARP & Condition section |
