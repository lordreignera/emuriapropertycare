# Product Component Parameter System & Tier Recommendation Engine

## 📦 Nested Structure Overview

```
PRODUCT (e.g., "Complete HVAC Maintenance")
  ├── COMPONENT 1 (e.g., "Inspection")
  │   ├── PARAMETER 1 (e.g., "Labor Hours")
  │   │   ├── Value: 2
  │   │   ├── Unit: "hours"
  │   │   └── Cost: $100/hour → Total: $200
  │   ├── PARAMETER 2 (e.g., "Travel Time")
  │   │   ├── Value: 1
  │   │   ├── Unit: "hours"
  │   │   └── Cost: $50/hour → Total: $50
  │   └── Component Total: $250
  │
  ├── COMPONENT 2 (e.g., "Filter Replacement")
  │   ├── PARAMETER 1 (e.g., "Number of Filters")
  │   │   ├── Value: 4
  │   │   ├── Unit: "units"
  │   │   └── Cost: $25/unit → Total: $100
  │   ├── PARAMETER 2 (e.g., "Installation Time")
  │   │   ├── Value: 0.5
  │   │   ├── Unit: "hours"
  │   │   └── Cost: $80/hour → Total: $40
  │   └── Component Total: $140
  │
  ├── COMPONENT 3 (e.g., "Cleaning")
  │   ├── PARAMETER 1 (e.g., "Fixed Service Fee")
  │   │   ├── Value: 1
  │   │   ├── Unit: "service"
  │   │   └── Cost: $150/service → Total: $150
  │   └── Component Total: $150
  │
  └── PRODUCT TOTAL: $540
```

---

## 🏗️ Database Structure

### 1. **products** table
- Holds main product information
- Can be created by admin
- Has base pricing and category

### 2. **product_components** table
- Multiple components per product
- Each component can have its own calculation type
- Can be `fixed`, `multiply`, `hourly`, etc.

### 3. **component_parameters** table ⭐ NEW
- Multiple parameters per component
- Each parameter has:
  - `parameter_name`: "Labor Hours", "Material Quantity", etc.
  - `value_type`: numeric, boolean, text, selection, calculated
  - `default_value`: Starting value
  - `cost_per_unit`: Cost per unit of this parameter
  - `calculated_cost`: Final calculated cost
  - `unit`: "hours", "sqft", "units", etc.
  - `calculation_formula`: JSON for complex calculations

---

## 💡 Example: Building a Product

### **Product: "Preventive Roof Maintenance"**

#### **Component 1: Inspection & Assessment**
```
Parameter 1: Inspector Labor
  - Value: 3 hours
  - Cost per unit: $100/hour
  - Calculated: 3 × $100 = $300

Parameter 2: Safety Equipment
  - Value: 1 set
  - Cost per unit: $50/set
  - Calculated: 1 × $50 = $50

Parameter 3: Report Generation
  - Value: 1 report
  - Cost per unit: $75/report
  - Calculated: 1 × $75 = $75

Component Total: $425
```

#### **Component 2: Minor Repairs**
```
Parameter 1: Technician Labor
  - Value: 5 hours
  - Cost per unit: $85/hour
  - Calculated: 5 × $85 = $425

Parameter 2: Roofing Materials
  - Value: 50 sqft
  - Cost per unit: $8/sqft
  - Calculated: 50 × $8 = $400

Parameter 3: Sealant Application
  - Value: 2 tubes
  - Cost per unit: $30/tube
  - Calculated: 2 × $30 = $60

Component Total: $885
```

#### **Component 3: Gutter Cleaning**
```
Parameter 1: Cleaning Labor
  - Value: 2 hours
  - Cost per unit: $75/hour
  - Calculated: 2 × $75 = $150

Parameter 2: Disposal Fee
  - Value: 1 service
  - Cost per unit: $35/service
  - Calculated: 1 × $35 = $35

Component Total: $185
```

**Product Total: $1,495**

---

## 🎯 Tier Recommendation Engine

### **Core Philosophy**
Instead of clients selecting a pre-defined tier, the system **calculates** the appropriate tier based on property data and inspection findings.

### **7 Key Input Categories**

#### 1. **Issue Severity** (30% weight)
- Critical, High, Medium, Low
- Based on inspection findings
- Urgent issues increase complexity score

#### 2. **Property Use / Lifestyle** (20% weight)
- Owner-occupied vs. Rental
- Has pets / Has kids
- High-traffic vs. Low-use
- Personality: calm, busy, luxury, high-use

#### 3. **Property Type & Complexity** (15% weight)
- Single-family, Multi-unit, Duplex
- Square footage
- Luxury finishes vs. Standard

#### 4. **Structural Access Difficulty** (15% weight)
- Crawlspaces
- Rooflines
- Steep terrain
- Narrow access
- Drainage complexity

#### 5. **Property Age** (10% weight)
- New (0-10 years): Low score
- Moderate (11-20 years): Medium score
- Aging (21-30 years): High score
- Historic (30+ years): Very high score

#### 6. **System Complexity** (5% weight)
- HVAC systems
- Plumbing complexity
- Electrical systems
- Mechanical equipment

#### 7. **Environmental Factors** (5% weight)
- Climate zone
- Terrain challenges
- Weather exposure
- Regional considerations

---

## 📊 Complexity Score Calculation

### **Score Range: 0-100**

```php
// Example calculation
$total_score = 
    ($issue_severity_score × 0.30) +
    ($lifestyle_score × 0.20) +
    ($complexity_score × 0.15) +
    ($access_difficulty_score × 0.15) +
    ($age_score × 0.10) +
    ($system_score × 0.05) +
    ($environmental_score × 0.05);
```

### **Tier Mapping**

| Complexity Score | Recommended Tier | Visit Frequency | Skill Level | Base Price |
|------------------|------------------|-----------------|-------------|------------|
| 80-100 | Elite Estate Care | Weekly (24/year) | Expert | $1,499/mo |
| 60-79 | Premium Protection | Monthly (12/year) | Advanced | $849/mo |
| 40-59 | Enhanced Care | Bi-monthly (6/year) | Intermediate | $549/mo |
| 20-39 | Essential Care | Quarterly (4/year) | Intermediate | $349/mo |
| 0-19 | Basic Care | Semi-annual (2/year) | Basic | $199/mo |

---

## 🔄 Workflow: From Property Registration to Tier Recommendation

```
1. Client registers property (free)
   ↓
2. System auto-generates property code (e.g., APP12)
   ↓
3. Client adds tenants (if applicable)
   - Each tenant gets login: APP12-1, APP12-2, etc.
   - Common password per property
   ↓
4. PM assigns inspector
   ↓
5. Inspector conducts inspection
   - Documents findings
   - Assesses severity
   - Notes system complexity
   ↓
6. System runs TierRecommendationEngine
   - Calculates 7 factor scores
   - Generates complexity score (0-100)
   - Recommends tier
   - Suggests visit frequency
   - Recommends skill level
   ↓
7. Admin creates custom product for client
   - Based on recommended tier
   - Add components
   - Add parameters to each component
   - Set costs for each parameter
   - System auto-calculates total
   ↓
8. System presents offer to client
   - Option A: Pay-as-you-go
   - Option B: Subscribe (monthly/annual)
   ↓
9. Client accepts offer
   ↓
10. Work begins based on scope
```

---

## 💻 Usage Example: Admin Creating a Custom Product

```php
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\ComponentParameter;

// 1. Create base product
$product = Product::create([
    'product_code' => 'HVAC-MAINT-001',
    'product_name' => 'Complete HVAC Maintenance',
    'category' => 'maintenance',
    'pricing_type' => 'component_based',
    'base_price' => 0,
    'is_customizable' => true,
    'created_by' => auth()->id(),
]);

// 2. Add Component: Inspection
$inspection = ProductComponent::create([
    'product_id' => $product->id,
    'component_name' => 'Initial Inspection',
    'calculation_type' => 'add', // Sum all parameters
    'sort_order' => 1,
]);

// 3. Add Parameters to Inspection Component
ComponentParameter::create([
    'component_id' => $inspection->id,
    'parameter_name' => 'Labor Hours',
    'value_type' => 'numeric',
    'default_value' => 2,
    'unit' => 'hours',
    'cost_per_unit' => 100,
    'sort_order' => 1,
]);

ComponentParameter::create([
    'component_id' => $inspection->id,
    'parameter_name' => 'Travel Time',
    'value_type' => 'numeric',
    'default_value' => 1,
    'unit' => 'hours',
    'cost_per_unit' => 50,
    'sort_order' => 2,
]);

// 4. Calculate total
$product->recalculateComponents();
$totalPrice = $product->calculateTotalPrice();
// Result: $300 (2×$100 + 1×$50)
```

---

## 🎨 Admin Interface Features

### **Product Management**
- ✅ Create unlimited products
- ✅ Add/remove components
- ✅ Add/remove parameters per component
- ✅ Set calculation types (fixed, multiply, hourly, percentage)
- ✅ Define validation rules
- ✅ Set min/max values

### **Component Builder**
- ✅ Drag-and-drop reordering
- ✅ Copy/paste components between products
- ✅ Template library for common components
- ✅ Real-time cost calculation preview

### **Parameter Editor**
- ✅ Multiple value types (numeric, boolean, text, dropdown)
- ✅ Custom units (hours, sqft, units, kg, etc.)
- ✅ Formula builder for complex calculations
- ✅ Conditional parameters (show if X is true)

### **Tier Recommendation Rules**
- ✅ Create custom scoring rules
- ✅ Adjust factor weights
- ✅ Define condition criteria
- ✅ Test rules against sample properties
- ✅ View rule application history

---

## 🚀 Benefits of This System

### **1. Flexibility**
- Unlimited products
- Unlimited components per product
- Unlimited parameters per component
- Fully customizable pricing logic

### **2. Transparency**
- Clients see exactly what they're paying for
- Parameter-level cost breakdown
- Clear formula visibility

### **3. Scalability**
- Add new products without code changes
- Adjust pricing dynamically
- Regional pricing variations
- Seasonal adjustments

### **4. Intelligence**
- Data-driven tier recommendations
- Removes human bias
- Consistent pricing logic
- Builds trust with clients

### **5. Diagnostic Approach**
- Positions company as expert advisor
- "Doctor prescribing care" vs. "salesperson"
- Regenerative philosophy alignment
- Client feels seen, not sold to

---

## 📝 Summary

This system transforms EMURIA from a "package seller" to a "diagnostic care system":

✅ **Products** are built from **Components**  
✅ **Components** are built from **Parameters**  
✅ **Parameters** have individual costs  
✅ **Tier Recommendation Engine** calculates optimal tier  
✅ **Complexity scoring** is data-driven and transparent  
✅ **Pay-as-you-go** or **subscription** options  
✅ **Tenant system** integrated per property  
✅ **Property codes** for easy tenant login  

**Result**: A scalable, intelligent, regenerative property care platform.

---

**Last Updated**: November 15, 2025  
**System Version**: 2.0
