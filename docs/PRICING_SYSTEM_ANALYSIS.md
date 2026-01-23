# ETOGO PRICING CALCULATOR - SYSTEM ANALYSIS & IMPLEMENTATION PLAN

## 📊 PRICING CALCULATOR OVERVIEW

The ETOGO pricing calculator uses a sophisticated multi-factor model to automatically calculate monthly service costs based on:
1. **Property characteristics** (type, size, units)
2. **Service package tier** (Essentials, Premium, White-Glove)
3. **CPI (Condition, Pressure, Infrastructure) Score** (0-4 band system)

---

## 🎯 CORE PRICING FORMULA

```
Final Monthly Cost = Base Price × Size Factor × CPI Multiplier
```

### Components Breakdown:

#### 1. **Base Price** (Service Package)
| Package | Residential Base | Commercial Base |
|---------|-----------------|-----------------|
| Essentials | $350/mo | $650/mo |
| Premium | $650/mo | $1,200/mo |
| White-Glove | $1,100/mo | $2,000/mo |

#### 2. **Size Factor** (Property Scale)

**Residential (by unit count):**
- 1-5 units: 1.0x
- 6-20 units: 1.25x
- 21-50 units: 1.5x
- 51+ units: 1.75x

**Commercial (by square footage):**
- Formula: `MAX(1.0, SqFt / 10,000)`
- Example: 5,000 sqft = 0.5, but MIN is 1.0, so = 1.0x
- Example: 25,000 sqft = 2.5x

**Mixed-Use:**
- Blend both factors using commercial weight percentage

#### 3. **CPI Multiplier** (Risk Premium)
| CPI Band | Score Range | Multiplier |
|----------|-------------|------------|
| CPI-0 | 0-2 pts | 1.00x (no premium) |
| CPI-1 | 3-5 pts | 1.08x (+8%) |
| CPI-2 | 6-8 pts | 1.18x (+18%) |
| CPI-3 | 9-11 pts | 1.35x (+35%) |
| CPI-4 | 12+ pts | 1.55x (+55%) |

---

## 🔍 CPI SCORING SYSTEM (6 DOMAINS)

### Domain 1: System Design & Pressure (0-7 points)
- **Unit-level shut-offs**: No = +3 pts
- **Shared risers**: Yes = +2 pts
- **High water pressure (>80 PSI)**: +2 pts
- **No isolation zones**: +2 pts

### Domain 2: Material Risk (0-5 points)
**Supply Line Materials:**
- Copper: 0 pts ✅
- PEX: +1 pt
- CPVC: +2 pts
- Mixed/Unknown: +2 pts
- Galvanized: +3 pts
- Poly-B: +4 pts ⚠️

**Unknown drain/waste**: +1 pt

### Domain 3: Age & Lifecycle (0-5 points)
**Building or Fixture Age (whichever is worse):**
- 0-10 years: 0 pts
- 11-25 years: +1 pt
- 26-40 years: +2 pts
- 41-60 years: +3 pts
- 61+ years: +4 pts

**Undocumented systems**: +1 pt

### Domain 4: Access & Containment (0-3 points)
- Accessible isolation: 0 pts
- Partial isolation: +1 pt
- Poor isolation: +2 pts
- No isolation: +3 pts

### Domain 5: Accessibility & Safety (0-4 points, capped)
Takes the **WORST** of:

**Crawl Space Access:**
- No crawl/full basement: 0 pts
- Crawl with clearance: +1 pt
- Low clearance (<3 ft): +2 pts
- Damp/poorly ventilated: +3 pts
- Hazardous: +4 pts

**Roof Access:**
- Flat/low pitch: 0 pts
- Moderate pitch: +1 pt
- High pitch: +2 pts
- High pitch + brittle roofing: +3 pts

**Equipment Needed:**
- Standard ladder: 0 pts
- Extended ladder/anchors: +1 pt
- Scissor lift: +2 pts
- Boom lift/crane: +3 pts

**Access Time:**
- ≤10 minutes: 0 pts
- 11-30 minutes: +1 pt
- 31-60 minutes: +2 pts
- >60 minutes: +3 pts

### Domain 6: Operational Complexity (0-3 points)
- Low density/simple: 0 pts
- Medium density: +1 pt
- High density: +2 pts
- Business-critical: +3 pts

---

## 💡 PRICING EXAMPLES

### Example 1: Simple Residential
- **Property**: 10-unit residential building
- **Package**: Essentials
- **CPI Scoring**:
  - Good shut-offs, copper pipes, 15 years old, accessible
  - CPI Score: 1 pt → **CPI-0 (1.0x)**
- **Calculation**:
  ```
  $350 (base) × 1.25 (10 units) × 1.0 (CPI-0) = $437.50/mo
  ```

### Example 2: High-Risk Commercial
- **Property**: 25,000 sqft commercial
- **Package**: Premium
- **CPI Scoring**:
  - No unit shutoffs (+3), Poly-B pipes (+4), 45 years old (+3), poor isolation (+2)
  - CPI Score: 12 pts → **CPI-4 (1.55x)**
- **Calculation**:
  ```
  $1,200 (base) × 2.5 (25k sqft) × 1.55 (CPI-4) = $4,650/mo
  ```

---

## 🏗️ IMPLEMENTATION PLAN

### Phase 1: PHAR Inspection Form Design

Create comprehensive inspection form capturing all CPI domains:

**Database Schema: `inspections` table additions:**
```sql
-- CPI Domain 1: System Design
unit_level_shutoffs BOOLEAN
shared_risers BOOLEAN
water_pressure_psi INT
isolation_zones_present BOOLEAN

-- CPI Domain 2: Materials
supply_line_material VARCHAR (copper, pex, cpvc, galvanized, poly-b, mixed)
drain_waste_unknown BOOLEAN

-- CPI Domain 3: Age
building_age_years INT
fixture_age_years INT
systems_documented BOOLEAN

-- CPI Domain 4: Containment
containment_category VARCHAR (accessible, partial, poor, none)

-- CPI Domain 5: Accessibility
crawl_access_category VARCHAR
roof_access_category VARCHAR
equipment_requirement VARCHAR
access_time_minutes INT

-- CPI Domain 6: Complexity
operational_complexity VARCHAR (low, medium, high, business_critical)

-- Calculated Fields
cpi_total_score INT
cpi_band VARCHAR (CPI-0, CPI-1, CPI-2, CPI-3, CPI-4)
cpi_multiplier DECIMAL(4,2)
```

### Phase 2: Automatic Pricing Engine

**Model: `App\Models\PricingEngine`**

Methods:
- `calculateCPIScore(Inspection $inspection): int`
- `calculateCPIBand(int $score): string`
- `calculateCPIMultiplier(string $band): float`
- `calculateSizeFactor(Property $property): float`
- `calculateMonthlyPrice(Property $property, Inspection $inspection, string $package): float`

### Phase 3: Care Package Selection

After PHAR inspection completion:
1. **Calculate CPI automatically** from inspection data
2. **Generate 3 package options** with prices:
   - Essentials
   - Premium (recommended based on CPI)
   - White-Glove
3. **Present to client** with clear breakdown:
   - Base price
   - Size adjustment
   - Risk premium (CPI)
   - Total monthly cost
4. **Client selects package** → Subscription created

### Phase 4: Approval Workflow Integration

Based on CPI and work order cost:
- **PM Can Approve**: <$5,000 + Safety-critical
- **Owner Must Approve**: $5,000-$25,000
- **Board Must Approve**: >$25,000 + Structural

---

## 🎨 UI/UX FLOW

### Inspector's PHAR Form
Multi-step wizard with 6 sections (one per CPI domain)
- Photo uploads for evidence
- Dropdown selections (pre-populated from lookups)
- Numeric inputs for ages, pressure, time
- Real-time CPI score preview

### Client's Package Selection Page
```
┌─────────────────────────────────────────┐
│  Your Property Assessment Results       │
│  CPI Score: 7 (CPI-2 - Moderate Risk)  │
└─────────────────────────────────────────┘

┌─────────────┬─────────────┬─────────────┐
│ ESSENTIALS  │  PREMIUM    │ WHITE-GLOVE │
│  $531/mo    │  $980/mo    │  $1,652/mo  │
│             │ RECOMMENDED │             │
│ • Basic     │ • Enhanced  │ • Premium   │
│ • Reactive  │ • Proactive │ • Concierge │
└─────────────┴─────────────┴─────────────┘
```

---

## 📋 NEXT STEPS

1. ✅ **Understand calculator** (DONE)
2. 🔲 Create PHAR inspection form migrations
3. 🔲 Build PricingEngine service class
4. 🔲 Design inspector UI for data collection
5. 🔲 Create care package presentation page
6. 🔲 Integrate with subscription system
7. 🔲 Test with sample properties

Would you like me to start implementing any of these phases?
