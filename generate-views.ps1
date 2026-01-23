# PowerShell script to generate all remaining CPI pricing system views
# Run: .\generate-views.ps1

$tables = @(
    @{
        Route = "property-types"
        Title = "Property Types"
        Variable = "propertyTypes"
        Singular = "propertyType"
        Columns = @("name", "slug", "uses_residential_pricing", "uses_commercial_pricing")
    },
    @{
        Route = "cpi-multipliers"
        Title = "CPI Multipliers"
        Variable = "multipliers"
        Singular = "cpiMultiplier"
        Columns = @("cpiBand.band_name", "multiplier_value")
        Relation = "cpiBand"
    },
    @{
        Route = "supply-materials"
        Title = "Supply Line Materials"
        Variable = "materials"
        Singular = "supplyMaterial"
        Columns = @("material_name", "cpiDomain.name", "score_value")
        Relation = "cpiDomain"
    },
    @{
        Route = "age-brackets"
        Title = "Age Brackets"
        Variable = "ageBrackets"
        Singular = "ageBracket"
        Columns = @("bracket_name", "min_age", "max_age", "score_value")
        Relation = "cpiDomain"
    },
    @{
        Route = "containment-categories"
        Title = "Containment Categories"
        Variable = "categories"
        Singular = "containmentCategory"
        Columns = @("category_name", "cpiDomain.name", "score_value")
        Relation = "cpiDomain"
    },
    @{
        Route = "crawl-access"
        Title = "Crawl Space Access"
        Variable = "categories"
        Singular = "crawlAccess"
        Columns = @("category_name", "cpiDomain.name", "score_value")
        Relation = "cpiDomain"
    },
    @{
        Route = "roof-access"
        Title = "Roof Access"
        Variable = "categories"
        Singular = "roofAccess"
        Columns = @("category_name", "cpiDomain.name", "score_value")
        Relation = "cpiDomain"
    },
    @{
        Route = "equipment-requirements"
        Title = "Equipment Requirements"
        Variable = "equipment"
        Singular = "equipmentRequirement"
        Columns = @("equipment_name", "cpiDomain.name", "score_value")
        Relation = "cpiDomain"
    },
    @{
        Route = "complexity-categories"
        Title = "Complexity Categories"
        Variable = "categories"
        Singular = "complexityCategory"
        Columns = @("category_name", "cpiDomain.name", "score_value")
        Relation = "cpiDomain"
    },
    @{
        Route = "residential-tiers"
        Title = "Residential Size Tiers"
        Variable = "tiers"
        Singular = "residentialTier"
        Columns = @("tier_name", "min_units", "max_units", "size_factor")
    },
    @{
        Route = "commercial-settings"
        Title = "Commercial Size Settings"
        Variable = "settings"
        Singular = "commercialSetting"
        Columns = @("setting_name", "setting_key", "setting_value")
    },
    @{
        Route = "mixed-use-settings"
        Title = "Mixed-Use Settings"
        Variable = "settings"
        Singular = "mixedUseSetting"
        Columns = @("setting_name", "setting_key", "setting_value")
    },
    @{
        Route = "pricing-config"
        Title = "Pricing System Configuration"
        Variable = "configs"
        Singular = "pricingConfig"
        Columns = @("config_key", "config_value", "value_type")
    }
)

$baseDir = "resources\views\admin\pricing-system"

foreach ($table in $tables) {
    $dir = Join-Path $baseDir $table.Route
    New-Item -ItemType Directory -Force -Path $dir | Out-Null
    
    # Create index.blade.php
    $indexContent = @"
@extends('admin.layout.app')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">$($table.Title)</h4>
                    <a href="{{ route('admin.$($table.Route).create') }}" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-plus"></i> Add New
                    </a>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Details</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(`$$($table.Variable) as `$$($table.Singular))
                                <tr>
                                    <td>
                                        <strong>{{ `$$($table.Singular)->$($table.Columns[0]) }}</strong>
                                    </td>
                                    <td>
                                        @if(`$$($table.Singular)->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.$($table.Route).edit', `$$($table.Singular)) }}" class="btn btn-sm btn-warning">
                                            <i class="mdi mdi-pencil"></i> Edit
                                        </a>
                                        <form action="{{ route('admin.$($table.Route).destroy', `$$($table.Singular)) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="mdi mdi-delete"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4">
                                        <p class="text-muted">No records found.</p>
                                        <a href="{{ route('admin.$($table.Route).create') }}" class="btn btn-primary btn-sm">
                                            <i class="mdi mdi-plus"></i> Create First Record
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
"@
    
    Set-Content -Path (Join-Path $dir "index.blade.php") -Value $indexContent
    Write-Host "Created index view for $($table.Title)" -ForegroundColor Green
}

Write-Host "`nAll views generated successfully!" -ForegroundColor Cyan
