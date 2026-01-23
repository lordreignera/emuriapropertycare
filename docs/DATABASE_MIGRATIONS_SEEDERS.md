# Database Migrations & Seeders Documentation

**Project:** Emuria Regenerative Property Care  
**Date:** January 23, 2026  
**Database:** MySQL

---

## Overview

This document outlines all database migrations and seeders for the property management system with integrated CPI (Condition Performance Index) pricing.

---

## Migration Timeline

### Core Laravel & Authentication (0001_01_01_*)

| Migration | Table | Purpose |
|-----------|-------|---------|
| `0001_01_01_000000_create_users_table` | users | User authentication and profiles |
| `0001_01_01_000001_create_cache_table` | cache, cache_locks | Laravel caching |
| `0001_01_01_000002_create_jobs_table` | jobs, job_batches, failed_jobs | Queue management |

### Jetstream & Teams (2025_11_12_203*)

| Migration | Table | Purpose |
|-----------|-------|---------|
| `2025_11_12_203050_add_two_factor_columns_to_users_table` | users | Two-factor authentication fields |
| `2025_11_12_203334_create_personal_access_tokens_table` | personal_access_tokens | API token management |
| `2025_11_12_204349_create_teams_table` | teams | Team/organization management |
| `2025_11_12_204350_create_team_user_table` | team_user | User-team relationships |
| `2025_11_12_204351_create_team_invitations_table` | team_invitations | Team invitation system |

### Authorization (2025_11_12_210*)

| Migration | Table | Purpose |
|-----------|-------|---------|
| `2025_11_12_210419_create_permission_tables` | roles, permissions, model_has_roles, model_has_permissions, role_has_permissions | Spatie Permission RBAC system |

### Business Core (2025_11_12_211*)

| Migration | Table | Purpose |
|-----------|-------|---------|
| `2025_11_12_211000_create_tiers_table` | tiers | Service tier definitions |
| `2025_11_12_211500_create_subscriptions_table` | subscriptions | Client subscriptions |

### Property Management (2025_11_12_212*)

| Migration | Table | Purpose | Key Fields |
|-----------|-------|---------|------------|
| `2025_11_12_212005_create_clients_table` | clients | Client records | user_id, contact info |
| `2025_11_12_212416_create_properties_table` | properties | Property registry | **type** (residential/commercial/mixed_use), **residential_units**, **mixed_use_commercial_weight**, **project_manager_id**, **inspector_id**, square footage fields |
| `2025_11_12_212500_create_projects_table` | projects | Project management | property_id, status, budget |
| `2025_11_12_212510_create_inspections_table` | inspections | PHAR inspections | **project_id** (nullable), **inspector_id** (nullable), scheduled_date, status |
| `2025_11_12_212520_create_scope_of_works_table` | scope_of_works | Work scope definitions | project_id, deliverables |
| `2025_11_12_212530_create_quotes_table` | quotes | Price quotes | project_id, items, total_amount |
| `2025_11_12_212540_create_work_logs_table` | work_logs | Activity logging | project_id, hours, notes |
| `2025_11_12_212550_create_progress_trackers_table` | progress_trackers | Project progress | project_id, completion_percentage |
| `2025_11_12_212560_create_milestones_table` | milestones | Project milestones | project_id, due_date, status |
| `2025_11_12_212570_create_invoices_table` | invoices | Billing invoices | project_id, amount, payment_status |
| `2025_11_12_212580_create_budgets_table` | budgets | Budget tracking | project_id, allocated_amount |
| `2025_11_12_212590_create_change_orders_table` | change_orders | Scope changes | project_id, cost_impact |
| `2025_11_12_212600_create_communications_table` | communications | Messages/notes | project_id, message, sender |
| `2025_11_12_212610_create_savings_table` | savings | Cost savings tracking | project_id, saved_amount |

### Stripe Integration (2025_11_12_234*)

| Migration | Table | Purpose |
|-----------|-------|---------|
| `2025_11_12_234514_create_customer_columns` | users | Add stripe_id column |
| `2025_11_12_234516_create_subscription_items_table` | subscription_items | Stripe subscription items |
| `2025_11_12_234517_add_meter_id_to_subscription_items_table` | subscription_items | Usage-based billing |
| `2025_11_12_234518_add_meter_event_name_to_subscription_items_table` | subscription_items | Meter event tracking |
| `2025_11_13_011117_add_stripe_price_ids_to_tiers_table` | tiers | Link tiers to Stripe prices |

### System Enhancements (2025_11_13+)

| Migration | Table | Purpose |
|-----------|-------|---------|
| `2025_11_13_175711_create_notifications_table` | notifications | System notifications |
| `2025_11_15_211500_create_new_system_tables` | Multiple system tables | Extended system functionality |
| `2025_11_15_211600_add_component_parameters_table` | component_parameters | Configurable components |
| `2025_11_23_211727_add_awaiting_inspection_status_to_properties` | properties | Add 'awaiting_inspection' status |

### CPI Pricing System (2026_01_23_1500*)

#### Package & Property Management (150001-150003)

| Migration | Table | Purpose | Key Data |
|-----------|-------|---------|----------|
| `2026_01_23_150001_create_pricing_packages_table` | pricing_packages | Service packages | Essentials, Premium, White-Glove with residential/commercial base prices |
| `2026_01_23_150002_create_property_types_table` | property_types | Property type definitions | residential, commercial, mixed_use |
| `2026_01_23_150003_create_mixed_use_calculation_settings_table` | mixed_use_calculation_settings | Mixed-use pricing settings | default_commercial_weight (50%) |

#### CPI Band System (150004-150005)

| Migration | Table | Purpose | Key Data |
|-----------|-------|---------|----------|
| `2026_01_23_150004_create_cpi_band_ranges_table` | cpi_band_ranges | Score → Band mapping | CPI-0 (0-2), CPI-1 (3-5), CPI-2 (6-8), CPI-3 (9-11), CPI-4 (12+) |
| `2026_01_23_150005_create_cpi_multipliers_table` | cpi_multipliers | Band → Price multiplier | 1.00x, 1.08x, 1.18x, 1.35x, 1.55x |

#### CPI Domains (150006)

| Migration | Table | Purpose | Key Data |
|-----------|-------|---------|----------|
| `2026_01_23_150006_create_cpi_domains_table` | cpi_domains | 6 inspection domains | System Design (7pts), Materials (5pts), Age (5pts), Containment (3pts), Accessibility (4pts), Complexity (3pts) |

#### Lookup Tables (150007-150013)

| Migration | Table | Purpose | Options Count |
|-----------|-------|---------|--------------|
| `2026_01_23_150007_create_supply_line_materials_table` | supply_line_materials | Pipe material types | 6 options (Copper, PEX, CPVC, Galvanized, Poly-B, Mixed) |
| `2026_01_23_150008_create_age_brackets_table` | age_brackets | Age range scoring | 5 brackets (0-10, 11-25, 26-40, 41-60, 61+) |
| `2026_01_23_150009_create_containment_categories_table` | containment_categories | Leak containment | 4 categories (Accessible, Partial, Poor, No isolation) |
| `2026_01_23_150010_create_crawl_access_categories_table` | crawl_access_categories | Crawl space access | 5 categories (Full basement → Hazardous) |
| `2026_01_23_150011_create_roof_access_categories_table` | roof_access_categories | Roof access difficulty | 4 categories (Flat/low → High specialty) |
| `2026_01_23_150012_create_equipment_requirements_table` | equipment_requirements | Equipment needed | 4 levels (Standard ladder → Boom lift) |
| `2026_01_23_150013_create_complexity_categories_table` | complexity_categories | Operational complexity | 4 levels (Low density → Business-critical) |

#### Size Factor Tables (150014-150015)

| Migration | Table | Purpose | Configuration |
|-----------|-------|---------|--------------|
| `2026_01_23_150014_create_residential_size_tiers_table` | residential_size_tiers | Unit count → Size factor | 1-5 (1.0x), 6-20 (1.25x), 21-50 (1.5x), 51+ (1.75x) |
| `2026_01_23_150015_create_commercial_size_settings_table` | commercial_size_settings | Commercial size calculation | SqFt ÷ 10,000 = factor, min 1.0x |

#### System Configuration (150016-150017)

| Migration | Table | Purpose | Key Settings |
|-----------|-------|---------|-------------|
| `2026_01_23_150016_create_pricing_system_config_table` | pricing_system_config | Global pricing settings | CPI enabled, inspection fee ($299), currency (CAD) |
| `2026_01_23_150017_create_cpi_scoring_factors_table` | cpi_scoring_factors | Individual scoring factors | Links domains to lookup tables, defines question types |

---

## Database Seeders

### Standard Seeders (Auto-run with migrate:fresh --seed)

| Seeder | Purpose | Data Created |
|--------|---------|--------------|
| `RolePermissionSeeder` | Create roles & permissions | Super Admin, Administrator, PM, Inspector, Technician, Finance, Client roles |
| `SuperAdminSeeder` | Create admin user | admin@emuria.com / @dm1n2@25 |

### CPI Pricing System Seeder (Manual: php artisan db:seed --class=CPIPricingSystemSeeder)

**Master Seeder:** `CPIPricingSystemSeeder` - Runs all CPI seeders in correct order

#### Individual Seeders (Called by Master)

| Seeder | Table | Records | Data Source |
|--------|-------|---------|-------------|
| `PricingPackagesSeeder` | pricing_packages | 3 | Essentials ($350/$650), Premium ($650/$1200), White-Glove ($1100/$2000) |
| `PropertyTypesSeeder` | property_types | 3 | Residential, Commercial, Mixed-Use |
| `MixedUseCalculationSettingsSeeder` | mixed_use_calculation_settings | 1 | 50% default commercial weight |
| `CpiBandRangesSeeder` | cpi_band_ranges | 5 | CPI-0 to CPI-4 with score ranges |
| `CpiMultipliersSeeder` | cpi_multipliers | 5 | 1.00x to 1.55x multipliers |
| `CpiDomainsSeeder` | cpi_domains | 6 | 6 inspection domains from Excel |
| `SupplyLineMaterialsSeeder` | supply_line_materials | 6 | Pipe materials with risk scores |
| `AgeBracketsSeeder` | age_brackets | 5 | Age ranges with scores (0-4 points) |
| `ContainmentCategoriesSeeder` | containment_categories | 4 | Isolation levels with scores |
| `CrawlAccessCategoriesSeeder` | crawl_access_categories | 5 | Access difficulty with scores |
| `RoofAccessCategoriesSeeder` | roof_access_categories | 4 | Roof access levels with scores |
| `EquipmentRequirementsSeeder` | equipment_requirements | 4 | Equipment types with scores |
| `ComplexityCategoriesSeeder` | complexity_categories | 4 | Complexity levels with scores |
| `ResidentialSizeTiersSeeder` | residential_size_tiers | 4 | Unit tiers with size factors |
| `CommercialSizeSettingsSeeder` | commercial_size_settings | 3 | SqFt calculation parameters |
| `PricingSystemConfigSeeder` | pricing_system_config | 5 | System-wide pricing settings |

---

## Key Table Relationships

### CPI Pricing Flow

```
Property (type: residential/commercial/mixed_use, residential_units, square_footage)
    ↓
PricingPackage (Essentials/Premium/White-Glove)
    ↓ (gets base price)
ResidentialSizeTiers OR CommercialSizeSettings
    ↓ (calculates size factor)
Inspection → CpiScoringFactors (6 domains)
    ↓ (uses lookup tables)
SupplyLineMaterials, AgeBrackets, etc.
    ↓ (calculates total CPI score)
CpiBandRanges (determines which band)
    ↓ (looks up multiplier)
CpiMultipliers
    ↓ (final calculation)
Final Price = Base × Size Factor × CPI Multiplier
```

### Foreign Key Dependencies

**Properties Table:**
- `user_id` → users
- `subscription_id` → subscriptions
- `approved_by` → users
- `project_manager_id` → users
- `inspector_id` → users

**Inspections Table:**
- `project_id` → projects (nullable)
- `inspector_id` → users (nullable)
- `assigned_by` → users

**CpiScoringFactors Table:**
- `domain_id` → cpi_domains
- Soft references to lookup tables via `lookup_table` field

---

## Pricing Calculation Examples

### Example 1: 10-Unit Residential Property

**Property:**
- Type: Residential
- Units: 10
- Package: Essentials

**Calculation:**
```
Base Price: $350 (Essentials residential)
Size Factor: 1.25x (6-20 units tier)
CPI Score: 8 points → CPI-2 band → 1.18x multiplier

Final Monthly Price = $350 × 1.25 × 1.18 = $516.25
```

### Example 2: 5,000 SqFt Commercial Property

**Property:**
- Type: Commercial
- SqFt: 5,000
- Package: Premium

**Calculation:**
```
Base Price: $1,200 (Premium commercial)
Size Factor: max(1.0, 5000/10000) = 1.0x
CPI Score: 3 points → CPI-1 band → 1.08x multiplier

Final Monthly Price = $1,200 × 1.0 × 1.08 = $1,296
```

### Example 3: Mixed-Use Property

**Property:**
- Type: Mixed-Use
- Units: 8 residential
- SqFt: 3,000 commercial
- Commercial Weight: 40%
- Package: White-Glove

**Calculation:**
```
Residential Base: $1,100
Commercial Base: $2,000
Blended Base: ($1,100 × 0.6) + ($2,000 × 0.4) = $1,460

Residential Size: 1.25x (8 units → 6-20 tier)
Commercial Size: max(1.0, 3000/10000) = 1.0x
Blended Size: (1.25 × 0.6) + (1.0 × 0.4) = 1.15x

CPI Score: 5 points → CPI-1 band → 1.08x multiplier

Final Monthly Price = $1,460 × 1.15 × 1.08 = $1,813.92
```

---

## CPI Scoring Breakdown

### Domain Scoring (Total: 0-27 points possible, capped by domain max)

| Domain | Max Points | Scoring Method | Key Factors |
|--------|-----------|----------------|-------------|
| 1. System Design & Pressure | 7 | Sum | Unit shutoffs (3), Shared risers (2), Pressure >80 PSI (2), No isolation zones (2) |
| 2. Material Risk | 5 | Lookup | Poly-B (4), Galvanized (3), CPVC (2), PEX (1), Copper (0) |
| 3. Age & Lifecycle | 5 | Formula | Building age + Fixture age (take higher) + Documentation (1) |
| 4. Containment | 3 | Lookup | No isolation (3), Poor (2), Partial (1), Accessible (0) |
| 5. Accessibility | 4 | MAX | Takes worst case: Crawl (0-4), Roof (0-3), Equipment (0-3), Time (0-3) - capped at 4 |
| 6. Complexity | 3 | Lookup | Business-critical (3), High density (2), Medium (1), Low (0) |

### CPI Band Determination

```php
if (score <= 2)  → CPI-0 (Excellent)   → 1.00x multiplier
if (score <= 5)  → CPI-1 (Good)        → 1.08x multiplier  
if (score <= 8)  → CPI-2 (Fair)        → 1.18x multiplier
if (score <= 11) → CPI-3 (Poor)        → 1.35x multiplier
if (score >= 12) → CPI-4 (Critical)    → 1.55x multiplier
```

---

## Models Created

All 17 CPI pricing models with:
- ✅ Proper fillable fields
- ✅ Type casting
- ✅ Relationships (where applicable)
- ✅ Active scopes
- ✅ Helper methods

**Models:**
1. PricingPackage
2. PropertyType
3. MixedUseCalculationSetting
4. CpiBandRange
5. CpiMultiplier
6. CpiDomain
7. SupplyLineMaterial
8. AgeBracket
9. ContainmentCategory
10. CrawlAccessCategory
11. RoofAccessCategory
12. EquipmentRequirement
13. ComplexityCategory
14. ResidentialSizeTier
15. CommercialSizeSetting
16. PricingSystemConfig
17. CpiScoringFactor

---

## Running Migrations & Seeders

### Fresh Install
```bash
# Run all migrations and standard seeders
php artisan migrate:fresh --seed

# Run CPI pricing system seeders
php artisan db:seed --class=CPIPricingSystemSeeder
```

### Reset Just CPI Data
```bash
# Truncate and reseed CPI tables only
php artisan db:seed --class=CPIPricingSystemSeeder
```

### Individual Seeder
```bash
php artisan db:seed --class=PricingPackagesSeeder
```

---

## Configuration Notes

### Editable via Admin Panel (Future)

All lookup tables and settings are designed to be editable through an admin interface:
- ✅ Pricing package base prices
- ✅ CPI band score ranges
- ✅ CPI multipliers
- ✅ All lookup categories (materials, age brackets, etc.)
- ✅ Size factor tiers
- ✅ System configuration values

### Data Source

All CPI data seeded from: `ETOFO Pricing Calculator.xlsx`
- Sheet 1: Inputs (Property & Package data)
- Sheet 2: CPI_Scoring (6 domains)
- Sheet 3: Calculator (Final pricing formulas)
- Sheet 4: Lookups (All dropdown options)

---

**End of Documentation**

*Last Updated: January 23, 2026*
