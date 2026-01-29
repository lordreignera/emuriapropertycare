# Inspection Form - Data Immutability & Report Generation

## 🎯 Core Principle: SNAPSHOT EVERYTHING

**Problem**: If admin changes CPI scoring settings (e.g., changes Poly-B material from 4 points to 3 points), all historical inspections would be affected, causing:
- Wrong historical reports
- Incorrect pricing calculations
- Data integrity issues
- Legal/audit problems

**Solution**: **SNAPSHOT all lookup values at inspection time** - create an immutable record that never changes even if settings are updated.

---

## 📸 What Gets Snapshotted?

### **1. Property Information (at inspection time)**
```php
// Even if property details change later, inspection keeps original values
'owner_name' => 'John Doe',
'owner_email' => 'john@example.com', 
'owner_phone' => '+1234567890',
'property_code' => 'PROP-2025-001',
'property_name' => 'Downtown Apartments',
'property_address_snapshot' => '123 Main St, Toronto, Canada',
'property_type_snapshot' => 'residential',
'property_year_built' => 1990,
'residential_units_snapshot' => 10,
'commercial_sqft_snapshot' => null,
'mixed_use_weight_snapshot' => null,
```

### **2. Service Package (at inspection time)**
```php
// If package prices change tomorrow, this inspection keeps today's prices
'service_package_id' => 2, // References pricing_packages.id
'service_package_name' => 'Premium', // Snapshot of name
'base_price_snapshot' => 650.00, // Snapshot of price at inspection time
```

### **3. CPI Lookup Values (at inspection time)**
```php
// Domain 2: Material Risk
'cpi_supply_material_id' => 5, // FK to supply_line_materials
'cpi_supply_material_name' => 'Poly-B', // Snapshot of name
'cpi_supply_material_score' => 4, // Snapshot of score at inspection time

// If admin changes Poly-B score from 4 to 3 tomorrow:
// - New inspections get score = 3
// - This inspection KEEPS score = 4 (immutable)

// Domain 4: Containment
'cpi_containment_category_id' => 2,
'cpi_containment_category_name' => 'Partial isolation', // Snapshot
'cpi_containment_score' => 1, // Snapshot of score

// Domain 5: Accessibility sub-scores
'cpi_crawl_access_id' => 3,
'cpi_crawl_access_name' => 'Low-clearance crawl (<3 ft)', // Snapshot
'cpi_crawl_access_score' => 2, // Snapshot

'cpi_roof_access_id' => 2,
'cpi_roof_access_name' => 'Moderate pitch (4:12–7:12)', // Snapshot
'cpi_roof_access_score' => 1, // Snapshot

'cpi_equipment_requirement_id' => 1,
'cpi_equipment_requirement_name' => 'Standard ladder only', // Snapshot
'cpi_equipment_requirement_score' => 0, // Snapshot

// Domain 6: Complexity
'cpi_complexity_category_id' => 2,
'cpi_complexity_category_name' => 'Medium density', // Snapshot
'cpi_complexity_score' => 1, // Snapshot
```

### **4. CPI Band Determination (at inspection time)**
```php
// If admin changes CPI-3 range from "9-11" to "10-12" tomorrow:
// - New inspections use new ranges
// - This inspection KEEPS original determination
'cpi_total_score' => 10, // Calculated sum
'cpi_band' => 'CPI-3', // Band at inspection time
'cpi_multiplier' => 1.35, // Multiplier at inspection time
'cpi_band_range_snapshot' => '9-11 points', // Snapshot of range
'cpi_band_name_snapshot' => 'Poor', // Snapshot of display name
```

### **5. Final Pricing Calculation (at inspection time)**
```php
// Complete pricing snapshot - NEVER CHANGES
'residential_size_factor' => 1.25, // 10 units → 6-20 tier
'commercial_size_factor' => null,
'harmonised_size_factor' => 1.25,
'final_monthly_cost' => 673.00, // $650 × 1.25 × 1.35
'final_annual_cost' => 8076.00, // $673 × 12
```

---

## 💾 Database Storage Strategy

### **Migration Pattern:**
```php
// Store both FK (for relationship) AND snapshot values
$table->foreignId('cpi_supply_material_id')->nullable()->constrained('supply_line_materials');
$table->string('cpi_supply_material_name')->nullable(); // Snapshot
$table->integer('cpi_supply_material_score')->default(0); // Snapshot

// Why both?
// - FK: Allows JOIN queries to lookup table (e.g., show all inspections using Poly-B)
// - Snapshot: Preserves exact value at inspection time even if lookup table changes
```

---

## 🔒 How to Prevent Changes to Historical Data

### **Controller Store Method:**
```php
public function store(Request $request)
{
    $property = Property::with('user')->findOrFail($request->property_id);
    $servicePackage = PricingPackage::findOrFail($request->service_package_id);
    
    // Get current values from lookup tables
    $supplyMaterial = SupplyLineMaterial::find($request->cpi_supply_material_id);
    $containmentCategory = ContainmentCategory::find($request->cpi_containment_category_id);
    $crawlAccess = CrawlAccessCategory::find($request->cpi_crawl_access_id);
    $roofAccess = RoofAccessCategory::find($request->cpi_roof_access_id);
    $equipmentRequirement = EquipmentRequirement::find($request->cpi_equipment_requirement_id);
    $complexityCategory = ComplexityCategory::find($request->cpi_complexity_category_id);
    
    // Calculate building age
    $buildingAge = date('Y') - $request->property_year_built;
    
    // Calculate domain scores
    $domain1Score = $this->calculateDomain1Score($request);
    $domain2Score = $supplyMaterial->score_points + ($request->cpi_drain_material_unknown === 'yes' ? 1 : 0);
    $domain3Score = $this->calculateDomain3Score($buildingAge, $request->cpi_fixture_age, $request->cpi_systems_documented);
    $domain4Score = $containmentCategory->score_points;
    $domain5Score = min(max($crawlAccess->score_points, $roofAccess->score_points, $equipmentRequirement->score_points), 4);
    $domain6Score = $complexityCategory->score_points;
    
    // Calculate CPI total
    $cpiTotalScore = $domain1Score + $domain2Score + $domain3Score + $domain4Score + $domain5Score + $domain6Score;
    
    // Determine CPI band (using CURRENT settings)
    $cpiBandRange = CpiBandRange::active()
        ->where('min_score', '<=', $cpiTotalScore)
        ->where(function($q) use ($cpiTotalScore) {
            $q->whereNull('max_score')->orWhere('max_score', '>=', $cpiTotalScore);
        })
        ->orderBy('sort_order')
        ->first();
    
    $cpiMultiplier = CpiMultiplier::where('band_code', $cpiBandRange->band_code)->first();
    
    // Get base price based on property type
    $basePrice = $this->getBasePrice($property->type, $servicePackage, $property->mixed_use_commercial_weight);
    
    // Calculate size factors
    $sizeFactor = $this->calculateSizeFactor($property);
    
    // Final pricing calculation
    $finalMonthly = $basePrice * $sizeFactor * $cpiMultiplier->multiplier;
    $finalAnnual = $finalMonthly * 12;
    
    // CREATE INSPECTION WITH SNAPSHOTS
    $inspection = Inspection::create([
        'property_id' => $property->id,
        'inspector_id' => auth()->id(),
        
        // ===== PROPERTY SNAPSHOTS =====
        'owner_name' => $property->user->name,
        'owner_email' => $property->user->email,
        'owner_phone' => $property->user->phone,
        'property_code' => $property->property_code,
        'property_name' => $property->property_name,
        'property_address_snapshot' => "{$property->property_address}, {$property->city}, {$property->country}",
        'property_type_snapshot' => $property->type,
        'property_year_built' => $request->property_year_built,
        'residential_units_snapshot' => $property->residential_units,
        'commercial_sqft_snapshot' => $property->square_footage_interior,
        'mixed_use_weight_snapshot' => $property->mixed_use_commercial_weight,
        
        // ===== SERVICE PACKAGE SNAPSHOT =====
        'service_package_id' => $servicePackage->id,
        'service_package_name' => $servicePackage->package_name,
        'base_price_snapshot' => $basePrice,
        
        // ===== INSPECTION DETAILS =====
        'inspection_date' => $request->inspection_date,
        'scheduled_date' => $request->inspection_date,
        'weather_conditions' => $request->weather_conditions,
        'summary' => $request->summary,
        
        // ===== DOMAIN 1 =====
        'cpi_unit_shutoffs' => $request->cpi_unit_shutoffs,
        'cpi_shared_risers' => $request->cpi_shared_risers,
        'cpi_static_pressure' => $request->cpi_static_pressure,
        'cpi_isolation_zones' => $request->cpi_isolation_zones,
        'domain_1_score' => $domain1Score,
        'domain_1_notes' => $request->domain_1_notes,
        
        // ===== DOMAIN 2 WITH SNAPSHOTS =====
        'cpi_supply_material_id' => $supplyMaterial->id,
        'cpi_supply_material_name' => $supplyMaterial->material_name, // SNAPSHOT
        'cpi_supply_material_score' => $supplyMaterial->score_points, // SNAPSHOT
        'cpi_drain_material_unknown' => $request->cpi_drain_material_unknown,
        'domain_2_score' => $domain2Score,
        'domain_2_notes' => $request->domain_2_notes,
        
        // ===== DOMAIN 3 =====
        'building_age_calculated' => $buildingAge,
        'cpi_fixture_age' => $request->cpi_fixture_age,
        'cpi_systems_documented' => $request->cpi_systems_documented,
        'cpi_age_score_harmonised' => $domain3Score,
        'domain_3_score' => $domain3Score,
        'domain_3_notes' => $request->domain_3_notes,
        
        // ===== DOMAIN 4 WITH SNAPSHOTS =====
        'cpi_containment_category_id' => $containmentCategory->id,
        'cpi_containment_category_name' => $containmentCategory->category_name, // SNAPSHOT
        'cpi_containment_score' => $containmentCategory->score_points, // SNAPSHOT
        'domain_4_score' => $domain4Score,
        'domain_4_notes' => $request->domain_4_notes,
        
        // ===== DOMAIN 5 WITH SNAPSHOTS =====
        'cpi_crawl_access_id' => $crawlAccess->id,
        'cpi_crawl_access_name' => $crawlAccess->category_name, // SNAPSHOT
        'cpi_crawl_access_score' => $crawlAccess->score_points, // SNAPSHOT
        
        'cpi_roof_access_id' => $roofAccess->id,
        'cpi_roof_access_name' => $roofAccess->category_name, // SNAPSHOT
        'cpi_roof_access_score' => $roofAccess->score_points, // SNAPSHOT
        
        'cpi_equipment_requirement_id' => $equipmentRequirement->id,
        'cpi_equipment_requirement_name' => $equipmentRequirement->requirement_name, // SNAPSHOT
        'cpi_equipment_requirement_score' => $equipmentRequirement->score_points, // SNAPSHOT
        
        'cpi_time_to_access' => $request->cpi_time_to_access,
        'cpi_accessibility_score_capped' => $domain5Score,
        'domain_5_score' => $domain5Score,
        'domain_5_notes' => $request->domain_5_notes,
        
        // ===== DOMAIN 6 WITH SNAPSHOTS =====
        'cpi_complexity_category_id' => $complexityCategory->id,
        'cpi_complexity_category_name' => $complexityCategory->category_name, // SNAPSHOT
        'cpi_complexity_score' => $complexityCategory->score_points, // SNAPSHOT
        'domain_6_score' => $domain6Score,
        'domain_6_notes' => $request->domain_6_notes,
        
        // ===== CPI OUTPUTS (IMMUTABLE SNAPSHOTS) =====
        'cpi_total_score' => $cpiTotalScore,
        'cpi_band' => $cpiBandRange->band_code,
        'cpi_multiplier' => $cpiMultiplier->multiplier,
        'cpi_band_range_snapshot' => "{$cpiBandRange->min_score}-{$cpiBandRange->max_score} points",
        'cpi_band_name_snapshot' => $cpiBandRange->band_name,
        
        // ===== SIZE FACTORS (SNAPSHOTS) =====
        'residential_size_factor' => $sizeFactor['residential'] ?? null,
        'commercial_size_factor' => $sizeFactor['commercial'] ?? null,
        'harmonised_size_factor' => $sizeFactor['harmonised'],
        
        // ===== FINAL PRICING (IMMUTABLE SNAPSHOTS) =====
        'final_monthly_cost' => round($finalMonthly, 2),
        'final_annual_cost' => round($finalAnnual, 2),
        
        // ===== ASSESSMENT =====
        'overall_condition' => $request->overall_condition,
        'inspector_notes' => $request->inspector_notes,
        'recommendations' => $request->recommendations,
        'risk_summary' => $request->risk_summary,
        'photo_notes' => $request->photo_notes,
        
        // ===== STATUS =====
        'status' => $request->status === 'completed' ? 'completed' : 'in_progress',
        'completed_date' => $request->status === 'completed' ? now() : null,
    ]);
    
    // Handle photo uploads
    if ($request->hasFile('inspection_photos')) {
        $photos = [];
        foreach ($request->file('inspection_photos') as $photo) {
            $path = $photo->store('inspections/' . $inspection->id, 'public');
            $photos[] = $path;
        }
        $inspection->update(['photos' => $photos]);
    }
    
    // Generate PDF report if completed
    if ($inspection->status === 'completed') {
        $this->generateInspectionReport($inspection);
    }
    
    return redirect()->route('inspections.show', $inspection)
        ->with('success', 'Inspection saved successfully!');
}
```

---

## 📄 Report Generation

### **Generate PDF Report After Completion:**

```php
use Barryvdh\DomPDF\Facade\Pdf;

protected function generateInspectionReport(Inspection $inspection)
{
    $pdf = Pdf::loadView('admin.inspections.report', compact('inspection'));
    
    $filename = "inspection-{$inspection->property_code}-" . now()->format('Y-m-d') . ".pdf";
    $path = "reports/inspections/{$inspection->id}/{$filename}";
    
    Storage::disk('public')->put($path, $pdf->output());
    
    $inspection->update(['report_file' => $path]);
    
    // Optionally email report to client
    Mail::to($inspection->owner_email)->send(new InspectionReportMail($inspection));
    
    return $path;
}
```

### **Report Blade Template:** `resources/views/admin/inspections/report.blade.php`

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Property Inspection Report - {{ $inspection->property_code }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { background: #5b67ca; color: white; padding: 20px; text-align: center; }
        .section { margin: 20px 0; page-break-inside: avoid; }
        .section-title { background: #f0f0f0; padding: 10px; font-weight: bold; border-left: 4px solid #5b67ca; }
        .grid { display: table; width: 100%; border-collapse: collapse; }
        .grid-row { display: table-row; }
        .grid-cell { display: table-cell; padding: 8px; border: 1px solid #ddd; }
        .grid-label { font-weight: bold; width: 40%; background: #f9f9f9; }
        .score-badge { display: inline-block; padding: 5px 10px; border-radius: 4px; font-weight: bold; }
        .score-cpi-0 { background: #d4edda; color: #155724; }
        .score-cpi-1 { background: #d1ecf1; color: #0c5460; }
        .score-cpi-2 { background: #fff3cd; color: #856404; }
        .score-cpi-3 { background: #f8d7da; color: #721c24; }
        .score-cpi-4 { background: #f5c6cb; color: #721c24; }
        .pricing-box { background: #e8f5e9; padding: 15px; border: 2px solid #4caf50; border-radius: 8px; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>PROPERTY INSPECTION REPORT</h1>
        <p>{{ $inspection->property_name }} ({{ $inspection->property_code }})</p>
        <p>Inspection Date: {{ $inspection->inspection_date->format('F d, Y') }}</p>
    </div>

    <!-- Property Details -->
    <div class="section">
        <div class="section-title">Property Information</div>
        <div class="grid">
            <div class="grid-row">
                <div class="grid-cell grid-label">Property Owner</div>
                <div class="grid-cell">{{ $inspection->owner_name }}</div>
                <div class="grid-cell grid-label">Contact Email</div>
                <div class="grid-cell">{{ $inspection->owner_email }}</div>
            </div>
            <div class="grid-row">
                <div class="grid-cell grid-label">Property Type</div>
                <div class="grid-cell">{{ ucfirst($inspection->property_type_snapshot) }}</div>
                <div class="grid-cell grid-label">Year Built</div>
                <div class="grid-cell">{{ $inspection->property_year_built }} ({{ $inspection->building_age_calculated }} years old)</div>
            </div>
            <div class="grid-row">
                <div class="grid-cell grid-label">Address</div>
                <div class="grid-cell" colspan="3">{{ $inspection->property_address_snapshot }}</div>
            </div>
            @if($inspection->residential_units_snapshot)
            <div class="grid-row">
                <div class="grid-cell grid-label">Residential Units</div>
                <div class="grid-cell">{{ $inspection->residential_units_snapshot }} units</div>
            </div>
            @endif
            @if($inspection->commercial_sqft_snapshot)
            <div class="grid-row">
                <div class="grid-cell grid-label">Commercial SqFt</div>
                <div class="grid-cell">{{ number_format($inspection->commercial_sqft_snapshot) }} sq ft</div>
            </div>
            @endif
        </div>
    </div>

    <!-- CPI Scoring Summary -->
    <div class="section">
        <div class="section-title">CPI Scoring Summary</div>
        <div class="grid">
            <div class="grid-row">
                <div class="grid-cell grid-label">Domain 1: System Design & Pressure</div>
                <div class="grid-cell">{{ $inspection->domain_1_score }} / 7 points</div>
            </div>
            <div class="grid-row">
                <div class="grid-cell grid-label">Domain 2: Material Risk</div>
                <div class="grid-cell">{{ $inspection->domain_2_score }} / 5 points</div>
            </div>
            <div class="grid-row">
                <div class="grid-cell grid-label">Domain 3: Age & Lifecycle</div>
                <div class="grid-cell">{{ $inspection->domain_3_score }} / 5 points</div>
            </div>
            <div class="grid-row">
                <div class="grid-cell grid-label">Domain 4: Access & Containment</div>
                <div class="grid-cell">{{ $inspection->domain_4_score }} / 3 points</div>
            </div>
            <div class="grid-row">
                <div class="grid-cell grid-label">Domain 5: Accessibility & Safety</div>
                <div class="grid-cell">{{ $inspection->domain_5_score }} / 4 points</div>
            </div>
            <div class="grid-row">
                <div class="grid-cell grid-label">Domain 6: Operational Complexity</div>
                <div class="grid-cell">{{ $inspection->domain_6_score }} / 3 points</div>
            </div>
            <div class="grid-row" style="background: #f0f0f0; font-weight: bold;">
                <div class="grid-cell grid-label">TOTAL CPI SCORE</div>
                <div class="grid-cell">{{ $inspection->cpi_total_score }} points</div>
            </div>
            <div class="grid-row">
                <div class="grid-cell grid-label">CPI Band</div>
                <div class="grid-cell">
                    <span class="score-badge score-{{ strtolower($inspection->cpi_band) }}">
                        {{ $inspection->cpi_band }}: {{ $inspection->cpi_band_name_snapshot }}
                    </span>
                </div>
            </div>
            <div class="grid-row">
                <div class="grid-cell grid-label">CPI Multiplier</div>
                <div class="grid-cell">{{ $inspection->cpi_multiplier }}x</div>
            </div>
        </div>
    </div>

    <!-- Pricing Calculation -->
    <div class="section">
        <div class="section-title">Service Pricing</div>
        <div class="pricing-box">
            <div class="grid">
                <div class="grid-row">
                    <div class="grid-cell grid-label">Service Package</div>
                    <div class="grid-cell">{{ $inspection->service_package_name }}</div>
                </div>
                <div class="grid-row">
                    <div class="grid-cell grid-label">Base Price (Monthly)</div>
                    <div class="grid-cell">${{ number_format($inspection->base_price_snapshot, 2) }}</div>
                </div>
                <div class="grid-row">
                    <div class="grid-cell grid-label">Size Factor</div>
                    <div class="grid-cell">{{ $inspection->harmonised_size_factor }}x</div>
                </div>
                <div class="grid-row">
                    <div class="grid-cell grid-label">CPI Multiplier</div>
                    <div class="grid-cell">{{ $inspection->cpi_multiplier }}x</div>
                </div>
                <div class="grid-row" style="background: #4caf50; color: white; font-size: 14px; font-weight: bold;">
                    <div class="grid-cell grid-label">FINAL MONTHLY COST</div>
                    <div class="grid-cell">${{ number_format($inspection->final_monthly_cost, 2) }}</div>
                </div>
                <div class="grid-row" style="background: #388e3c; color: white; font-size: 14px; font-weight: bold;">
                    <div class="grid-cell grid-label">FINAL ANNUAL COST</div>
                    <div class="grid-cell">${{ number_format($inspection->final_annual_cost, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-break"></div>

    <!-- Domain Details -->
    <div class="section">
        <div class="section-title">Domain 1: System Design & Pressure</div>
        <div class="grid">
            <div class="grid-row">
                <div class="grid-cell grid-label">Unit-level water shut-offs present?</div>
                <div class="grid-cell">{{ ucfirst($inspection->cpi_unit_shutoffs) }}</div>
            </div>
            <div class="grid-row">
                <div class="grid-cell grid-label">Shared risers impacting multiple units?</div>
                <div class="grid-cell">{{ ucfirst($inspection->cpi_shared_risers) }}</div>
            </div>
            <div class="grid-row">
                <div class="grid-cell grid-label">Static water pressure (PSI)</div>
                <div class="grid-cell">{{ $inspection->cpi_static_pressure }} PSI</div>
            </div>
            <div class="grid-row">
                <div class="grid-cell grid-label">Isolation zones present?</div>
                <div class="grid-cell">{{ ucfirst($inspection->cpi_isolation_zones) }}</div>
            </div>
        </div>
        @if($inspection->domain_1_notes)
        <p><strong>Notes:</strong> {{ $inspection->domain_1_notes }}</p>
        @endif
    </div>

    <!-- Repeat for other domains... -->

    <!-- Overall Assessment -->
    <div class="section">
        <div class="section-title">Overall Assessment</div>
        <p><strong>Overall Condition:</strong> {{ ucfirst($inspection->overall_condition) }}</p>
        @if($inspection->inspector_notes)
        <p><strong>Inspector Notes:</strong> {{ $inspection->inspector_notes }}</p>
        @endif
        @if($inspection->recommendations)
        <p><strong>Recommendations:</strong> {{ $inspection->recommendations }}</p>
        @endif
        @if($inspection->risk_summary)
        <p><strong>Risk Summary:</strong> {{ $inspection->risk_summary }}</p>
        @endif
    </div>

    <!-- Photos -->
    @if($inspection->photos)
    <div class="section">
        <div class="section-title">Inspection Photos</div>
        @foreach($inspection->photos as $photo)
            <img src="{{ Storage::url($photo) }}" style="max-width: 45%; margin: 10px;">
        @endforeach
    </div>
    @endif

    <!-- Footer -->
    <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
        <p><strong>Inspector:</strong> {{ $inspection->inspector->name }}</p>
        <p><strong>Inspection Date:</strong> {{ $inspection->inspection_date->format('F d, Y') }}</p>
        <p><strong>Report Generated:</strong> {{ now()->format('F d, Y H:i:s') }}</p>
    </div>
</body>
</html>
```

---

## ✅ Benefits of This Approach

1. **✅ Data Immutability**: Historical inspections NEVER change even if settings are updated
2. **✅ Audit Trail**: Complete snapshot of all values at inspection time
3. **✅ Report Consistency**: Regenerating report 5 years later shows exact same data
4. **✅ Legal Protection**: Original calculations preserved for disputes
5. **✅ Settings Flexibility**: Admin can update CPI settings without fear of breaking history
6. **✅ Client Reports**: Each client gets PDF report for their specific property with immutable pricing

---

## 🎯 Implementation Checklist

- [ ] Run migration to add CPI columns to inspections table
- [ ] Update Inspection model with fillable fields and casts
- [ ] Create InspectionController store method with snapshot logic
- [ ] Update inspection form blade to include all CPI inputs
- [ ] Install dompdf package: `composer require barryvdh/laravel-dompdf`
- [ ] Create report blade template
- [ ] Add generateInspectionReport method
- [ ] Test: Create inspection, change CPI settings, verify old inspection unchanged
- [ ] Test: Generate PDF report for completed inspection
- [ ] Add email notification with report attachment

This ensures your system is **future-proof** and **audit-ready**!
