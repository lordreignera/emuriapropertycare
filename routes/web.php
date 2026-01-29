<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CheckoutController;

Route::get('/', function () {
    return redirect('/home/index.html');
});

// Custom logout route that redirects to login
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login')->with('status', 'You have been logged out successfully.');
})->name('logout');

// Client Registration (Free - No Tier Selection)
Route::get('/register', function () {
    return view('auth.register');
})->name('register');

// Checkout Process (for custom products after inspection)
Route::post('/checkout/process', [CheckoutController::class, 'processCheckout'])->name('checkout.process');
Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success')->middleware('auth');
Route::get('/checkout/cancel', [CheckoutController::class, 'cancel'])->name('checkout.cancel');

// Subscription Required Page
Route::get('/subscription-required', function() {
    return view('subscription-required');
})->middleware('auth')->name('subscription.required');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'check.subscription',
])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    
    // Main workflow routes
    Route::resource('properties', App\Http\Controllers\PropertyController::class);
    Route::post('/properties/{property}/approve', [App\Http\Controllers\PropertyController::class, 'approve'])->name('properties.approve');
    Route::post('/properties/{property}/reject', [App\Http\Controllers\PropertyController::class, 'reject'])->name('properties.reject');
    Route::post('/properties/{property}/assign', [App\Http\Controllers\PropertyController::class, 'assign'])->name('properties.assign');
    Route::resource('inspections', App\Http\Controllers\InspectionController::class);
    Route::resource('projects', App\Http\Controllers\ProjectController::class);
    Route::resource('invoices', App\Http\Controllers\InvoiceController::class);
    Route::resource('work-logs', App\Http\Controllers\WorkLogController::class);
    Route::resource('milestones', App\Http\Controllers\MilestoneController::class);
    Route::resource('budgets', App\Http\Controllers\BudgetController::class);
    Route::resource('change-orders', App\Http\Controllers\ChangeOrderController::class);
    Route::resource('communications', App\Http\Controllers\CommunicationController::class);
    
    // Reports & Savings
    Route::prefix('reports')->name('reports.')->group(function() {
        Route::get('/', function() { return view('reports.index'); })->name('index');
        Route::get('/performance', function() { return view('reports.performance'); })->name('performance');
        Route::get('/financial', function() { return view('reports.financial'); })->name('financial');
    });
    
    Route::prefix('savings')->name('savings.')->group(function() {
        Route::get('/', function() { return view('savings.index'); })->name('index');
        Route::get('/analysis', function() { return view('savings.analysis'); })->name('analysis');
    });
    
    // Subscription management
    Route::get('/subscription', function() {
        return view('admin.subscription');
    })->name('subscription.show');
    
    // Search
    Route::get('/search', function() {
        return redirect()->route('dashboard');
    })->name('search');
    
    // Notifications
    Route::get('/notifications', function() {
        return view('admin.notifications');
    })->name('notifications.index');
    
    // Client routes - No role middleware needed (FREE access model)
    Route::prefix('client')->name('client.')->group(function() {
        // Properties
        Route::resource('properties', App\Http\Controllers\Client\PropertyController::class);
        
        // Tenants
        Route::resource('tenants', App\Http\Controllers\Client\TenantController::class);
        Route::get('/tenants/export', [App\Http\Controllers\Client\TenantController::class, 'export'])->name('tenants.export');
        Route::get('/tenants/property-password/{property}', [App\Http\Controllers\Client\TenantController::class, 'getPropertyPassword'])->name('tenants.property-password');
        
        // Inspections
        Route::get('/inspections', function() {
            return view('client.inspections.index');
        })->name('inspections.index');

        // Schedule & pay for inspection
        Route::get('/inspections/{property}/schedule', [App\Http\Controllers\Client\InspectionController::class, 'scheduleCreate'])
            ->name('inspections.schedule');
        Route::post('/inspections/{property}/schedule', [App\Http\Controllers\Client\InspectionController::class, 'scheduleStore'])
            ->name('inspections.store-schedule');
        Route::get('/inspections/checkout-success', [App\Http\Controllers\Client\InspectionController::class, 'checkoutSuccess'])
            ->name('inspections.checkout-success');
        Route::get('/inspections/checkout-cancel', [App\Http\Controllers\Client\InspectionController::class, 'checkoutCancel'])
            ->name('inspections.checkout-cancel');
        
        // Projects
        Route::get('/projects', function() {
            return view('client.projects.index');
        })->name('projects.index');
        
        // Invoices
        Route::get('/invoices', function() {
            return view('client.invoices.index');
        })->name('invoices.index');
        
        // Subscription
        Route::get('/subscription', function() {
            return view('client.subscription');
        })->name('subscription.show');
        
        // Complaints
        Route::get('/complaints', function() {
            return view('client.complaints.index');
        })->name('complaints.index');
        
        // Emergency Reports
        Route::get('/emergency-reports', function() {
            return view('client.emergency-reports.index');
        })->name('emergency-reports.index');
        
        // Support
        Route::get('/support', function() {
            return view('client.support');
        })->name('support');
    });
    
    // Admin routes
    Route::prefix('admin')->name('admin.')->group(function() {
        // Access Control
        Route::resource('users', App\Http\Controllers\Admin\UserManagementController::class);
        Route::post('users/{user}/assign-role', [App\Http\Controllers\Admin\UserManagementController::class, 'assignRole'])->name('users.assign-role');
        Route::delete('users/{user}/remove-role/{role}', [App\Http\Controllers\Admin\UserManagementController::class, 'removeRole'])->name('users.remove-role');
        
        Route::resource('roles', App\Http\Controllers\Admin\RoleManagementController::class);
        Route::post('roles/{role}/assign-permission', [App\Http\Controllers\Admin\RoleManagementController::class, 'assignPermission'])->name('roles.assign-permission');
        Route::delete('roles/{role}/remove-permission/{permission}', [App\Http\Controllers\Admin\RoleManagementController::class, 'removePermission'])->name('roles.remove-permission');
        
        Route::resource('permissions', App\Http\Controllers\Admin\PermissionManagementController::class);
        Route::post('permissions/{permission}/assign-role', [App\Http\Controllers\Admin\PermissionManagementController::class, 'assignToRole'])->name('permissions.assign-role');
        Route::delete('permissions/{permission}/remove-role/{role}', [App\Http\Controllers\Admin\PermissionManagementController::class, 'removeFromRole'])->name('permissions.remove-role');
        
        // Product Management (replaces Tier Management)
        Route::resource('products', App\Http\Controllers\Admin\ProductManagementController::class);
        Route::post('products/{product}/components', [App\Http\Controllers\Admin\ProductManagementController::class, 'addComponent'])->name('products.add-component');
        Route::post('products/{product}/recalculate', [App\Http\Controllers\Admin\ProductManagementController::class, 'recalculateComponents'])->name('products.recalculate');
        Route::post('products/{product}/toggle-status', [App\Http\Controllers\Admin\ProductManagementController::class, 'toggleStatus'])->name('products.toggle-status');
        Route::post('products/{product}/duplicate', [App\Http\Controllers\Admin\ProductManagementController::class, 'duplicate'])->name('products.duplicate');
        
        // CPI Pricing System Management
        Route::resource('pricing-packages', App\Http\Controllers\Admin\PricingPackageController::class)->names('pricing-packages');
        Route::resource('property-types', App\Http\Controllers\Admin\PropertyTypeController::class)->names('property-types');
        Route::resource('cpi-bands', App\Http\Controllers\Admin\CpiBandController::class)->names('cpi-bands');
        Route::resource('cpi-multipliers', App\Http\Controllers\Admin\CpiMultiplierController::class)->names('cpi-multipliers');
        Route::resource('cpi-domains', App\Http\Controllers\Admin\CpiDomainController::class)->names('cpi-domains');
        
        // CPI Scoring Factors (nested under domains)
        Route::get('cpi-domains/{cpiDomain}/factors/create', [App\Http\Controllers\Admin\CpiDomainController::class, 'createFactor'])->name('cpi-domains.factors.create');
        Route::post('cpi-domains/{cpiDomain}/factors', [App\Http\Controllers\Admin\CpiDomainController::class, 'storeFactor'])->name('cpi-domains.factors.store');
        Route::get('cpi-domains/{cpiDomain}/factors/{factor}/edit', [App\Http\Controllers\Admin\CpiDomainController::class, 'editFactor'])->name('cpi-domains.factors.edit');
        Route::put('cpi-domains/{cpiDomain}/factors/{factor}', [App\Http\Controllers\Admin\CpiDomainController::class, 'updateFactor'])->name('cpi-domains.factors.update');
        Route::delete('cpi-domains/{cpiDomain}/factors/{factor}', [App\Http\Controllers\Admin\CpiDomainController::class, 'destroyFactor'])->name('cpi-domains.factors.destroy');
        
        Route::resource('supply-materials', App\Http\Controllers\Admin\SupplyMaterialController::class)->names('supply-materials');
        Route::resource('age-brackets', App\Http\Controllers\Admin\AgeBracketController::class)->names('age-brackets');
        Route::resource('containment-categories', App\Http\Controllers\Admin\ContainmentCategoryController::class)->names('containment-categories');
        Route::resource('crawl-access', App\Http\Controllers\Admin\CrawlAccessController::class)->names('crawl-access');
        Route::resource('roof-access', App\Http\Controllers\Admin\RoofAccessController::class)->names('roof-access');
        Route::resource('equipment-requirements', App\Http\Controllers\Admin\EquipmentRequirementController::class)->names('equipment-requirements');
        Route::resource('complexity-categories', App\Http\Controllers\Admin\ComplexityCategoryController::class)->names('complexity-categories');
        Route::resource('residential-tiers', App\Http\Controllers\Admin\ResidentialTierController::class)->names('residential-tiers');
        Route::resource('commercial-settings', App\Http\Controllers\Admin\CommercialSettingController::class)->names('commercial-settings');
        Route::resource('mixed-use-settings', App\Http\Controllers\Admin\MixedUseSettingController::class)->names('mixed-use-settings');
        Route::resource('pricing-config', App\Http\Controllers\Admin\PricingConfigController::class)->names('pricing-config');
        Route::resource('reactive-costs', App\Http\Controllers\Admin\ReactiveCostAssumptionController::class)->names('reactive-costs');
        Route::resource('stewardship-loss', App\Http\Controllers\Admin\StewardshipLossReductionController::class)->names('stewardship-loss');
        
        // Reports
        Route::get('/reports', function() {
            return view('admin.reports.index');
        })->name('reports.index');
    });
});
