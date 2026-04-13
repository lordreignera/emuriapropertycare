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
    Route::get('/inspections/{inspection}/download-invoice', [App\Http\Controllers\InspectionController::class, 'downloadInvoice'])->name('inspections.download-invoice');
    Route::get('/inspections/{inspection}/work-payment', [App\Http\Controllers\InspectionController::class, 'workPayment'])->name('inspections.work-payment');
    Route::post('/inspections/{inspection}/work-payment', [App\Http\Controllers\InspectionController::class, 'processWorkPayment'])->name('inspections.process-work-payment');
    Route::get('/inspections/{inspection}/phar-data', [App\Http\Controllers\InspectionController::class, 'pharData'])->name('inspections.phar-data');
    Route::post('/inspections/{inspection}/store-phar-data', [App\Http\Controllers\InspectionController::class, 'storePharData'])->name('inspections.store-phar-data');
    Route::post('/inspections/{inspection}/complete-assessment', [App\Http\Controllers\InspectionController::class, 'completeAssessment'])->name('inspections.complete-assessment');
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
        Route::get('/inspections', [App\Http\Controllers\Client\InspectionController::class, 'index'])
            ->name('inspections.index');
        Route::get('/inspections/{inspection}/report', [App\Http\Controllers\Client\InspectionController::class, 'report'])
            ->name('inspections.report');
        Route::get('/inspections/{inspection}/agreement', [App\Http\Controllers\Client\InspectionController::class, 'agreement'])
            ->name('inspections.agreement');
        Route::get('/inspections/{inspection}/agreement/download', [App\Http\Controllers\Client\InspectionController::class, 'downloadAgreementPdf'])
            ->name('inspections.agreement.download');
        Route::post('/inspections/{inspection}/agreement/sign', [App\Http\Controllers\Client\InspectionController::class, 'signAgreement'])
            ->name('inspections.agreement.sign');
        Route::post('/inspections/{inspection}/findings/{findingIndex}/photos', [App\Http\Controllers\Client\InspectionController::class, 'addFindingPhotos'])
            ->name('inspections.findings.add-photos');
        Route::get('/inspections/{inspection}/work-payment', [App\Http\Controllers\Client\InspectionController::class, 'workPayment'])
            ->name('inspections.work-payment');
        Route::post('/inspections/{inspection}/work-payment', [App\Http\Controllers\Client\InspectionController::class, 'processWorkPayment'])
            ->name('inspections.process-work-payment');

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
        Route::get('/invoices', [App\Http\Controllers\Client\InvoiceController::class, 'index'])
            ->name('invoices.index');
        Route::get('/invoices/{invoice}/download', [App\Http\Controllers\Client\InvoiceController::class, 'download'])
            ->name('invoices.download');
        Route::get('/invoices/{invoice}', [App\Http\Controllers\Client\InvoiceController::class, 'show'])
            ->name('invoices.show');
        
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
        
        // Pricing System Management
        Route::resource('property-types', App\Http\Controllers\Admin\PropertyTypeController::class)->names('property-types');
        Route::resource('equipment-requirements', App\Http\Controllers\Admin\EquipmentRequirementController::class)->names('equipment-requirements');
        Route::resource('complexity-categories', App\Http\Controllers\Admin\ComplexityCategoryController::class)->names('complexity-categories');
        Route::resource('residential-tiers', App\Http\Controllers\Admin\ResidentialTierController::class)->names('residential-tiers');
        Route::resource('commercial-settings', App\Http\Controllers\Admin\CommercialSettingController::class)->names('commercial-settings');
        Route::resource('pricing-config', App\Http\Controllers\Admin\PricingConfigController::class)->names('pricing-config');
        Route::post('parameters/reload-defaults', [App\Http\Controllers\Admin\ParameterController::class, 'reloadDefaults'])->name('parameters.reload-defaults');
        Route::resource('parameters', App\Http\Controllers\Admin\ParameterController::class)->except(['show'])->names('parameters');
        Route::post('fmc-material-settings/reload-defaults', [App\Http\Controllers\Admin\FmcMaterialSettingController::class, 'reloadDefaults'])->name('fmc-material-settings.reload-defaults');
        Route::resource('fmc-material-settings', App\Http\Controllers\Admin\FmcMaterialSettingController::class)->except(['show'])->names('fmc-material-settings');
        Route::post('finding-template-settings/reload-defaults', [App\Http\Controllers\Admin\FindingTemplateSettingController::class, 'reloadDefaults'])->name('finding-template-settings.reload-defaults');
        Route::resource('finding-template-settings', App\Http\Controllers\Admin\FindingTemplateSettingController::class)->except(['show'])->names('finding-template-settings');
        Route::post('recommendation-settings/reload-defaults', [App\Http\Controllers\Admin\RecommendationSettingController::class, 'reloadDefaults'])->name('recommendation-settings.reload-defaults');
        Route::resource('recommendation-settings', App\Http\Controllers\Admin\RecommendationSettingController::class)->except(['show'])->names('recommendation-settings');
        Route::resource('tool-settings', App\Http\Controllers\Admin\ToolSettingController::class)->except(['show'])->names('tool-settings');
        Route::resource('systems', App\Http\Controllers\Admin\SystemController::class)->except(['show'])->names('systems');
        Route::resource('subsystems', App\Http\Controllers\Admin\SubsystemController::class)->except(['show'])->names('subsystems');
        
        // BDC Calibration Engine Settings
        Route::get('settings/bdc', [App\Http\Controllers\Admin\BDCSettingsController::class, 'index'])->name('settings.bdc');
        Route::put('settings/bdc', [App\Http\Controllers\Admin\BDCSettingsController::class, 'update'])->name('settings.bdc.update');
        Route::post('settings/bdc/preview', [App\Http\Controllers\Admin\BDCSettingsController::class, 'preview'])->name('settings.bdc.preview');
        Route::post('settings/bdc/reset', [App\Http\Controllers\Admin\BDCSettingsController::class, 'reset'])->name('settings.bdc.reset');
        
        // Reports
        Route::get('/reports', function() {
            return view('admin.reports.index');
        })->name('reports.index');
    });
});
