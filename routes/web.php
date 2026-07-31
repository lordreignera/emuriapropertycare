<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CheckoutController;

Route::get('/', function () {
    return redirect('/home/index.html');
});

Route::get('/trade/register', [App\Http\Controllers\TradeApplicationController::class, 'create'])
    ->name('trade-applications.create');
Route::post('/trade/register', [App\Http\Controllers\TradeApplicationController::class, 'store'])
    ->name('trade-applications.store');
Route::get('/trade/register/thank-you/{tradeApplication}', [App\Http\Controllers\TradeApplicationController::class, 'thankYou'])
    ->name('trade-applications.thank-you');

// Custom logout route that redirects to login
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login')->with('status', 'You have been logged out successfully.');
})->name('logout');

// ── Stripe Webhooks (CSRF-exempt, signed by Stripe) ────────────────────────
Route::post('/stripe/webhook', [App\Http\Controllers\StripeWebhookController::class, 'handle'])
    ->name('stripe.webhook')
    ->withoutMiddleware('Illuminate\Foundation\Http\Middleware\VerifyCsrfToken');

// Client Registration (Free - No Tier Selection)
Route::get('/register', function () {
    return view('auth.register');
})->name('register');

// Checkout Process
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
    $notificationIndexHandler = function() {
        $user = auth()->user();

        return view('admin.notifications', [
            'notifications' => $user->notifications()->latest()->limit(100)->get(),
            'unreadNotificationsCount' => $user->unreadNotifications()->count(),
        ]);
    };

    $notificationOpenHandler = function(string $notification) {
        $user = auth()->user();
        $notificationRecord = $user->notifications()->findOrFail($notification);

        if ($notificationRecord->read_at === null) {
            $notificationRecord->markAsRead();
        }

        $fallbackRoute = $user->hasRole('Client') ? 'client.notifications.index' : 'notifications.index';

        return redirect($notificationRecord->data['action_url'] ?? route($fallbackRoute));
    };

    $notificationReadAllHandler = function() {
        $user = auth()->user();
        $user->unreadNotifications->markAsRead();

        return back()->with('success', 'Notifications marked as read.');
    };

    $registerNotificationRoutes = function() use (
        $notificationIndexHandler,
        $notificationOpenHandler,
        $notificationReadAllHandler
    ) {
        Route::get('/notifications', $notificationIndexHandler)->name('notifications.index');
        Route::get('/notifications/{notification}', $notificationOpenHandler)->name('notifications.open');
        Route::post('/notifications/read-all', $notificationReadAllHandler)->name('notifications.read-all');
    };

    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // ── Profile & Settings (all authenticated users) ────────────────────────
    Route::get('/settings',               [App\Http\Controllers\ProfileController::class, 'show'])->name('profile.settings');
    Route::put('/settings/profile',       [App\Http\Controllers\ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::put('/settings/password',      [App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/settings/photo',        [App\Http\Controllers\ProfileController::class, 'uploadPhoto'])->name('profile.photo');
    Route::post('/settings/signature',    [App\Http\Controllers\ProfileController::class, 'uploadSignature'])->name('profile.signature');
    Route::delete('/settings/signature',  [App\Http\Controllers\ProfileController::class, 'removeSignature'])->name('profile.signature.remove');
    // ────────────────────────────────────────────────────────────────────────
    
    // Main workflow routes
    Route::resource('properties', App\Http\Controllers\PropertyController::class);
    Route::prefix('properties/{property}')->name('properties.')->group(function () {
        Route::post('/assign', [App\Http\Controllers\PropertyController::class, 'assign'])
            ->name('assign')
            ->middleware('role:Super Admin|Administrator|Project Manager');
        Route::post('/diagnosis-invoice', [App\Http\Controllers\PropertyController::class, 'createDiagnosisInvoice'])
            ->name('diagnosis-invoice.store')
            ->middleware('role:Super Admin|Administrator|Project Manager');
        Route::get('/process-invoice', [App\Http\Controllers\PropertyController::class, 'previewProcessInvoice'])
            ->name('process-invoice.preview')
            ->middleware('role:Super Admin|Administrator|Project Manager');
        Route::post('/process-invoice/share', [App\Http\Controllers\PropertyController::class, 'shareProcessInvoice'])
            ->name('process-invoice.share')
            ->middleware('role:Super Admin|Administrator|Project Manager');
        Route::get('/process-invoice/download', [App\Http\Controllers\PropertyController::class, 'downloadProcessInvoice'])
            ->name('process-invoice.download')
            ->middleware('role:Super Admin|Administrator|Project Manager');
        Route::get('/digital-twin', [App\Http\Controllers\DigitalTwinController::class, 'showProperty'])
            ->name('digital-twin');
    });
    Route::resource('inspections', App\Http\Controllers\InspectionController::class)
        ->except(['update', 'destroy']);
    Route::post('/inspections/autosave-draft', [App\Http\Controllers\InspectionController::class, 'autosaveDraft'])
        ->name('inspections.autosave-draft');
    Route::prefix('inspections/{inspection}')
        ->name('inspections.')
        ->group(function () {
            Route::get('/download-invoice', [App\Http\Controllers\InspectionController::class, 'downloadInvoice'])->name('download-invoice');
            Route::get('/agreement/download', [App\Http\Controllers\InspectionController::class, 'downloadAgreementPdf'])->name('agreement.download');
            Route::get('/work-payment', [App\Http\Controllers\InspectionController::class, 'workPayment'])->name('work-payment');
            Route::post('/work-payment', [App\Http\Controllers\InspectionController::class, 'processWorkPayment'])->name('process-work-payment');
            Route::post('/agreement/staff-sign', [App\Http\Controllers\InspectionController::class, 'staffSignAgreement'])->name('agreement.staff-sign');
            Route::post('/agreement/countersign', [App\Http\Controllers\InspectionController::class, 'countersignAgreement'])->name('agreement.countersign');
            Route::post('/work-schedule', [App\Http\Controllers\InspectionController::class, 'storeWorkSchedule'])->name('work-schedule.store');
            Route::post('/assessment-schedule', [App\Http\Controllers\InspectionController::class, 'updateAssessmentSchedule'])->name('assessment-schedule.update');
            Route::get('/digital-twin', [App\Http\Controllers\DigitalTwinController::class, 'show'])->name('digital-twin');
            Route::post('/digital-twin/models', [App\Http\Controllers\DigitalTwinController::class, 'storeSpatialModel'])->name('digital-twin.models.store');
            Route::post('/digital-twin/markers', [App\Http\Controllers\DigitalTwinController::class, 'storeIssueMarker'])->name('digital-twin.markers.store');
            Route::get('/matterport', fn (App\Models\Inspection $inspection) => redirect()->route('inspections.digital-twin', $inspection))->name('matterport');
            Route::post('/matterport-model', [App\Http\Controllers\MatterportModelController::class, 'store'])->name('matterport-model.store');
            Route::get('/phar-data', [App\Http\Controllers\InspectionController::class, 'pharData'])->name('phar-data');
            Route::post('/store-phar-data', [App\Http\Controllers\InspectionController::class, 'storePharData'])->name('store-phar-data');
            // Assessment / Estimation phase split (ETOGO workflow Stages B & D)
            Route::get('/findings-preview', [App\Http\Controllers\InspectionController::class, 'findingsPreview'])->name('findings-preview');
            Route::post('/finalise-assessment', [App\Http\Controllers\InspectionController::class, 'finaliseAssessment'])->name('finalise-assessment');
            Route::get('/assessment-report', [App\Http\Controllers\InspectionController::class, 'assessmentReport'])->name('assessment-report');
            Route::post('/reopen-assessment', [App\Http\Controllers\InspectionController::class, 'reopenAssessment'])->name('reopen-assessment');
            Route::post('/share-findings-report', [App\Http\Controllers\InspectionController::class, 'shareFindingsReport'])->name('share-findings-report');
            Route::get('/estimation', [App\Http\Controllers\InspectionController::class, 'estimation'])->name('estimation');
            Route::post('/store-estimation', [App\Http\Controllers\InspectionController::class, 'storeEstimation'])->name('store-estimation');
            Route::post('/share-quotation', [App\Http\Controllers\InspectionController::class, 'shareQuotation'])->name('share-quotation');
            Route::post('/share-followup-quotation', [App\Http\Controllers\InspectionController::class, 'shareFollowupQuotation'])->name('share-followup-quotation');
            Route::post('/complete-assessment', [App\Http\Controllers\InspectionController::class, 'completeAssessment'])->name('complete-assessment');
            Route::get('/preview-report', [App\Http\Controllers\InspectionController::class, 'previewReport'])->name('preview-report');
            Route::get('/preview-agreement', [App\Http\Controllers\InspectionController::class, 'previewAgreement'])->name('preview-agreement');
            Route::post('/findings/{findingIndex}/photos', [App\Http\Controllers\InspectionController::class, 'addFindingPhotos'])->name('findings.add-photos');
        });
    Route::resource('projects', App\Http\Controllers\ProjectController::class);
    Route::resource('invoices', App\Http\Controllers\InvoiceController::class);
    Route::resource('work-logs', App\Http\Controllers\WorkLogController::class);
    Route::resource('milestones', App\Http\Controllers\MilestoneController::class);

    // Property Maintenance Visit Logs
    Route::get('/maintenance-visit-logs', [App\Http\Controllers\MaintenanceVisitLogController::class, 'index'])->name('maintenance-visit-logs.index');
    Route::get('/maintenance-visit-logs/{inspection}', [App\Http\Controllers\MaintenanceVisitLogController::class, 'show'])->name('maintenance-visit-logs.show');
    Route::post('/maintenance-visit-logs/{inspection}/log', [App\Http\Controllers\MaintenanceVisitLogController::class, 'store'])->name('maintenance-visit-logs.store');
    Route::post('/maintenance-visit-logs/{inspection}/complete-finding', [App\Http\Controllers\MaintenanceVisitLogController::class, 'completeFinding'])->name('maintenance-visit-logs.complete-finding');
    Route::post('/maintenance-visit-logs/{inspection}/complete-project', [App\Http\Controllers\MaintenanceVisitLogController::class, 'completeProject'])->name('maintenance-visit-logs.complete-project');

    // Tool Return & Assignment (Store Manager + Super Admin only)
    Route::middleware('role:Super Admin|Store Manager')->group(function () {
        Route::get('/tool-assignments', [App\Http\Controllers\ToolAssignmentController::class, 'index'])->name('tool-assignments.index');
        Route::post('/tool-assignments/manual', [App\Http\Controllers\ToolAssignmentController::class, 'storeManualAssignment'])->name('tool-assignments.manual');
        Route::post('/tool-assignments/{assignment}/assign', [App\Http\Controllers\ToolAssignmentController::class, 'assignQuantity'])->name('tool-assignments.assign');
        Route::post('/tool-assignments/{assignment}/return', [App\Http\Controllers\ToolAssignmentController::class, 'markReturned'])->name('tool-assignments.return');
    });
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
    $registerNotificationRoutes();
    
    // Client routes - client-only access (Super Admin and other staff roles excluded)
    Route::prefix('client')->name('client.')->middleware('role:Client')->group(function() use ($registerNotificationRoutes) {
        $registerNotificationRoutes();

        // Properties
        Route::resource('properties', App\Http\Controllers\Client\PropertyController::class);
        
        // Inspections
        Route::get('/inspections', [App\Http\Controllers\Client\InspectionController::class, 'index'])->name('inspections.index');
        Route::get('/inspections/quotations', [App\Http\Controllers\Client\InspectionController::class, 'quotations'])->name('inspections.quotations');
        Route::get('/inspections/{inspection}/report', [App\Http\Controllers\Client\InspectionController::class, 'report'])->name('inspections.report');
        Route::get('/inspections/{inspection}/agreement', [App\Http\Controllers\Client\InspectionController::class, 'agreement'])->name('inspections.agreement');
        Route::get('/inspections/{inspection}/agreement/download', [App\Http\Controllers\Client\InspectionController::class, 'downloadAgreementPdf'])->name('inspections.agreement.download');
        Route::post('/inspections/{inspection}/agreement/sign', [App\Http\Controllers\Client\InspectionController::class, 'signAgreement'])->name('inspections.agreement.sign');
        Route::post('/inspections/{inspection}/findings/{findingIndex}/photos', [App\Http\Controllers\Client\InspectionController::class, 'addFindingPhotos'])->name('inspections.findings.add-photos');
        // ETOGO Stage C — client reviews findings (no pricing) and commits to which items to remediate
        Route::get('/inspections/{inspection}/findings-report', [App\Http\Controllers\Client\InspectionController::class, 'findingsReport'])->name('inspections.findings-report');
        Route::post('/inspections/{inspection}/commit-findings', [App\Http\Controllers\Client\InspectionController::class, 'commitFindings'])->name('inspections.commit-findings');
        Route::get('/inspections/{inspection}/quotation', [App\Http\Controllers\Client\InspectionController::class, 'quotation'])->name('inspections.quotation');
        Route::post('/inspections/{inspection}/quotation/respond', [App\Http\Controllers\Client\InspectionController::class, 'respondQuotation'])->name('inspections.quotation.respond');
        Route::get('/inspections/{inspection}/work-payment', [App\Http\Controllers\Client\InspectionController::class, 'workPayment'])->name('inspections.work-payment');
        Route::post('/inspections/{inspection}/work-payment', [App\Http\Controllers\Client\InspectionController::class, 'processWorkPayment'])->name('inspections.process-work-payment');
        Route::get('/inspections/{inspection}/installment', [App\Http\Controllers\Client\InspectionController::class, 'payInstallment'])->name('inspections.pay-installment');
        Route::post('/inspections/{inspection}/installment', [App\Http\Controllers\Client\InspectionController::class, 'processInstallment'])->name('inspections.process-installment');

        // Schedule & pay for inspection
        Route::get('/inspections/{property}/schedule', [App\Http\Controllers\Client\InspectionController::class, 'scheduleCreate'])->name('inspections.schedule');
        Route::post('/inspections/{property}/payment-intent', [App\Http\Controllers\Client\InspectionController::class, 'createInspectionPaymentIntent'])->name('inspections.payment-intent');
        Route::post('/inspections/{property}/schedule', [App\Http\Controllers\Client\InspectionController::class, 'scheduleStore'])->name('inspections.store-schedule');
        Route::get('/inspections/checkout-success', [App\Http\Controllers\Client\InspectionController::class, 'checkoutSuccess'])->name('inspections.checkout-success');
        Route::get('/inspections/checkout-cancel', [App\Http\Controllers\Client\InspectionController::class, 'checkoutCancel'])->name('inspections.checkout-cancel');
        
        // Projects
        Route::get('/projects', [App\Http\Controllers\Client\ProjectController::class, 'index'])
            ->name('projects.index');
        Route::get('/projects/{project}/inspections/{inspection}/log-sheet', [App\Http\Controllers\Client\ProjectController::class, 'showCompletedLogSheet'])
            ->name('projects.log-sheet');

        // Service Requests
        Route::get('/service-requests', [App\Http\Controllers\Client\ServiceRequestController::class, 'index'])
            ->name('service-requests.index');
        Route::get('/service-requests/create', [App\Http\Controllers\Client\ServiceRequestController::class, 'create'])
            ->name('service-requests.create');
        Route::post('/service-requests', [App\Http\Controllers\Client\ServiceRequestController::class, 'store'])
            ->name('service-requests.store');
        Route::get('/service-requests/{serviceRequest}', [App\Http\Controllers\Client\ServiceRequestController::class, 'show'])
            ->name('service-requests.show');
        
        // Invoices
        Route::get('/invoices', [App\Http\Controllers\Client\InvoiceController::class, 'index'])
            ->name('invoices.index');
        Route::get('/invoices/{invoice}/payment', [App\Http\Controllers\Client\InvoiceController::class, 'payment'])
            ->name('invoices.payment');
        Route::post('/invoices/{invoice}/payment', [App\Http\Controllers\Client\InvoiceController::class, 'processPayment'])
            ->name('invoices.process-payment');
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
    
    // Admin routes — restricted to Super Admin and Administrator only
    Route::prefix('admin')->name('admin.')->middleware('role:Super Admin|Administrator')->group(function() {
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
        // Tool Settings — also accessible to Store Manager (nested group overrides outer middleware)
    }); // end Super Admin|Administrator group — re-opened below for shared routes

    // Tool settings: accessible to Super Admin, Administrator, and Store Manager
    Route::prefix('admin')->name('admin.')->middleware('role:Super Admin|Administrator|Store Manager')->group(function () {
        Route::resource('tool-settings', App\Http\Controllers\Admin\ToolSettingController::class)->except(['show'])->names('tool-settings')->parameters(['tool-settings' => 'toolSetting']);
        Route::get('tool-settings/{toolSetting}/logs', [App\Http\Controllers\Admin\ToolSettingController::class, 'logs'])->name('tool-settings.logs');
        Route::post('tool-assignments/{assignment}/return', [App\Http\Controllers\Admin\ToolSettingController::class, 'markReturned'])->name('admin-tool-assignments.return');
    });

    // Resume Super Admin|Administrator only routes
    Route::prefix('admin')->name('admin.')->middleware('role:Super Admin|Administrator')->group(function () {
        Route::resource('systems', App\Http\Controllers\Admin\SystemController::class)->except(['show'])->names('systems');
        Route::resource('subsystems', App\Http\Controllers\Admin\SubsystemController::class)->except(['show'])->names('subsystems');
        Route::resource('components', App\Http\Controllers\Admin\BuildingComponentController::class)->except(['show'])->names('components');
        
        // BDC Calibration Engine Settings
        Route::get('settings/bdc', [App\Http\Controllers\Admin\BDCSettingsController::class, 'index'])->name('settings.bdc');
        Route::put('settings/bdc', [App\Http\Controllers\Admin\BDCSettingsController::class, 'update'])->name('settings.bdc.update');
        Route::post('settings/bdc/preview', [App\Http\Controllers\Admin\BDCSettingsController::class, 'preview'])->name('settings.bdc.preview');
        Route::post('settings/bdc/reset', [App\Http\Controllers\Admin\BDCSettingsController::class, 'reset'])->name('settings.bdc.reset');
        
        // Reports
        Route::get('/reports', function() {
            return view('admin.reports.index');
        })->name('reports.index');

        // Service Requests
        Route::get('/service-requests', [App\Http\Controllers\Admin\ServiceRequestController::class, 'index'])
            ->name('service-requests.index');
        Route::get('/service-requests/create', [App\Http\Controllers\Admin\ServiceRequestController::class, 'create'])
            ->name('service-requests.create');
        Route::post('/service-requests', [App\Http\Controllers\Admin\ServiceRequestController::class, 'store'])
            ->name('service-requests.store');
        Route::get('/service-requests/{serviceRequest}', [App\Http\Controllers\Admin\ServiceRequestController::class, 'show'])
            ->name('service-requests.show');
        Route::post('/service-requests/{serviceRequest}/triage', [App\Http\Controllers\Admin\ServiceRequestController::class, 'triage'])
            ->name('service-requests.triage');
        Route::post('/service-requests/{serviceRequest}/assess', [App\Http\Controllers\Admin\ServiceRequestController::class, 'assess'])
            ->name('service-requests.assess');

        // Trade partner onboarding review
        Route::get('/trade-partners', [App\Http\Controllers\Admin\TradePartnerController::class, 'index'])
            ->name('trade-partners.index');
        Route::get('/trade-partners/{tradePartner}', [App\Http\Controllers\Admin\TradePartnerController::class, 'show'])
            ->name('trade-partners.show');
        Route::get('/trade-applications', [App\Http\Controllers\Admin\TradeApplicationController::class, 'index'])
            ->name('trade-applications.index');
        Route::get('/trade-applications/{tradeApplication}', [App\Http\Controllers\Admin\TradeApplicationController::class, 'show'])
            ->name('trade-applications.show');
        Route::patch('/trade-applications/{tradeApplication}/status', [App\Http\Controllers\Admin\TradeApplicationController::class, 'updateStatus'])
            ->name('trade-applications.update-status');
    });
});
