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
    background: linear-gradient(180deg, #1f2f98 0%, #1a2a86 55%, #183075 100%) !important;
    border: none !important;
    box-shadow: none !important;
}

.client-clean-sidebar,
.client-clean-sidebar *,
.client-clean-sidebar *::before,
.client-clean-sidebar *::after {
    border: none !important;
    box-shadow: none !important;
}

.client-clean-sidebar .client-sidebar-inner {
    padding: 0.7rem 0.85rem 1rem;
}

.client-clean-sidebar .client-brand {
    padding: 0.35rem 0.55rem 0.95rem;
}

.client-clean-sidebar .client-brand a {
    color: #ffffff !important;
    text-decoration: underline;
    font-size: 2rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    line-height: 1;
}

.client-clean-sidebar .client-user {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.4rem 0.5rem 0.9rem;
}

.client-clean-sidebar .client-avatar {
    width: 38px;
    height: 38px;
    border-radius: 999px;
    object-fit: cover;
}

.client-clean-sidebar .client-name {
    color: #ffffff;
    font-size: 1.02rem;
    font-weight: 600;
    line-height: 1.1;
}

.client-clean-sidebar .client-role {
    color: #ffffff;
    font-size: 0.95rem;
    opacity: 1;
}

.client-clean-sidebar .client-section-title {
    color: #ffffff;
    text-transform: uppercase;
    letter-spacing: 1.6px;
    font-size: 0.77rem;
    font-weight: 700;
    padding: 0.85rem 0.55rem 0.45rem;
}

.client-clean-sidebar .client-link {
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

.client-clean-sidebar .client-link i {
    color: #ffffff !important;
    font-size: 1rem;
    width: 20px;
    text-align: center;
}

.client-clean-sidebar .client-link span,
.client-clean-sidebar .client-sublink,
.client-clean-sidebar .client-sublink:visited,
.client-clean-sidebar .client-sublink:focus,
.client-clean-sidebar .client-sublink:hover {
    color: #ffffff !important;
    text-decoration: none !important;
}

.client-clean-sidebar .client-summary-left {
    display: flex;
    align-items: center;
    gap: 0.65rem;
}

.client-clean-sidebar .client-group {
    margin: 0;
}

.client-clean-sidebar .client-group summary {
    list-style: none;
    cursor: pointer;
}

.client-clean-sidebar .client-group summary::-webkit-details-marker {
    display: none;
}

.client-clean-sidebar .client-arrow {
    color: #ffffff;
    transition: transform 0.15s ease;
    font-size: 0.9rem;
    line-height: 1;
}

.client-clean-sidebar details[open] .client-arrow {
    transform: rotate(180deg);
}

.client-clean-sidebar .client-submenu {
    padding: 0.18rem 0 0.52rem 0;
}

.client-clean-sidebar .client-sublink {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-left: 2rem;
    padding: 0.44rem 0.55rem;
    border-radius: 0.5rem;
    background: transparent !important;
}

.client-clean-sidebar .client-sublabel {
    display: inline-block;
    line-height: 1.2;
}

.client-clean-sidebar .client-badge {
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

.client-clean-sidebar .is-active {
    background: rgba(255, 255, 255, 0.12) !important;
}

.client-clean-sidebar .client-link:hover,
.client-clean-sidebar .client-link:focus,
.client-clean-sidebar .client-sublink:hover,
.client-clean-sidebar .client-sublink:focus {
    background: transparent !important;
}

/* Color-coded icon chips */
.client-clean-sidebar .icon-success {
    background: rgba(40, 167, 69, 0.2) !important;
    color: #28a745 !important;
    border-radius: 0.35rem;
    padding: 0.4rem 0.45rem !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px !important;
    height: 30px !important;
}

.client-clean-sidebar .icon-primary {
    background: rgba(0, 123, 255, 0.2) !important;
    color: #007bff !important;
    border-radius: 0.35rem;
    padding: 0.4rem 0.45rem !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px !important;
    height: 30px !important;
}

.client-clean-sidebar .icon-info {
    background: rgba(17, 182, 214, 0.2) !important;
    color: #11b6d6 !important;
    border-radius: 0.35rem;
    padding: 0.4rem 0.45rem !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px !important;
    height: 30px !important;
}

.client-clean-sidebar .icon-warning {
    background: rgba(255, 193, 7, 0.2) !important;
    color: #ffc107 !important;
    border-radius: 0.35rem;
    padding: 0.4rem 0.45rem !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px !important;
    height: 30px !important;
}

.client-clean-sidebar .icon-danger {
    background: rgba(220, 53, 69, 0.2) !important;
    color: #dc3545 !important;
    border-radius: 0.35rem;
    padding: 0.4rem 0.45rem !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px !important;
    height: 30px !important;
}

.client-version-bar {
    padding: 10px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-top: 1px solid rgba(255,255,255,.12);
    margin-top: auto;
    font-size: .72rem;
    color: rgba(255,255,255,.45);
    letter-spacing: .04em;
    font-weight: 600;
    text-transform: uppercase;
}

.client-version-badge {
    background: rgba(255,255,255,.12);
    color: rgba(255,255,255,.65);
    border-radius: 20px;
    padding: 2px 9px;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .06em;
}

</style>
