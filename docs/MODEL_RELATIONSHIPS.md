# Model Relationships Documentation

## 🗂️ Complete Model Relationship Map

This document provides a comprehensive overview of all model relationships in the EMURIA Regenerative Property Care system.

---

## 📊 Model Hierarchy

```
User (Client/Staff)
  ├── Properties
  │   ├── Tenants
  │   │   └── Emergency Reports
  │   ├── Inspections
  │   ├── Custom Products
  │   ├── Complexity Scores
  │   └── Projects
  │       ├── Scope of Work
  │       ├── Work Logs
  │       ├── Milestones
  │       └── Budgets
  ├── Subscriptions
  │   └── Custom Product
  └── Created Products (Admin)
      └── Components
          └── Parameters
```

---

## 👤 User Model

### Relationships

| Relationship | Type | Model | Description |
|--------------|------|-------|-------------|
| `properties()` | HasMany | Property | Properties owned by client |
| `tenants()` | HasMany | Tenant | Tenants managed by client |
| `subscriptions()` | HasMany | Subscription | All subscriptions |
| `customProducts()` | HasMany | ClientCustomProduct | Products offered to client |
| `createdProducts()` | HasMany | Product | Products created by admin |
| `managedProjects()` | HasMany | Project | Projects managed (PM role) |
| `inspections()` | HasMany | Inspection | Inspections assigned (Inspector) |
| `assignedEmergencyReports()` | HasMany | TenantEmergencyReport | Emergency reports assigned |
| `approvedProperties()` | HasMany | Property | Properties approved by user |

### Key Methods
- `isClient()` - Check if user is a client
- `isStaff()` - Check if user is staff
- `hasActiveSubscription()` - Check subscription status

---

## 🏘️ Property Model

### Relationships

| Relationship | Type | Model | Description |
|--------------|------|-------|-------------|
| `user()` | BelongsTo | User | Property owner (client) |
| `subscription()` | BelongsTo | Subscription | Associated subscription |
| `approvedBy()` | BelongsTo | User | Who approved the property |
| `projects()` | HasMany | Project | All projects for this property |
| `tenants()` | HasMany | Tenant | All tenants in this property |
| `emergencyReports()` | HasMany | TenantEmergencyReport | All emergency reports |
| `customProducts()` | HasMany | ClientCustomProduct | Custom products for property |
| `complexityScores()` | HasMany | PropertyComplexityScore | Calculated complexity scores |
| `inspections()` | HasMany | Inspection | All inspections |

### Key Fields
- `property_code` - Unique code (APP12, SUN01, etc.)
- `property_brand` - Client's brand name
- `has_tenants` - Boolean flag
- `number_of_units` - Total units
- `tenant_common_password` - Shared tenant password
- `current_complexity_score` - Latest calculated score (0-100)
- `recommended_tier` - Recommended tier name

### Key Methods
- `generatePropertyCode($brand)` - Auto-generate property code
- `generateTenantPassword()` - Generate common password
- `hasTenants()` - Check if property has tenants
- `activeTenants()` - Get only active tenants

---

## 👥 Tenant Model

### Relationships

| Relationship | Type | Model | Description |
|--------------|------|-------|-------------|
| `property()` | BelongsTo | Property | The property tenant lives in |
| `client()` | BelongsTo | User | Property owner |
| `emergencyReports()` | HasMany | TenantEmergencyReport | Reports filed by tenant |

### Key Fields
- `tenant_number` - 1, 2, 3, etc.
- `tenant_login` - Format: APP12-1, APP12-2
- `can_report_emergency` - Boolean
- `status` - active, inactive, moved_out
- `last_login_at` - Last login timestamp

### Key Methods
- `generateTenantLogin($propertyCode, $tenantNumber)` - Generate login ID
- `isActive()` - Check if tenant is active
- `getFullNameAttribute()` - Get full name

---

## 🚨 TenantEmergencyReport Model

### Relationships

| Relationship | Type | Model | Description |
|--------------|------|-------|-------------|
| `tenant()` | BelongsTo | Tenant | Tenant who reported |
| `property()` | BelongsTo | Property | Property where emergency occurred |
| `assignedUser()` | BelongsTo | User | Technician/Inspector assigned |

### Key Fields
- `report_number` - Format: EMR-20251115-0001
- `emergency_type` - plumbing, electrical, heating, etc.
- `urgency` - low, medium, high, critical
- `status` - reported, acknowledged, assigned, in_progress, resolved
- `photos` - JSON array
- `floor_plan_pin` - JSON {x, y, floor}

### Key Methods
- `generateReportNumber()` - Auto-generate report number
- `acknowledge()` - Mark as acknowledged
- `assignTo($userId)` - Assign to technician
- `markResolved($notes, $cost)` - Mark as resolved
- `isCritical()` - Check if critical

---

## 📦 Product Model (Admin-Created)

### Relationships

| Relationship | Type | Model | Description |
|--------------|------|-------|-------------|
| `creator()` | BelongsTo | User | Admin who created product |
| `components()` | HasMany | ProductComponent | Product components |
| `customProducts()` | HasMany | ClientCustomProduct | Custom versions for clients |

### Key Fields
- `product_code` - Unique code (e.g., HVAC-MAINT-001)
- `product_name` - Display name
- `category` - maintenance, inspection, repair, etc.
- `pricing_type` - fixed, component_based, subscription, pay_per_use
- `base_price` - Base price
- `is_customizable` - Can be customized per client

### Key Methods
- `calculateTotalPrice()` - Calculate total from components
- `recalculateComponents()` - Recalculate all component costs

---

## 🧩 ProductComponent Model

### Relationships

| Relationship | Type | Model | Description |
|--------------|------|-------|-------------|
| `product()` | BelongsTo | Product | Parent product |
| `parameters()` | HasMany | ComponentParameter | Component parameters |

### Key Fields
- `component_name` - Component name
- `calculation_type` - fixed, multiply, add, percentage, hourly
- `parameter_value` - Default value
- `unit_cost` - Cost per unit
- `calculated_cost` - Final calculated cost
- `is_required` - Boolean
- `is_customizable` - Boolean

### Key Methods
- `calculateCost()` - Calculate component cost
- `recalculateParameters()` - Recalculate all parameters

---

## ⚙️ ComponentParameter Model (NEW)

### Relationships

| Relationship | Type | Model | Description |
|--------------|------|-------|-------------|
| `component()` | BelongsTo | ProductComponent | Parent component |

### Key Fields
- `parameter_name` - e.g., "Labor Hours", "Material Quantity"
- `value_type` - numeric, boolean, text, selection, calculated
- `default_value` - Starting value
- `unit` - hours, sqft, units, etc.
- `cost_per_unit` - Cost per unit
- `calculated_cost` - Final cost
- `calculation_formula` - JSON for complex formulas

### Key Methods
- `calculateCost($customValue)` - Calculate parameter cost
- `applyFormula($value)` - Apply complex calculation
- `validateValue($value)` - Validate parameter value

---

## 🎯 ClientCustomProduct Model

### Relationships

| Relationship | Type | Model | Description |
|--------------|------|-------|-------------|
| `client()` | BelongsTo | User | Client this product is for |
| `property()` | BelongsTo | Property | Property this product is for |
| `baseProduct()` | BelongsTo | Product | Base product template |
| `inspection()` | BelongsTo | Inspection | Inspection that generated this |
| `creator()` | BelongsTo | User | Admin who created this |

### Key Fields
- `custom_product_name` - Customized name
- `customized_components` - JSON with modified values
- `total_price` - Total calculated price
- `pricing_model` - one_time, pay_as_you_go, monthly, annual
- `status` - draft, offered, accepted, declined, active

### Key Methods
- `calculateTotalPrice()` - Calculate from customized components
- `markAsOffered()` - Mark as offered to client
- `markAsAccepted()` - Mark as accepted by client
- `isValid()` - Check if still valid

---

## 💳 Subscription Model

### Relationships

| Relationship | Type | Model | Description |
|--------------|------|-------|-------------|
| `user()` | BelongsTo | User | Subscriber |
| `tier()` | BelongsTo | Tier | Legacy tier (if applicable) |
| `property()` | BelongsTo | Property | Property subscription is for |
| `customProduct()` | BelongsTo | ClientCustomProduct | Custom product (new system) |
| `properties()` | HasMany | Property | Properties under subscription |
| `projects()` | HasMany | Project | Projects under subscription |

### Key Fields
- `payment_cadence` - monthly, annual
- `payment_model` - pay_as_you_go, monthly, annual, hybrid
- `status` - active, expired, cancelled, paused

### Key Methods
- `getAmountAttribute()` - Calculate amount (custom product or tier)
- `isPayAsYouGo()` - Check payment model
- `isHybrid()` - Check if hybrid model

---

## 📊 PropertyComplexityScore Model

### Relationships

| Relationship | Type | Model | Description |
|--------------|------|-------|-------------|
| `property()` | BelongsTo | Property | Property being scored |
| `inspection()` | BelongsTo | Inspection | Inspection that generated score |
| `calculator()` | BelongsTo | User | Who calculated the score |

### Key Fields (All 0-100)
- `issue_severity_score` - 30% weight
- `lifestyle_score` - 20% weight
- `complexity_score` - 15% weight
- `access_difficulty_score` - 15% weight
- `age_score` - 10% weight
- `system_score` - 5% weight
- `environmental_score` - 5% weight
- `total_complexity_score` - Weighted total

### Recommended Fields
- `recommended_tier` - Calculated tier name
- `recommended_visit_frequency` - Visits per year
- `recommended_skill_level` - basic, intermediate, advanced, expert
- `recommended_base_price` - Suggested pricing

### Key Methods
- `calculateTotal()` - Calculate weighted total
- `getTierRecommendation()` - Get recommended tier
- `getVisitFrequency()` - Get visit frequency
- `getSkillLevel()` - Get skill level
- `getComplexityGrade()` - Get A-F grade

---

## 🎯 TierRecommendationRule Model

### Relationships
None (configuration data)

### Key Fields
- `rule_name` - Rule name
- `input_category` - Category (7 types)
- `condition_criteria` - JSON matching criteria
- `complexity_score` - Score contribution (0-100)
- `priority_weight` - Weight multiplier
- `recommended_adjustments` - JSON adjustments

### Key Methods
- `appliesTo($propertyData)` - Check if rule applies
- `getWeightedScore()` - Get weighted score contribution

---

## 🔗 Relationship Summary Table

| Model | Has Many | Belongs To | Many to Many |
|-------|----------|------------|--------------|
| **User** | properties, tenants, subscriptions, customProducts, inspections | - | roles, permissions |
| **Property** | tenants, projects, inspections, customProducts, complexityScores | user, subscription | - |
| **Tenant** | emergencyReports | property, client(User) | - |
| **Product** | components, customProducts | creator(User) | - |
| **ProductComponent** | parameters | product | - |
| **ComponentParameter** | - | component | - |
| **ClientCustomProduct** | - | client(User), property, baseProduct, inspection | - |
| **Subscription** | properties, projects | user, tier, property, customProduct | - |
| **PropertyComplexityScore** | - | property, inspection, calculator(User) | - |
| **TenantEmergencyReport** | - | tenant, property, assignedUser(User) | - |

---

## 🚀 Usage Examples

### Creating a Property with Tenants
```php
$property = Property::create([
    'user_id' => $client->id,
    'property_code' => Property::generatePropertyCode('Sunrise'),
    'property_brand' => 'Sunrise Apartments',
    'has_tenants' => true,
    'number_of_units' => 4,
    'tenant_common_password' => Property::generateTenantPassword(),
    // ... other fields
]);

// Add tenants
for ($i = 1; $i <= 4; $i++) {
    Tenant::create([
        'property_id' => $property->id,
        'client_id' => $client->id,
        'tenant_number' => $i,
        'tenant_login' => Tenant::generateTenantLogin($property->property_code, $i),
        'unit_number' => "Unit {$i}",
    ]);
}
```

### Calculate Complexity Score
```php
use App\Services\TierRecommendationEngine;

$engine = new TierRecommendationEngine();
$score = $engine->calculateRecommendation($property, $inspection);

// Access results
echo $score->total_complexity_score; // 75
echo $score->recommended_tier; // "Premium Protection"
echo $score->recommended_visit_frequency; // 12 visits/year
```

### Create Custom Product for Client
```php
$customProduct = ClientCustomProduct::create([
    'client_id' => $client->id,
    'property_id' => $property->id,
    'base_product_id' => $baseProduct->id,
    'inspection_id' => $inspection->id,
    'custom_product_name' => 'Enhanced HVAC Care - Sunrise Apt',
    'customized_components' => [...], // Modified components
    'pricing_model' => 'monthly_subscription',
    'status' => 'offered',
]);

$customProduct->calculateTotalPrice();
$customProduct->markAsOffered();
```

---

**Last Updated**: November 15, 2025  
**Version**: 2.0
