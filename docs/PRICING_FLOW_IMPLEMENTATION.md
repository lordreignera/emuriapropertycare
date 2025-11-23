# EMURIA Property Care - Pricing & Subscription Flow Implementation Guide

**Last Updated:** November 23, 2025  
**Based On:** `pricing.md` - ETOGO Subscription Costing Template

---

## Table of Contents

1. [Overview & Philosophy](#overview--philosophy)
2. [Key Pricing Principles](#key-pricing-principles)
3. [The Complete Flow](#the-complete-flow)
4. [Database Architecture](#database-architecture)
5. [Pricing Calculation Engine](#pricing-calculation-engine)
6. [Implementation Steps](#implementation-steps)
7. [User Journeys](#user-journeys)
8. [Technical Implementation](#technical-implementation)
9. [Admin Management](#admin-management)

---

## Overview & Philosophy

### Core Business Model

**CRITICAL INSIGHT:** Homes do NOT deteriorate on a monthly cycle!

Traditional subscription models charge monthly fees for monthly visits. EMURIA's model is **value-aligned**:

- ✅ **Visit frequency matches actual home needs** (2-9 visits/year, not 12)
- ✅ **Pricing based on property complexity** (not arbitrary monthly fees)
- ✅ **Post-inspection custom pricing** (after value is demonstrated)
- ✅ **Transparent, auditable scoring** (builds trust)

### Payment Model Evolution

```
Traditional Model:          EMURIA Model:
Monthly visits → Monthly fee    Inspection → Custom Product → Subscription/One-time
(12 visits/year)               (2-9 visits/year based on actual needs)
```

---

## Key Pricing Principles

### 1. **No Fixed Monthly Visits**

**Why:**
- Plumbing systems don't need monthly checks
- Roofing degrades seasonally, not monthly
- HVAC needs quarterly attention, not monthly
- Most issues are seasonal or wear-based

**Result:** 2-9 visits per year depending on home complexity

### 2. **Component-Based Pricing**

Every custom product is built from:
- **Base Visit Cost:** $150 (1 hour skilled labor)
- **Complexity Multiplier:** 1.00 - 1.60 (based on structure + finish)
- **Access Adjustment:** $0 - $150/year (easy to difficult access)
- **Home Value Premium:** 8.5% - 22.5% (based on finish level)

### 3. **Transparent Scoring**

All pricing factors are visible and explainable:

| Factor | Score Range | Impact |
|--------|-------------|--------|
| **Structural Complexity** | 0-3 | +10% per point |
| **Finish Level** | 0-3 | +10% per point |
| **Access Difficulty** | 0-3 | +$0/$50/$150 annually |
| **Home Value Premium** | 8.5%-22.5% | Final price multiplier |

### 4. **Savings-Driven Conversion**

Every custom product shows:
```
One-Time Project Cost: $3,000
EMURIA Annual Price:    $1,265
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Year 1 Savings:        $1,735 ✅
```

---

## The Complete Flow

### Phase 1: Property Registration (FREE)

```
1. Client registers (FREE - no payment)
   ↓
2. Client adds property details
   - Address, size, type
   - Photos (optional)
   - Initial description
   ↓
3. Property status = "pending_approval"
   ↓
4. Admin reviews and approves
   ↓
5. Property status = "approved"
```

**Database:** `properties` table
- `status` = 'pending_approval' → 'approved'
- `property_code` generated (e.g., "PROP-2025-001")

### Phase 2: Inspection Scheduling

```
1. Admin schedules inspection
   ↓
2. Inspector assigned
   ↓
3. Inspection conducted
   - Structural assessment
   - Finish level evaluation
   - Access difficulty check
   - System complexity rating
   - Issue identification
   ↓
4. Inspection report generated
   ↓
5. Complexity scores calculated
```

**Database:** 
- `inspections` table (report details)
- `property_complexity_scores` table (scoring breakdown)

**Calculated Scores:**
```php
// Individual scores (0-100 each)
issue_severity_score
lifestyle_score
complexity_score
access_difficulty_score
age_score
system_score
environmental_score

// Weighted total
total_complexity_score = sum of above with weights
```

### Phase 3: Custom Product Creation (CRITICAL STEP)

```
1. Admin creates custom product based on inspection
   ↓
2. Select base product template
   - Preventive Maintenance Package
   - Repair Project
   - Emergency Response Package
   - Subscription Package
   ↓
3. Customize components
   - Add/remove components
   - Adjust quantities
   - Set labor hours
   - Calculate material costs
   ↓
4. Apply pricing formula
   ↓
5. Generate custom offer
```

**This is where the ETOGO pricing model is applied!**

### Phase 4: Pricing Calculation (THE MAGIC)

```
INPUTS (from inspection):
- Structural Complexity Score (0-3)
- Finish Level Score (0-3)
- Access Difficulty (Easy/Moderate/Difficult → 0/1/3)
- Recommended Visits/Year (2-9)
- Home Type (Starter/Standard/High-End/Estate)

CALCULATION:
Step 1: Complexity Multiplier
  = 1 + (StructuralScore + FinishScore) / 10
  Example: (2 + 2) / 10 = 1.40

Step 2: Access Adjustment
  = AccessScore × $50
  Example: 1 × $50 = $50/year

Step 3: Raw Subscription
  = VisitsPerYear × $150 × ComplexityMultiplier + AccessAdjustment
  Example: 5 × $150 × 1.40 + $50 = $1,100

Step 4: Home Value Premium
  = Based on finish level (8.5% - 22.5%)
  Example: 15% for high-end finishes

Step 5: Final Annual Price
  = RawSubscription × (1 + Premium)
  Example: $1,100 × 1.15 = $1,265

Step 6: Compare to One-Time Project
  One-Time Cost: $3,000
  Annual Subscription: $1,265
  Year 1 Savings: $1,735 ✅
```

### Phase 5: Offer Presentation

```
1. Custom product created
   ↓
2. Status = "offered"
   ↓
3. Client views offer in dashboard
   - Product details
   - Component breakdown
   - Pricing comparison
   - Savings calculation
   ↓
4. Client decision:
   Option A: Accept → Status = "accepted"
   Option B: Decline → Status = "declined"
   Option C: Negotiate → Back to admin
```

**Database:** `client_custom_products` table
- `status` = 'draft' → 'offered' → 'accepted'/'declined'
- `offered_at` timestamp
- `accepted_at` timestamp

### Phase 6: Payment & Subscription Activation

```
IF ACCEPTED:
  ↓
1. Client proceeds to checkout
   ↓
2. Choose payment model:
   - One-time payment
   - Monthly subscription
   - Annual subscription
   - Pay-as-you-go
   ↓
3. Stripe payment processing
   ↓
4. Subscription created (if recurring)
   ↓
5. Work begins / Schedule visits
```

**Database:** `subscriptions` table
- Linked to `client_custom_products` via `custom_product_id`
- `payment_model` = 'monthly'/'annual'/'pay_as_you_go'

### Phase 7: Ongoing Service

```
1. Scheduled visits occur (2-9/year)
   ↓
2. Work logs created
   ↓
3. Progress tracked
   ↓
4. Annual review
   - Re-assess complexity
   - Adjust pricing if needed
   - Renew subscription
```

---

## Database Architecture

### Core Tables

#### 1. `properties`
```php
id
user_id                        // Property owner
property_code                  // "PROP-2025-001"
property_brand                 // Optional branding
property_name
address
status                         // pending_approval, approved, active
current_complexity_score       // Latest score
recommended_tier               // Calculated recommendation
has_tenants
number_of_units
```

#### 2. `inspections`
```php
id
property_id
inspector_id (user_id)
inspection_date
status                         // scheduled, completed, approved
report_file_path
structural_score              // 0-3
finish_score                  // 0-3
access_score                  // 0-3
findings (JSON)
recommendations (JSON)
```

#### 3. `property_complexity_scores`
```php
id
property_id
inspection_id
issue_severity_score          // 0-100
lifestyle_score              // 0-100
complexity_score             // 0-100
access_difficulty_score      // 0-100
age_score                    // 0-100
system_score                 // 0-100
environmental_score          // 0-100
total_complexity_score       // Weighted total
recommended_tier
recommended_visit_frequency  // 2-9 visits/year
recommended_base_price
score_breakdown (JSON)       // How score was calculated
calculated_at
calculated_by (user_id)
```

#### 4. `products` (Admin-Created Templates)
```php
id
product_code                  // "MAINT-PKG-001"
product_name                  // "Standard Preventive Maintenance"
description
category                      // maintenance, inspection, repair, subscription_package
pricing_type                  // fixed, component_based, subscription
base_price
is_active
is_customizable
created_by (user_id)
metadata (JSON)
```

#### 5. `product_components`
```php
id
product_id
component_name                // "Labor Hours", "Material Cost"
description
calculation_type              // fixed, multiply, add, percentage, hourly
parameter_name               // "hours", "quantity", "sqft"
parameter_value              // Default value
unit_cost
calculated_cost
sort_order
is_required
is_customizable
```

#### 6. `component_parameters`
```php
id
component_id
parameter_name               // "Labor Hours", "Material Quantity"
description
value_type                   // numeric, boolean, text, selection
default_value
min_value
max_value
unit                         // "hours", "sqft", "units"
cost_per_unit
calculated_cost
is_required
is_user_editable
calculation_formula (JSON)
```

#### 7. `client_custom_products` (THE KEY TABLE)
```php
id
client_id (user_id)
property_id
base_product_id             // Template used
inspection_id               // Source of pricing data
custom_product_name
custom_description
customized_components (JSON) // Modified values
total_price
pricing_model               // one_time, monthly_subscription, annual_subscription
monthly_price
annual_price
valid_from
valid_until
status                      // draft, offered, accepted, declined, active
offered_at
accepted_at
created_by (user_id)
```

#### 8. `subscriptions` (Active Subscriptions)
```php
id
user_id
custom_product_id           // Link to custom product
tier_id                     // Legacy (nullable)
stripe_id
status                      // active, cancelled, expired
trial_ends_at
ends_at
payment_model               // pay_as_you_go, monthly, annual
```

#### 9. `tier_recommendation_rules` (Calculation Engine)
```php
id
rule_name
description
input_category              // issue_severity, property_use_lifestyle, etc.
condition_criteria (JSON)   // Conditions that trigger this rule
complexity_score            // 1-100 weighted score
priority_weight             // How important is this factor
recommended_adjustments (JSON)
is_active
sort_order
```

---

## Pricing Calculation Engine

### Implementation in PHP

```php
<?php

namespace App\Services;

class PricingCalculationService
{
    // Base constants from pricing.md
    const BASE_VISIT_COST = 150;
    const ACCESS_COST_PER_POINT = 50;
    
    /**
     * Calculate custom product pricing based on ETOGO model
     */
    public function calculateCustomProductPrice(array $inputs): array
    {
        // Extract inputs
        $structuralScore = $inputs['structural_score'] ?? 0;  // 0-3
        $finishScore = $inputs['finish_score'] ?? 0;          // 0-3
        $accessScore = $inputs['access_score'] ?? 0;          // 0/1/3
        $visitsPerYear = $inputs['visits_per_year'] ?? 4;     // 2-9
        $homeValuePremium = $inputs['home_value_premium'] ?? 0.10; // 0.085-0.225
        
        // Step 1: Calculate Complexity Multiplier
        $complexityMultiplier = 1 + ($structuralScore + $finishScore) / 10;
        
        // Step 2: Calculate Access Adjustment
        $accessAdjustment = $accessScore * self::ACCESS_COST_PER_POINT;
        
        // Step 3: Calculate Raw Subscription
        $rawSubscription = ($visitsPerYear * self::BASE_VISIT_COST * $complexityMultiplier) 
                         + $accessAdjustment;
        
        // Step 4: Apply Home Value Premium
        $finalPrice = $rawSubscription * (1 + $homeValuePremium);
        
        // Calculate monthly price (if applicable)
        $monthlyPrice = $finalPrice / 12;
        
        return [
            'complexity_multiplier' => round($complexityMultiplier, 2),
            'access_adjustment' => $accessAdjustment,
            'raw_subscription' => round($rawSubscription, 2),
            'home_value_premium_percent' => ($homeValuePremium * 100),
            'annual_price' => round($finalPrice, 2),
            'monthly_price' => round($monthlyPrice, 2),
            'per_visit_cost' => round($finalPrice / $visitsPerYear, 2),
            'visits_per_year' => $visitsPerYear,
            'breakdown' => [
                'base_visit_cost' => self::BASE_VISIT_COST,
                'structural_score' => $structuralScore,
                'finish_score' => $finishScore,
                'access_score' => $accessScore,
                'complexity_multiplier' => round($complexityMultiplier, 2),
                'access_adjustment_annual' => $accessAdjustment,
                'premium_percentage' => ($homeValuePremium * 100) . '%',
            ]
        ];
    }
    
    /**
     * Calculate savings vs one-time project
     */
    public function calculateSavings(float $oneTimeProjectCost, float $annualSubscription): array
    {
        $year1Savings = $oneTimeProjectCost - $annualSubscription;
        $savingsPercent = ($year1Savings / $oneTimeProjectCost) * 100;
        
        return [
            'one_time_cost' => $oneTimeProjectCost,
            'annual_subscription' => $annualSubscription,
            'year_1_savings' => $year1Savings,
            'savings_percent' => round($savingsPercent, 1),
            'break_even_years' => 1,
        ];
    }
    
    /**
     * Determine recommended visits based on home type
     */
    public function recommendVisitsPerYear(string $homeType, array $specialConditions = []): int
    {
        $baseVisits = match($homeType) {
            'starter', 'basic' => 2,
            'standard' => 3,
            'upper_mid' => 4,
            'high_end' => 5,
            'estate', 'luxury' => 6,
            'vacation' => 2,
            'rental' => 4,
            default => 3,
        };
        
        // Apply special conditions
        if (in_array('crawlspace', $specialConditions)) $baseVisits += 1;
        if (in_array('complex_roof', $specialConditions)) $baseVisits += 1;
        if (in_array('old_property', $specialConditions)) $baseVisits += 2;
        if (in_array('high_occupancy', $specialConditions)) $baseVisits += 1;
        
        // Cap at 9 visits max
        return min($baseVisits, 9);
    }
    
    /**
     * Determine home value premium based on finish level
     */
    public function getHomeValuePremium(int $finishScore): float
    {
        return match($finishScore) {
            0 => 0.085,  // 8.5% - Basic/Builder grade
            1 => 0.105,  // 10.5% - Mid-tier/Upgraded
            2 => 0.135,  // 13.5% - High-end/Premium
            3 => 0.210,  // 21% - Luxury/Estate
            default => 0.10,
        };
    }
}
```

### Example Calculation

```php
// In controller or service
$pricingService = new PricingCalculationService();

// Data from inspection
$inputs = [
    'structural_score' => 2,    // Moderate complexity
    'finish_score' => 2,        // High-end finishes
    'access_score' => 1,        // Moderate access
    'visits_per_year' => 5,     // Based on home type
    'home_value_premium' => 0.15, // 15% premium
];

$pricing = $pricingService->calculateCustomProductPrice($inputs);

/*
Result:
[
    'complexity_multiplier' => 1.40,
    'access_adjustment' => 50,
    'raw_subscription' => 1100.00,
    'home_value_premium_percent' => 15,
    'annual_price' => 1265.00,
    'monthly_price' => 105.42,
    'per_visit_cost' => 253.00,
    'visits_per_year' => 5,
]
*/

// Compare to one-time project
$savings = $pricingService->calculateSavings(3000, 1265);

/*
Result:
[
    'one_time_cost' => 3000,
    'annual_subscription' => 1265,
    'year_1_savings' => 1735,
    'savings_percent' => 57.8,
    'break_even_years' => 1,
]
*/
```

---

## Implementation Steps

### Step 1: Admin Product Management

**Create:** `app/Http/Controllers/Admin/ProductManagementController.php`

```php
public function index()
{
    // List all product templates
    $products = Product::with('components')->get();
    return view('admin.products.index', compact('products'));
}

public function create()
{
    // Form to create new product template
    return view('admin.products.create');
}

public function store(Request $request)
{
    // Save product with components
    DB::transaction(function() use ($request) {
        $product = Product::create([...]);
        
        // Create components
        foreach($request->components as $component) {
            $product->components()->create($component);
        }
    });
}
```

**Views:**
- `resources/views/admin/products/index.blade.php` - List products
- `resources/views/admin/products/create.blade.php` - Create template
- `resources/views/admin/products/edit.blade.php` - Edit template

### Step 2: Inspection with Scoring

**Update:** `app/Http/Controllers/Admin/InspectionController.php`

```php
public function complete(Inspection $inspection, Request $request)
{
    // Save inspection report
    $inspection->update([
        'status' => 'completed',
        'structural_score' => $request->structural_score,
        'finish_score' => $request->finish_score,
        'access_score' => $request->access_score,
        'findings' => $request->findings,
    ]);
    
    // Calculate complexity scores
    $complexityService = new PropertyComplexityService();
    $complexityService->calculateAndStore($inspection);
    
    // Redirect to create custom product
    return redirect()
        ->route('admin.custom-products.create', ['inspection' => $inspection->id])
        ->with('success', 'Inspection completed. Create custom product offer.');
}
```

### Step 3: Custom Product Creation

**Create:** `app/Http/Controllers/Admin/CustomProductController.php`

```php
public function create(Request $request)
{
    $inspection = Inspection::with('property', 'complexityScores')->findOrFail($request->inspection);
    $products = Product::where('is_active', true)->get();
    
    // Get recommended pricing
    $pricingService = new PricingCalculationService();
    $recommendedPricing = $pricingService->calculateCustomProductPrice([
        'structural_score' => $inspection->structural_score,
        'finish_score' => $inspection->finish_score,
        'access_score' => $inspection->access_score,
        'visits_per_year' => $inspection->complexityScores->recommended_visit_frequency,
        'home_value_premium' => $pricingService->getHomeValuePremium($inspection->finish_score),
    ]);
    
    return view('admin.custom-products.create', compact(
        'inspection',
        'products',
        'recommendedPricing'
    ));
}

public function store(Request $request)
{
    $customProduct = ClientCustomProduct::create([
        'client_id' => $request->client_id,
        'property_id' => $request->property_id,
        'base_product_id' => $request->base_product_id,
        'inspection_id' => $request->inspection_id,
        'custom_product_name' => $request->product_name,
        'customized_components' => $request->components,
        'total_price' => $request->annual_price,
        'monthly_price' => $request->monthly_price,
        'annual_price' => $request->annual_price,
        'pricing_model' => $request->pricing_model,
        'status' => 'offered',
        'offered_at' => now(),
        'created_by' => auth()->id(),
    ]);
    
    // Notify client
    $client = User::find($request->client_id);
    $client->notify(new CustomProductOffered($customProduct));
    
    return redirect()
        ->route('admin.custom-products.show', $customProduct)
        ->with('success', 'Custom product offer created and sent to client.');
}
```

### Step 4: Client Acceptance

**Create:** `app/Http/Controllers/Client/CustomProductController.php`

```php
public function index()
{
    $offers = ClientCustomProduct::where('client_id', auth()->id())
        ->whereIn('status', ['offered', 'accepted', 'active'])
        ->with('property', 'baseProduct')
        ->latest()
        ->get();
    
    return view('client.custom-products.index', compact('offers'));
}

public function show(ClientCustomProduct $customProduct)
{
    // Ensure client owns this product
    $this->authorize('view', $customProduct);
    
    // Calculate savings
    $pricingService = new PricingCalculationService();
    $savings = $pricingService->calculateSavings(
        $customProduct->one_time_equivalent ?? ($customProduct->total_price * 2),
        $customProduct->annual_price
    );
    
    return view('client.custom-products.show', compact('customProduct', 'savings'));
}

public function accept(ClientCustomProduct $customProduct)
{
    $customProduct->update([
        'status' => 'accepted',
        'accepted_at' => now(),
    ]);
    
    // Redirect to checkout
    return redirect()
        ->route('checkout.process', ['custom_product' => $customProduct->id])
        ->with('success', 'Offer accepted. Complete payment to activate.');
}

public function decline(ClientCustomProduct $customProduct, Request $request)
{
    $customProduct->update([
        'status' => 'declined',
        'decline_reason' => $request->reason,
    ]);
    
    return redirect()
        ->route('client.custom-products.index')
        ->with('info', 'Offer declined.');
}
```

### Step 5: Checkout Integration

**Update:** `app/Http/Controllers/CheckoutController.php`

```php
public function processCheckout(Request $request)
{
    $customProduct = ClientCustomProduct::findOrFail($request->custom_product);
    
    // Create Stripe checkout session
    $checkout = auth()->user()->checkout([
        'price_data' => [
            'currency' => 'usd',
            'product_data' => [
                'name' => $customProduct->custom_product_name,
                'description' => $customProduct->custom_description,
            ],
            'unit_amount' => $customProduct->pricing_model === 'monthly_subscription'
                ? ($customProduct->monthly_price * 100)
                : ($customProduct->annual_price * 100),
            'recurring' => $customProduct->pricing_model !== 'one_time' ? [
                'interval' => $customProduct->pricing_model === 'monthly_subscription' ? 'month' : 'year',
            ] : null,
        ],
    ], [
        'success_url' => route('checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => route('checkout.cancel'),
        'metadata' => [
            'custom_product_id' => $customProduct->id,
            'property_id' => $customProduct->property_id,
        ],
    ]);
    
    return redirect($checkout->url);
}

public function success(Request $request)
{
    // Verify payment and activate subscription
    $session = Stripe\Checkout\Session::retrieve($request->session_id);
    $customProductId = $session->metadata->custom_product_id;
    
    $customProduct = ClientCustomProduct::findOrFail($customProductId);
    $customProduct->update(['status' => 'active']);
    
    // Create subscription record
    Subscription::create([
        'user_id' => auth()->id(),
        'custom_product_id' => $customProduct->id,
        'stripe_id' => $session->subscription,
        'status' => 'active',
        'payment_model' => $customProduct->pricing_model,
    ]);
    
    return view('checkout.success', compact('customProduct'));
}
```

---

## User Journeys

### Journey 1: Client with New Property

```
Day 1:
✅ Client registers (FREE)
✅ Adds property "123 Main St"
✅ Property status: "pending_approval"

Day 2:
✅ Admin reviews and approves property
✅ Admin schedules inspection for Day 5

Day 5:
✅ Inspector visits property
✅ Conducts assessment:
   - Structural Score: 2 (moderate complexity - multi-level, complex roof)
   - Finish Score: 2 (high-end finishes - granite, hardwood)
   - Access Score: 1 (moderate - some crawlspace, 2-story)
✅ Uploads report with photos
✅ System calculates complexity scores

Day 6:
✅ Admin reviews inspection report
✅ Creates custom product:
   - Base: "Preventive Maintenance Package"
   - Customizes components based on findings
   - System applies ETOGO formula:
     * Complexity Multiplier: 1.40
     * Access Adjustment: $50
     * Raw Price: $1,100
     * Premium (15%): $165
     * Final Annual Price: $1,265
     * Monthly Option: $105.42
   - Compares to one-time repairs: $3,000
   - Shows savings: $1,735
✅ Offers product to client

Day 7:
✅ Client logs in, sees offer
✅ Reviews pricing breakdown
✅ Sees 58% savings vs one-time
✅ Accepts offer
✅ Chooses monthly subscription
✅ Completes Stripe checkout
✅ Subscription activated

Ongoing:
✅ 5 preventive visits scheduled throughout year
✅ Client saves $1,735 first year
✅ Property maintained proactively
```

### Journey 2: Existing Client Needing Repair

```
Day 1:
✅ Client reports issue via dashboard
✅ Admin creates inspection for specific issue

Day 3:
✅ Inspector diagnoses problem
✅ Identifies needed repairs
✅ Scores complexity

Day 4:
✅ Admin creates custom repair project:
   - Not a subscription
   - One-time pricing_model
   - Component breakdown:
     * Labor: 8 hours @ $150
     * Materials: $500
     * Complexity adjustment
     * Final: $1,840
✅ Offers to client

Day 5:
✅ Client accepts
✅ Pays via Stripe (one-time)
✅ Work scheduled

Day 7:
✅ Work completed
✅ Project closed
```

---

## Technical Implementation

### Models

**Product.php**
```php
class Product extends Model
{
    protected $fillable = [
        'product_code', 'product_name', 'description', 'category',
        'pricing_type', 'base_price', 'is_active', 'is_customizable',
        'created_by', 'metadata'
    ];
    
    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_customizable' => 'boolean',
    ];
    
    public function components()
    {
        return $this->hasMany(ProductComponent::class);
    }
    
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

**ClientCustomProduct.php**
```php
class ClientCustomProduct extends Model
{
    protected $fillable = [
        'client_id', 'property_id', 'base_product_id', 'inspection_id',
        'custom_product_name', 'custom_description', 'customized_components',
        'total_price', 'pricing_model', 'monthly_price', 'annual_price',
        'valid_from', 'valid_until', 'status', 'offered_at', 'accepted_at',
        'created_by'
    ];
    
    protected $casts = [
        'customized_components' => 'array',
        'total_price' => 'decimal:2',
        'monthly_price' => 'decimal:2',
        'annual_price' => 'decimal:2',
        'offered_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];
    
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }
    
    public function property()
    {
        return $this->belongsTo(Property::class);
    }
    
    public function baseProduct()
    {
        return $this->belongsTo(Product::class, 'base_product_id');
    }
    
    public function inspection()
    {
        return $this->belongsTo(Inspection::class);
    }
    
    public function subscription()
    {
        return $this->hasOne(Subscription::class, 'custom_product_id');
    }
}
```

---

## Admin Management

### Product Templates (Admin Creates Once, Reuses Many Times)

**Example Product Templates:**

1. **Basic Preventive Maintenance**
   - Category: subscription_package
   - Components:
     * Quarterly Visit (4 visits/year)
     * Basic System Check
     * Minor Repairs (included)
   - Base Price: $600
   - Customizable: Yes

2. **High-End Estate Maintenance**
   - Category: subscription_package
   - Components:
     * Monthly Visit (12 visits/year)
     * Comprehensive Inspection
     * Premium System Monitoring
     * Priority Emergency Response
   - Base Price: $3,600
   - Customizable: Yes

3. **Emergency Plumbing Repair**
   - Category: repair
   - Components:
     * Emergency Callout Fee
     * Labor Hours (variable)
     * Parts & Materials (variable)
   - Base Price: $0
   - Customizable: Yes

### Admin Workflow

```
1. Create product templates in admin panel
   (Do this once per product type)
   ↓
2. When inspection completes, select appropriate template
   ↓
3. Customize components based on inspection findings
   - Adjust labor hours
   - Modify material quantities
   - Add/remove components
   ↓
4. System auto-calculates price using ETOGO formula
   ↓
5. Review and adjust if needed
   ↓
6. Send offer to client
```

---

## Summary

### What Makes This System Different

✅ **NOT Monthly Visit Based** - Visits match actual home needs (2-9/year)  
✅ **Transparent Scoring** - Every price factor is visible and explainable  
✅ **Post-Inspection Pricing** - Value demonstrated before payment requested  
✅ **Component-Based** - Every cost is itemized and justifiable  
✅ **Savings-Driven** - Shows clear ROI vs one-time projects  
✅ **Flexible Payment** - One-time, monthly, or annual options  

### Implementation Priority

**Phase 1 (Now):**
1. ✅ Property approval workflow
2. ⏳ Inspection scheduling and reporting
3. ⏳ Complexity scoring calculation

**Phase 2 (Next):**
4. ⏳ Product template management
5. ⏳ Custom product creation
6. ⏳ Pricing calculation engine

**Phase 3 (Then):**
7. ⏳ Client offer presentation
8. ⏳ Stripe checkout integration
9. ⏳ Subscription management

**Phase 4 (Future):**
10. ⏳ Visit scheduling
11. ⏳ Work log tracking
12. ⏳ Savings reporting

---

**Next Action:** Shall we build the inspection workflow with complexity scoring? This is the foundation for the entire pricing system!
