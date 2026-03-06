@php
    $user = Auth::user();

    $roleLabel = match (true) {
        $user->hasRole('Super Admin') => 'Super Admin',
        $user->hasRole('Administrator') => 'Administrator',
        $user->hasRole('Inspector') => 'Inspector',
        $user->hasRole('Project Manager') => 'Project Manager',
        $user->hasRole('Technician') => 'Technician',
        $user->hasRole('Finance') => 'Finance',
        default => 'Member',
    };

    $propertiesOpen = request()->routeIs('properties.*');
    $inspectionsOpen = request()->routeIs('inspections.*');
    $projectsOpen = request()->routeIs('projects.*') || request()->routeIs('work-logs.*') || request()->routeIs('milestones.*') || request()->routeIs('change-orders.*');
    $billingOpen = request()->routeIs('invoices.*') || request()->routeIs('budgets.*');
    $reportsOpen = request()->routeIs('reports.*') || request()->routeIs('savings.*');
    $accessControlOpen = request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*');
    $cpiOpen = request()->routeIs('admin.pricing-packages.*')
        || request()->routeIs('admin.property-types.*')
        || request()->routeIs('admin.cpi-bands.*')
        || request()->routeIs('admin.cpi-multipliers.*')
        || request()->routeIs('admin.cpi-domains.*')
        || request()->routeIs('admin.supply-materials.*')
        || request()->routeIs('admin.age-brackets.*')
        || request()->routeIs('admin.containment-categories.*')
        || request()->routeIs('admin.crawl-access.*')
        || request()->routeIs('admin.roof-access.*')
        || request()->routeIs('admin.equipment-requirements.*')
        || request()->routeIs('admin.complexity-categories.*')
        || request()->routeIs('admin.residential-tiers.*')
        || request()->routeIs('admin.commercial-settings.*')
        || request()->routeIs('admin.mixed-use-settings.*')
        || request()->routeIs('admin.pricing-config.*')
        || request()->routeIs('admin.parameters.*')
        || request()->routeIs('admin.fmc-material-settings.*')
        || request()->routeIs('admin.finding-template-settings.*')
        || request()->routeIs('admin.systems.*')
        || request()->routeIs('admin.subsystems.*')
        || request()->routeIs('admin.settings.bdc*')
        || request()->routeIs('admin.reactive-costs.*')
        || request()->routeIs('admin.stewardship-loss.*');

    $scheduledPaidCount = 0;
    $unscheduledCount = 0;
    if ($user->can('view-all-properties')) {
        $scheduledPaidCount = \App\Models\Inspection::where('inspection_fee_status', 'paid')
            ->where('status', 'scheduled')
            ->whereNull('inspector_id')
            ->count();

        $unscheduledCount = \App\Models\Property::where('status', 'active')
            ->whereDoesntHave('inspections')
            ->count();
    }

    $scheduledInspectionsCount = 0;
    $unscheduledInspectionsCount = 0;
    $inProgressInspectionsCount = 0;

    if ($user->hasRole(['Super Admin', 'Administrator'])) {
        $propertyIds = \App\Models\Property::where('status', 'approved')->pluck('id');
        $projectIds = \App\Models\Project::whereIn('property_id', $propertyIds)->pluck('id');

        $scheduledInspectionsCount = \App\Models\Inspection::whereIn('project_id', $projectIds)
            ->where('status', 'scheduled')
            ->count();

        $inProgressInspectionsCount = \App\Models\Inspection::whereIn('project_id', $projectIds)
            ->where('status', 'in_progress')
            ->count();
    } elseif ($user->hasRole('Inspector')) {
        $scheduledInspectionsCount = \App\Models\Property::where('inspector_id', $user->id)
            ->where('status', 'awaiting_inspection')
            ->whereNotNull('inspection_scheduled_at')
            ->count();

        $unscheduledInspectionsCount = \App\Models\Property::where('inspector_id', $user->id)
            ->where('status', 'awaiting_inspection')
            ->whereNull('inspection_scheduled_at')
            ->count();
    } elseif ($user->hasRole('Project Manager')) {
        $scheduledInspectionsCount = \App\Models\Property::where('project_manager_id', $user->id)
            ->where('status', 'awaiting_inspection')
            ->whereNotNull('inspection_scheduled_at')
            ->count();

        $unscheduledInspectionsCount = \App\Models\Property::where('project_manager_id', $user->id)
            ->where('status', 'awaiting_inspection')
            ->whereNull('inspection_scheduled_at')
            ->count();
    }

    $projectsWithScopeCount = \App\Models\Project::whereHas('scopeOfWorks')->count();

    $activeProjectsCount = $user->hasRole('Technician')
        ? \App\Models\Project::where('assigned_to', $user->id)->where('status', 'active')->count()
        : \App\Models\Project::where('status', 'active')->count();

    $unpaidInvoicesCount = \App\Models\Invoice::pending()->count();
@endphp

<nav class="sidebar sidebar-offcanvas admin-client-sidebar" id="sidebar">
    <div class="admin-client-sidebar-inner">
        <div class="admin-client-brand">
            <a href="{{ route('dashboard') }}">EMURIA</a>
        </div>

        <div class="admin-client-user">
            <img class="admin-client-avatar" src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}">
            <div>
                <div class="admin-client-name">{{ $user->name }}</div>
                <div class="admin-client-role">{{ $roleLabel }}</div>
            </div>
        </div>

        <div class="admin-client-section-title">Main Navigation</div>
        <a class="admin-client-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" href="{{ route('dashboard') }}">
            <i class="mdi mdi-view-dashboard"></i>
            <span>Dashboard</span>
        </a>

        @can('view-all-properties')
            <div class="admin-client-section-title">Property Management</div>
            <details class="admin-client-group" {{ $propertiesOpen ? 'open' : '' }}>
                <summary class="admin-client-link {{ $propertiesOpen ? 'is-active' : '' }}">
                    <span class="admin-client-summary-left">
                        <i class="mdi mdi-home-city"></i>
                        <span>Property Management</span>
                    </span>
                    <span class="admin-client-arrow">▾</span>
                </summary>
                <div class="admin-client-submenu">
                    <a class="admin-client-sublink {{ request()->get('status') == 'awaiting_inspection' ? 'is-active' : '' }}" href="{{ route('properties.index') }}?status=awaiting_inspection">
                        <span class="admin-client-sublabel">Scheduled &amp; Paid</span>
                        @if($scheduledPaidCount > 0)
                            <span class="admin-client-badge">{{ $scheduledPaidCount }}</span>
                        @endif
                    </a>
                    <a class="admin-client-sublink {{ request()->get('status') == 'active' ? 'is-active' : '' }}" href="{{ route('properties.index') }}?status=active">
                        <span class="admin-client-sublabel">Not Scheduled</span>
                        @if($unscheduledCount > 0)
                            <span class="admin-client-badge">{{ $unscheduledCount }}</span>
                        @endif
                    </a>
                    <a class="admin-client-sublink {{ !request()->has('status') ? 'is-active' : '' }}" href="{{ route('properties.index') }}">
                        <span class="admin-client-sublabel">All Properties</span>
                    </a>
                </div>
            </details>
        @endcan

        @if($user->hasRole(['Inspector', 'Project Manager', 'Super Admin', 'Administrator']) || $user->can('view-inspections'))
            <div class="admin-client-section-title">Services</div>
            <details class="admin-client-group" {{ $inspectionsOpen ? 'open' : '' }}>
                <summary class="admin-client-link {{ $inspectionsOpen ? 'is-active' : '' }}">
                    <span class="admin-client-summary-left">
                        <i class="mdi mdi-clipboard-check"></i>
                        <span>Inspection Workflow</span>
                    </span>
                    <span class="admin-client-arrow">▾</span>
                </summary>
                <div class="admin-client-submenu">
                    @if($user->hasRole(['Super Admin', 'Administrator']))
                        <a class="admin-client-sublink {{ request()->get('status') == 'scheduled' ? 'is-active' : '' }}" href="{{ route('inspections.index') }}?status=scheduled">
                            <span class="admin-client-sublabel">Awaiting Inspection</span>
                            @if($scheduledInspectionsCount > 0)
                                <span class="admin-client-badge">{{ $scheduledInspectionsCount }}</span>
                            @endif
                        </a>
                        <a class="admin-client-sublink {{ request()->get('status') == 'in_progress' ? 'is-active' : '' }}" href="{{ route('inspections.index') }}?status=in_progress">
                            <span class="admin-client-sublabel">In Progress</span>
                            @if($inProgressInspectionsCount > 0)
                                <span class="admin-client-badge">{{ $inProgressInspectionsCount }}</span>
                            @endif
                        </a>
                        <a class="admin-client-sublink {{ request()->get('status') == 'completed' ? 'is-active' : '' }}" href="{{ route('inspections.index') }}?status=completed">
                            <span class="admin-client-sublabel">Completed</span>
                        </a>
                        <a class="admin-client-sublink {{ !request()->has('status') ? 'is-active' : '' }}" href="{{ route('inspections.index') }}">
                            <span class="admin-client-sublabel">All Inspections</span>
                        </a>
                    @else
                        <a class="admin-client-sublink {{ request()->get('status') == 'scheduled' ? 'is-active' : '' }}" href="{{ route('inspections.index') }}?status=scheduled">
                            <span class="admin-client-sublabel">Scheduled</span>
                            @if($scheduledInspectionsCount > 0)
                                <span class="admin-client-badge">{{ $scheduledInspectionsCount }}</span>
                            @endif
                        </a>
                        <a class="admin-client-sublink {{ request()->get('status') == 'unscheduled' ? 'is-active' : '' }}" href="{{ route('inspections.index') }}?status=unscheduled">
                            <span class="admin-client-sublabel">Unscheduled</span>
                            @if($unscheduledInspectionsCount > 0)
                                <span class="admin-client-badge">{{ $unscheduledInspectionsCount }}</span>
                            @endif
                        </a>
                        <a class="admin-client-sublink {{ !request()->has('status') ? 'is-active' : '' }}" href="{{ route('inspections.index') }}">
                            <span class="admin-client-sublabel">All Inspections</span>
                        </a>
                    @endif
                </div>
            </details>
        @endif

        @if($user->hasRole(['Technician', 'Project Manager', 'Super Admin', 'Administrator']) || $user->can('view-all-projects'))
            <details class="admin-client-group" {{ $projectsOpen ? 'open' : '' }}>
                <summary class="admin-client-link {{ $projectsOpen ? 'is-active' : '' }}">
                    <span class="admin-client-summary-left">
                        <i class="mdi mdi-briefcase"></i>
                        <span>Project Management</span>
                    </span>
                    <span class="admin-client-arrow">▾</span>
                </summary>
                <div class="admin-client-submenu">
                    @if($user->hasRole(['Project Manager', 'Super Admin', 'Administrator']) || $user->can('view-all-projects'))
                        <a class="admin-client-sublink {{ request()->get('has_scope') == 'true' ? 'is-active' : '' }}" href="{{ route('projects.index') }}?has_scope=true">
                            <span class="admin-client-sublabel">Scope of Work &amp; Quotes</span>
                            @if($projectsWithScopeCount > 0)
                                <span class="admin-client-badge">{{ $projectsWithScopeCount }}</span>
                            @endif
                        </a>
                        <a class="admin-client-sublink {{ request()->routeIs('projects.index') && request()->get('view') == 'scheduling' ? 'is-active' : '' }}" href="{{ route('projects.index') }}?view=scheduling">
                            <span class="admin-client-sublabel">Project Scheduling</span>
                        </a>
                    @endif
                    <a class="admin-client-sublink {{ request()->get('status') == 'active' ? 'is-active' : '' }}" href="{{ route('projects.index') }}?status=active">
                        <span class="admin-client-sublabel">Active Projects</span>
                        @if($activeProjectsCount > 0)
                            <span class="admin-client-badge">{{ $activeProjectsCount }}</span>
                        @endif
                    </a>
                    <a class="admin-client-sublink {{ request()->routeIs('work-logs.*') ? 'is-active' : '' }}" href="{{ route('work-logs.index') }}">
                        <span class="admin-client-sublabel">Work Logs &amp; Progress</span>
                    </a>
                    @if($user->hasRole(['Project Manager', 'Super Admin', 'Administrator']) || $user->can('view-all-projects'))
                        <a class="admin-client-sublink {{ request()->routeIs('milestones.*') ? 'is-active' : '' }}" href="{{ route('milestones.index') }}">
                            <span class="admin-client-sublabel">Milestones &amp; Budget</span>
                        </a>
                        <a class="admin-client-sublink {{ request()->routeIs('change-orders.*') ? 'is-active' : '' }}" href="{{ route('change-orders.index') }}">
                            <span class="admin-client-sublabel">Change Orders</span>
                        </a>
                    @endif
                    <a class="admin-client-sublink {{ !request()->has('status') && !request()->has('has_scope') && !request()->has('view') ? 'is-active' : '' }}" href="{{ route('projects.index') }}">
                        <span class="admin-client-sublabel">All Projects</span>
                    </a>
                </div>
            </details>
        @endif

        @can('view-invoices')
            <div class="admin-client-section-title">Billing</div>
            <details class="admin-client-group" {{ $billingOpen ? 'open' : '' }}>
                <summary class="admin-client-link {{ $billingOpen ? 'is-active' : '' }}">
                    <span class="admin-client-summary-left">
                        <i class="mdi mdi-cash-multiple"></i>
                        <span>Billing &amp; Finance</span>
                    </span>
                    <span class="admin-client-arrow">▾</span>
                </summary>
                <div class="admin-client-submenu">
                    <a class="admin-client-sublink {{ request()->get('status') == 'pending' ? 'is-active' : '' }}" href="{{ route('invoices.index') }}?status=pending">
                        <span class="admin-client-sublabel">Unpaid Invoices</span>
                        @if($unpaidInvoicesCount > 0)
                            <span class="admin-client-badge">{{ $unpaidInvoicesCount }}</span>
                        @endif
                    </a>
                    <a class="admin-client-sublink {{ request()->routeIs('invoices.index') && !request()->has('status') ? 'is-active' : '' }}" href="{{ route('invoices.index') }}">
                        <span class="admin-client-sublabel">All Invoices</span>
                    </a>
                    <a class="admin-client-sublink {{ request()->routeIs('budgets.*') ? 'is-active' : '' }}" href="{{ route('budgets.index') }}">
                        <span class="admin-client-sublabel">Budget Management</span>
                    </a>
                </div>
            </details>
        @endcan

        @can('view-communications')
            <a class="admin-client-link {{ request()->routeIs('communications.*') ? 'is-active' : '' }}" href="{{ route('communications.index') }}">
                <i class="mdi mdi-message-text"></i>
                <span>Communications</span>
            </a>
        @endcan

        @can('view-reports')
            <details class="admin-client-group" {{ $reportsOpen ? 'open' : '' }}>
                <summary class="admin-client-link {{ $reportsOpen ? 'is-active' : '' }}">
                    <span class="admin-client-summary-left">
                        <i class="mdi mdi-chart-areaspline"></i>
                        <span>Reports &amp; Savings</span>
                    </span>
                    <span class="admin-client-arrow">▾</span>
                </summary>
                <div class="admin-client-submenu">
                    <a class="admin-client-sublink {{ request()->routeIs('reports.index') ? 'is-active' : '' }}" href="{{ route('reports.index') }}">
                        <span class="admin-client-sublabel">Performance Reports</span>
                    </a>
                    <a class="admin-client-sublink {{ request()->routeIs('savings.*') ? 'is-active' : '' }}" href="{{ route('savings.index') }}">
                        <span class="admin-client-sublabel">Cost &amp; Savings Analysis</span>
                    </a>
                </div>
            </details>
        @endcan

        @role('Super Admin|Administrator')
            <div class="admin-client-section-title">Admin</div>
            <details class="admin-client-group" {{ $accessControlOpen ? 'open' : '' }}>
                <summary class="admin-client-link {{ $accessControlOpen ? 'is-active' : '' }}">
                    <span class="admin-client-summary-left">
                        <i class="mdi mdi-shield-account"></i>
                        <span>Access Control</span>
                    </span>
                    <span class="admin-client-arrow">▾</span>
                </summary>
                <div class="admin-client-submenu">
                    <a class="admin-client-sublink {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" href="{{ route('admin.users.index') }}"><span class="admin-client-sublabel">User Management</span></a>
                    <a class="admin-client-sublink {{ request()->routeIs('admin.roles.*') ? 'is-active' : '' }}" href="{{ route('admin.roles.index') }}"><span class="admin-client-sublabel">Role Management</span></a>
                    <a class="admin-client-sublink {{ request()->routeIs('admin.permissions.*') ? 'is-active' : '' }}" href="{{ route('admin.permissions.index') }}"><span class="admin-client-sublabel">Permission Management</span></a>
                </div>
            </details>

            <details class="admin-client-group" {{ $cpiOpen ? 'open' : '' }}>
                <summary class="admin-client-link {{ $cpiOpen ? 'is-active' : '' }}">
                    <span class="admin-client-summary-left">
                        <i class="mdi mdi-calculator"></i>
                        <span>CPI Pricing System</span>
                    </span>
                    <span class="admin-client-arrow">▾</span>
                </summary>
                <div class="admin-client-submenu">
                    <a class="admin-client-sublink {{ request()->routeIs('admin.systems.*') ? 'is-active' : '' }}" href="{{ route('admin.systems.index') }}"><span class="admin-client-sublabel">Property Systems</span></a>
                    <a class="admin-client-sublink {{ request()->routeIs('admin.subsystems.*') ? 'is-active' : '' }}" href="{{ route('admin.subsystems.index') }}"><span class="admin-client-sublabel">Property Subsystems</span></a>
                    <a class="admin-client-sublink {{ request()->routeIs('admin.settings.bdc*') ? 'is-active' : '' }}" href="{{ route('admin.settings.bdc') }}"><span class="admin-client-sublabel">BDC Calibration Engine</span></a>
                    <a class="admin-client-sublink {{ request()->routeIs('admin.fmc-material-settings.*') ? 'is-active' : '' }}" href="{{ route('admin.fmc-material-settings.index') }}"><span class="admin-client-sublabel">FMC Material Settings</span></a>
                    <a class="admin-client-sublink {{ request()->routeIs('admin.finding-template-settings.*') ? 'is-active' : '' }}" href="{{ route('admin.finding-template-settings.index') }}"><span class="admin-client-sublabel">Findings Template Settings</span></a>
                    <a class="admin-client-sublink {{ request()->routeIs('admin.pricing-packages.*') ? 'is-active' : '' }}" href="{{ route('admin.pricing-packages.index') }}"><span class="admin-client-sublabel">Pricing Packages</span></a>
                    <a class="admin-client-sublink {{ request()->routeIs('admin.property-types.*') ? 'is-active' : '' }}" href="{{ route('admin.property-types.index') }}"><span class="admin-client-sublabel">Property Types</span></a>
                    <a class="admin-client-sublink {{ request()->routeIs('admin.cpi-bands.*') ? 'is-active' : '' }}" href="{{ route('admin.cpi-bands.index') }}"><span class="admin-client-sublabel">CPI Band Ranges</span></a>
                    <a class="admin-client-sublink {{ request()->routeIs('admin.cpi-multipliers.*') ? 'is-active' : '' }}" href="{{ route('admin.cpi-multipliers.index') }}"><span class="admin-client-sublabel">CPI Multipliers</span></a>
                    <a class="admin-client-sublink {{ request()->routeIs('admin.cpi-domains.*') ? 'is-active' : '' }}" href="{{ route('admin.cpi-domains.index') }}"><span class="admin-client-sublabel">CPI Domains</span></a>
                    <a class="admin-client-sublink {{ request()->routeIs('admin.supply-materials.*') ? 'is-active' : '' }}" href="{{ route('admin.supply-materials.index') }}"><span class="admin-client-sublabel">Supply Materials</span></a>
                    <a class="admin-client-sublink {{ request()->routeIs('admin.age-brackets.*') ? 'is-active' : '' }}" href="{{ route('admin.age-brackets.index') }}"><span class="admin-client-sublabel">Age Brackets</span></a>
                    <a class="admin-client-sublink {{ request()->routeIs('admin.containment-categories.*') ? 'is-active' : '' }}" href="{{ route('admin.containment-categories.index') }}"><span class="admin-client-sublabel">Containment Categories</span></a>
                    <a class="admin-client-sublink {{ request()->routeIs('admin.crawl-access.*') ? 'is-active' : '' }}" href="{{ route('admin.crawl-access.index') }}"><span class="admin-client-sublabel">Crawl Space Access</span></a>
                    <a class="admin-client-sublink {{ request()->routeIs('admin.roof-access.*') ? 'is-active' : '' }}" href="{{ route('admin.roof-access.index') }}"><span class="admin-client-sublabel">Roof Access</span></a>
                    <a class="admin-client-sublink {{ request()->routeIs('admin.equipment-requirements.*') ? 'is-active' : '' }}" href="{{ route('admin.equipment-requirements.index') }}"><span class="admin-client-sublabel">Equipment Requirements</span></a>
                    <a class="admin-client-sublink {{ request()->routeIs('admin.complexity-categories.*') ? 'is-active' : '' }}" href="{{ route('admin.complexity-categories.index') }}"><span class="admin-client-sublabel">Complexity Categories</span></a>
                    <a class="admin-client-sublink {{ request()->routeIs('admin.residential-tiers.*') ? 'is-active' : '' }}" href="{{ route('admin.residential-tiers.index') }}"><span class="admin-client-sublabel">Residential Size Tiers</span></a>
                    <a class="admin-client-sublink {{ request()->routeIs('admin.commercial-settings.*') ? 'is-active' : '' }}" href="{{ route('admin.commercial-settings.index') }}"><span class="admin-client-sublabel">Commercial Size Settings</span></a>
                    <a class="admin-client-sublink {{ request()->routeIs('admin.mixed-use-settings.*') ? 'is-active' : '' }}" href="{{ route('admin.mixed-use-settings.index') }}"><span class="admin-client-sublabel">Mixed-Use Settings</span></a>
                    <a class="admin-client-sublink {{ request()->routeIs('admin.pricing-config.*') ? 'is-active' : '' }}" href="{{ route('admin.pricing-config.index') }}"><span class="admin-client-sublabel">System Configuration</span></a>
                    <a class="admin-client-sublink {{ request()->routeIs('admin.parameters.*') ? 'is-active' : '' }}" href="{{ route('admin.parameters.index') }}"><span class="admin-client-sublabel">Parameters</span></a>
                    <a class="admin-client-sublink {{ request()->routeIs('admin.reactive-costs.*') ? 'is-active' : '' }}" href="{{ route('admin.reactive-costs.index') }}"><span class="admin-client-sublabel">Reactive Cost Assumptions</span></a>
                    <a class="admin-client-sublink {{ request()->routeIs('admin.stewardship-loss.*') ? 'is-active' : '' }}" href="{{ route('admin.stewardship-loss.index') }}"><span class="admin-client-sublabel">Stewardship Loss Reduction</span></a>
                </div>
            </details>

            <a class="admin-client-link {{ request()->routeIs('admin.reports.*') ? 'is-active' : '' }}" href="{{ route('admin.reports.index') }}">
                <i class="mdi mdi-chart-bar"></i>
                <span>Reports</span>
            </a>
        @endrole
    </div>
</nav>

<style>
.admin-client-sidebar,
body .admin-client-sidebar,
body.light-theme .admin-client-sidebar {
    background: linear-gradient(180deg, #1f2f98 0%, #1a2a86 55%, #183075 100%) !important;
    border: none !important;
    box-shadow: none !important;
}

.admin-client-sidebar,
.admin-client-sidebar *,
.admin-client-sidebar *::before,
.admin-client-sidebar *::after {
    border: none !important;
    box-shadow: none !important;
}

.admin-client-sidebar .admin-client-sidebar-inner {
    padding: 0.7rem 0.85rem 1rem;
}

.admin-client-sidebar .admin-client-brand {
    padding: 0.35rem 0.55rem 0.95rem;
}

.admin-client-sidebar .admin-client-brand a {
    color: #ffffff !important;
    text-decoration: underline;
    font-size: 2rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    line-height: 1;
}

.admin-client-sidebar .admin-client-user {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.4rem 0.5rem 0.9rem;
}

.admin-client-sidebar .admin-client-avatar {
    width: 38px;
    height: 38px;
    border-radius: 999px;
    object-fit: cover;
}

.admin-client-sidebar .admin-client-name {
    color: #ffffff;
    font-size: 1.02rem;
    font-weight: 600;
    line-height: 1.1;
}

.admin-client-sidebar .admin-client-role {
    color: #ffffff;
    font-size: 0.95rem;
    opacity: 1;
}

.admin-client-sidebar .admin-client-section-title {
    color: #ffffff;
    text-transform: uppercase;
    letter-spacing: 1.6px;
    font-size: 0.77rem;
    font-weight: 700;
    padding: 0.85rem 0.55rem 0.45rem;
}

.admin-client-sidebar .admin-client-link {
    color: #ffffff !important;
    text-decoration: none !important;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.65rem;
    padding: 0.72rem 0.6rem;
    border-radius: 0.6rem;
    background: transparent !important;
}

.admin-client-sidebar .admin-client-link i {
    color: #ffffff !important;
    font-size: 1rem;
    width: 20px;
    text-align: center;
}

.admin-client-sidebar .admin-client-link span,
.admin-client-sidebar .admin-client-sublink,
.admin-client-sidebar .admin-client-sublink:visited,
.admin-client-sidebar .admin-client-sublink:focus,
.admin-client-sidebar .admin-client-sublink:hover {
    color: #ffffff !important;
    text-decoration: none !important;
}

.admin-client-sidebar .admin-client-summary-left {
    display: flex;
    align-items: center;
    gap: 0.65rem;
}

.admin-client-sidebar .admin-client-group {
    margin: 0;
}

.admin-client-sidebar .admin-client-group summary {
    list-style: none;
    cursor: pointer;
}

.admin-client-sidebar .admin-client-group summary::-webkit-details-marker {
    display: none;
}

.admin-client-sidebar .admin-client-arrow {
    color: #ffffff;
    transition: transform 0.15s ease;
    font-size: 0.9rem;
    line-height: 1;
}

.admin-client-sidebar details[open] .admin-client-arrow {
    transform: rotate(180deg);
}

.admin-client-sidebar .admin-client-submenu {
    padding: 0.18rem 0 0.52rem 0;
}

.admin-client-sidebar .admin-client-sublink {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-left: 2rem;
    padding: 0.44rem 0.55rem;
    border-radius: 0.5rem;
    background: transparent !important;
}

.admin-client-sidebar .admin-client-sublabel {
    display: inline-block;
    line-height: 1.2;
}

.admin-client-sidebar .admin-client-badge {
    min-width: 20px;
    height: 20px;
    padding: 0 0.45rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-left: auto;
    line-height: 20px;
    flex-shrink: 0;
    border-radius: 999px;
    background: #ffffff !important;
    color: #1f2f98 !important;
    font-size: 0.74rem;
    font-weight: 700;
}

.admin-client-sidebar .is-active {
    background: rgba(255, 255, 255, 0.12) !important;
}

.admin-client-sidebar .admin-client-link:hover,
.admin-client-sidebar .admin-client-link:focus,
.admin-client-sidebar .admin-client-sublink:hover,
.admin-client-sidebar .admin-client-sublink:focus {
    background: transparent !important;
}
</style>
