@php
    $clientPropertyIds = \App\Models\Property::where('user_id', Auth::id())->pluck('id');
    $clientProjectIds = \App\Models\Project::whereIn('property_id', $clientPropertyIds)->pluck('id');

    $scheduledInspectionsCount = \App\Models\Inspection::whereIn('project_id', $clientProjectIds)
        ->where('status', 'scheduled')
        ->count();

    $activeProjectsCount = \App\Models\Project::whereIn('property_id', $clientPropertyIds)
        ->where('status', 'active')
        ->count();

        $quotationReadyCount = \App\Models\Inspection::whereIn('property_id', $clientPropertyIds)
            ->whereIn('quotation_status', ['shared', 'client_reviewing', 'approved'])
            ->count();

    $unpaidInvoicesCount = \App\Models\Invoice::where('user_id', Auth::id())
        ->whereIn('status', ['draft', 'sent', 'partial', 'overdue'])
        ->count();
    $openServiceRequestsCount = \App\Models\ServiceRequest::where('user_id', Auth::id())
        ->whereNotIn('status', ['resolved', 'cancelled'])
        ->count();

    $propertyOpen = request()->routeIs('client.properties.*') || request()->routeIs('client.tenants.*');
    $servicesOpen = request()->routeIs('client.inspections.*') || request()->routeIs('client.projects.*') || request()->routeIs('client.service-requests.*');
    $billingOpen = request()->routeIs('client.invoices.*') || request()->routeIs('client.subscription.*');
    $supportOpen = request()->routeIs('client.complaints.*') || request()->routeIs('client.emergency-reports.*') || request()->routeIs('client.support');
@endphp

<nav class="sidebar sidebar-offcanvas client-clean-sidebar" id="sidebar">
    <div class="client-sidebar-inner">
        <div class="client-brand">
            <a href="{{ route('dashboard') }}">EMURIA</a>
        </div>

        <div class="client-user">
            <img class="client-avatar" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}">
            <div>
                <div class="client-name">{{ Auth::user()->name }}</div>
                <div class="client-role">Client Dashboard</div>
            </div>
        </div>

        <a class="client-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" href="{{ route('dashboard') }}">
            <span class="client-summary-left">
                <i class="mdi mdi-view-dashboard icon-success"></i>
                <span>Dashboard</span>
            </span>
        </a>
        <details class="client-group" {{ $propertyOpen ? 'open' : '' }}>
            <summary class="client-link {{ $propertyOpen ? 'is-active' : '' }}">
                <span class="client-summary-left">
                    <i class="mdi mdi-home-city icon-primary"></i>
                    <span>Properties</span>
                </span>
                <span class="client-arrow">▾</span>
            </summary>
            <div class="client-submenu">
                <a class="client-sublink {{ request()->routeIs('client.properties.create') ? 'is-active' : '' }}" href="{{ route('client.properties.create') }}">Add Property</a>
                <a class="client-sublink {{ request()->routeIs('client.properties.index') || request()->routeIs('client.properties.show') || request()->routeIs('client.properties.edit') ? 'is-active' : '' }}" href="{{ route('client.properties.index') }}">My Properties</a>
                <a class="client-sublink {{ request()->routeIs('client.tenants.*') ? 'is-active' : '' }}" href="{{ route('client.tenants.index') }}">Tenants</a>
            </div>
        </details>

        <details class="client-group" {{ $servicesOpen ? 'open' : '' }}>
            <summary class="client-link {{ $servicesOpen ? 'is-active' : '' }}">
                <span class="client-summary-left">
                    <i class="mdi mdi-clipboard-check icon-info"></i>
                    <span>Services</span>
                </span>
                <span class="client-arrow">▾</span>
            </summary>
            <div class="client-submenu">
                <a class="client-sublink {{ request()->routeIs('client.inspections.*') ? 'is-active' : '' }}" href="{{ route('client.inspections.index') }}">
                    <span class="client-sublabel">Inspections</span>
                    @if($scheduledInspectionsCount > 0)
                        <span class="client-badge">{{ $scheduledInspectionsCount }}</span>
                    @endif
                </a>
                    <a class="client-sublink {{ request()->routeIs('client.inspections.quotations') ? 'is-active' : '' }}" href="{{ route('client.inspections.quotations') }}">
                        <span class="client-sublabel">Quotations</span>
                        @if($quotationReadyCount > 0)
                            <span class="client-badge">{{ $quotationReadyCount }}</span>
                        @endif
                    </a>
                <a class="client-sublink {{ request()->routeIs('client.projects.*') ? 'is-active' : '' }}" href="{{ route('client.projects.index') }}">
                    <span class="client-sublabel">Projects Preview</span>
                    @if($activeProjectsCount > 0)
                        <span class="client-badge">{{ $activeProjectsCount }}</span>
                    @endif
                </a>
                <a class="client-sublink {{ request()->routeIs('client.service-requests.*') ? 'is-active' : '' }}" href="{{ route('client.service-requests.index') }}">
                    <span class="client-sublabel">Service Requests</span>
                    @if($openServiceRequestsCount > 0)
                        <span class="client-badge">{{ $openServiceRequestsCount }}</span>
                    @endif
                </a>
            </div>
        </details>

        <details class="client-group" {{ $billingOpen ? 'open' : '' }}>
            <summary class="client-link {{ $billingOpen ? 'is-active' : '' }}">
                <span class="client-summary-left">
                    <i class="mdi mdi-cash-multiple icon-warning"></i>
                    <span>Billing & Finance</span>
                </span>
                <span class="client-arrow">▾</span>
            </summary>
            <div class="client-submenu">
                <a class="client-sublink {{ request()->routeIs('client.invoices.*') ? 'is-active' : '' }}" href="{{ route('client.invoices.index') }}">
                    <span class="client-sublabel">Invoices</span>
                    @if($unpaidInvoicesCount > 0)
                        <span class="client-badge">{{ $unpaidInvoicesCount }}</span>
                    @endif
                </a>
                <a class="client-sublink {{ request()->routeIs('client.subscription.*') ? 'is-active' : '' }}" href="{{ route('client.subscription.show') }}">My Subscription</a>
            </div>
        </details>

        <details class="client-group" {{ $supportOpen ? 'open' : '' }}>
            <summary class="client-link {{ $supportOpen ? 'is-active' : '' }}">
                <span class="client-summary-left">
                    <i class="mdi mdi-lifebuoy icon-danger"></i>
                    <span>Help & Support</span>
                </span>
                <span class="client-arrow">▾</span>
            </summary>
            <div class="client-submenu">
                <a class="client-sublink {{ request()->routeIs('client.complaints.*') ? 'is-active' : '' }}" href="{{ route('client.complaints.index') }}">Complaints</a>
                <a class="client-sublink {{ request()->routeIs('client.emergency-reports.*') ? 'is-active' : '' }}" href="{{ route('client.emergency-reports.index') }}">Emergency Reports</a>
                <a class="client-sublink {{ request()->routeIs('client.support') ? 'is-active' : '' }}" href="{{ route('client.support') }}">Contact Support</a>
            </div>
        </details>

        <details class="client-group">
            <summary class="client-link">
                <span class="client-summary-left">
                    <i class="mdi mdi-book-open-page-variant icon-success"></i>
                    <span>Resources</span>
                </span>
                <span class="client-arrow">▾</span>
            </summary>
            <div class="client-submenu">
                <a class="client-sublink" href="{{ asset('docs/client-welcome.html') }}" target="_blank" rel="noopener noreferrer">✉️ Welcome Letter</a>
                <a class="client-sublink" href="{{ asset('docs/client-guide.html') }}" target="_blank" rel="noopener noreferrer">📖 Client Guide</a>
                <a class="client-sublink" href="{{ asset('docs/agreement-guide.html') }}" target="_blank" rel="noopener noreferrer">📄 Agreement Guide</a>
            </div>
        </details>
    </div>
    <div class="client-version-bar">
        <span>EMURIA</span> <span class="client-version-badge">v1.0</span>
    </div>
</nav>

<style>
.client-clean-sidebar,
body .client-clean-sidebar,
body.light-theme .client-clean-sidebar {
    width: 280px !important;
    background: #eaf4ff !important;
    border-right: 1px solid #c8dff4 !important;
    box-shadow: 4px 0 14px rgba(28, 58, 92, .045) !important;
}

.client-clean-sidebar,
.client-clean-sidebar * {
    letter-spacing: 0 !important;
}

.client-clean-sidebar .client-sidebar-inner {
    padding: 18px 12px 16px !important;
}

.client-clean-sidebar .client-brand {
    display: flex !important;
    justify-content: center !important;
    padding: 0 0 18px !important;
}

.client-clean-sidebar .client-brand a {
    width: 64px !important;
    height: 64px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    background: #fff !important;
    border-radius: 8px !important;
    box-shadow: 0 3px 10px rgba(28, 58, 92, .08) !important;
    text-decoration: none !important;
}

.client-clean-sidebar .client-brand-logo {
    width: 40px !important;
    height: auto !important;
    object-fit: contain !important;
}

.client-clean-sidebar .client-user {
    display: flex !important;
    align-items: center !important;
    gap: .7rem !important;
    margin-bottom: .6rem !important;
    padding: 12px !important;
    background: rgba(255,255,255,.68) !important;
    border: 1px solid #d7e7f7 !important;
    border-radius: 8px !important;
    box-shadow: none !important;
}

.client-clean-sidebar .client-avatar {
    width: 34px !important;
    height: 34px !important;
    border-radius: 999px !important;
    object-fit: cover !important;
}

.client-clean-sidebar .client-name {
    color: #172033 !important;
    font-size: .9rem !important;
    font-weight: 800 !important;
    line-height: 1.15 !important;
}

.client-clean-sidebar .client-role,
.client-clean-sidebar .client-section-title {
    color: #667085 !important;
}

.client-clean-sidebar .client-role {
    font-size: .8rem !important;
    line-height: 1.2 !important;
}

.client-clean-sidebar .client-section-title {
    padding: 1rem .55rem .45rem !important;
    font-size: .68rem !important;
    font-weight: 850 !important;
    text-transform: uppercase !important;
    letter-spacing: .16em !important;
}

.client-clean-sidebar .client-link,
.client-clean-sidebar .client-sublink {
    display: flex !important;
    align-items: center !important;
    gap: .7rem !important;
    color: #172033 !important;
    text-decoration: none !important;
    border-radius: 7px !important;
    box-shadow: none !important;
    font-weight: 700 !important;
}

.client-clean-sidebar .client-link {
    min-height: 44px !important;
    padding: .55rem !important;
    justify-content: flex-start !important;
}

.client-clean-sidebar .client-summary-left {
    display: flex !important;
    align-items: center !important;
    gap: .7rem !important;
    flex: 1 !important;
    min-width: 0 !important;
}

.client-clean-sidebar .client-link span,
.client-clean-sidebar .client-sublink,
.client-clean-sidebar .client-sublink:visited,
.client-clean-sidebar .client-arrow {
    color: #172033 !important;
}

.client-clean-sidebar .client-link i,
.client-clean-sidebar .icon-success,
.client-clean-sidebar .icon-primary,
.client-clean-sidebar .icon-info,
.client-clean-sidebar .icon-warning,
.client-clean-sidebar .icon-danger {
    width: 30px !important;
    height: 30px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0 !important;
    background: #dbeafa !important;
    color: #344054 !important;
    border-radius: 7px !important;
    box-shadow: none !important;
}

.client-clean-sidebar .client-group {
    margin: 0 !important;
}

.client-clean-sidebar .client-group summary {
    list-style: none !important;
    cursor: pointer !important;
}

.client-clean-sidebar .client-group summary::-webkit-details-marker {
    display: none !important;
}

.client-clean-sidebar .client-arrow {
    margin-left: auto !important;
    opacity: .72 !important;
    transition: transform .15s ease !important;
}

.client-clean-sidebar details[open] .client-arrow {
    transform: rotate(180deg) !important;
}

.client-clean-sidebar .client-submenu {
    padding: .18rem 0 .52rem 0 !important;
}

.client-clean-sidebar .client-sublink {
    justify-content: space-between !important;
    margin-left: 2.65rem !important;
    padding: .42rem .5rem !important;
    font-size: .86rem !important;
    color: #344054 !important;
}

.client-clean-sidebar .client-link:hover,
.client-clean-sidebar .client-sublink:hover {
    background: rgba(255,255,255,.58) !important;
}

.client-clean-sidebar .client-link.is-active,
.client-clean-sidebar .client-sublink.is-active,
.client-clean-sidebar .is-active {
    background: #ffffff !important;
    color: #172033 !important;
    border-left: 3px solid #2458d6 !important;
    box-shadow: 0 3px 10px rgba(28, 58, 92, .055) !important;
}

.client-clean-sidebar .client-link.is-active i,
.client-clean-sidebar .client-link:hover i {
    background: #ffffff !important;
    color: #2458d6 !important;
}

.client-clean-sidebar .client-badge {
    min-width: 20px !important;
    height: 20px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 0 .45rem !important;
    border-radius: 999px !important;
    background: #2458d6 !important;
    color: #ffffff !important;
    font-size: .72rem !important;
    font-weight: 800 !important;
}

.client-version-bar {
    padding: 10px 14px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    border-top: 1px solid #c8dff4 !important;
    color: #667085 !important;
    font-size: .72rem !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
}

.client-version-badge {
    background: #dbeafa !important;
    color: #344054 !important;
    border-radius: 999px !important;
    padding: 2px 9px !important;
}
</style>
