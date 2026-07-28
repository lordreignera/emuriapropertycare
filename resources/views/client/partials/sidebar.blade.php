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

    $findingsReadyCount = \App\Models\Inspection::whereIn('property_id', $clientPropertyIds)
        ->whereNotNull('findings_report_shared_at')
        ->whereNull('client_committed_at')
        ->where('status', '!=', 'completed')
        ->count();

    $unpaidInvoicesCount = \App\Models\Invoice::where('user_id', Auth::id())
        ->whereIn('status', ['draft', 'sent', 'partial', 'overdue'])
        ->count();
    $openServiceRequestsCount = \App\Models\ServiceRequest::where('user_id', Auth::id())
        ->whereNotIn('status', ['resolved', 'cancelled'])
        ->count();

    $propertyOpen = request()->routeIs('client.properties.*');
    $servicesOpen = request()->routeIs('client.inspections.*') || request()->routeIs('inspections.digital-twin') || request()->routeIs('client.projects.*') || request()->routeIs('client.service-requests.*');
    $billingOpen = request()->routeIs('client.invoices.*') || request()->routeIs('client.subscription.*');
    $supportOpen = request()->routeIs('client.complaints.*') || request()->routeIs('client.emergency-reports.*') || request()->routeIs('client.support');
@endphp

<nav class="sidebar sidebar-offcanvas client-clean-sidebar" id="sidebar">
    <div class="client-sidebar-inner">
        <div class="client-brand">
            <a href="{{ route('dashboard') }}" aria-label="ETOGO dashboard">
                <img class="client-brand-logo" src="{{ asset('etogo%20log.png') }}" alt="ETOGO">
            </a>
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
                    <span>Property Registry</span>
                </span>
                <span class="client-arrow">▾</span>
            </summary>
            <div class="client-submenu">
                <a class="client-sublink {{ request()->routeIs('client.properties.create') ? 'is-active' : '' }}" href="{{ route('client.properties.create') }}">Register Property</a>
                <a class="client-sublink {{ request()->routeIs('client.properties.index') || request()->routeIs('client.properties.show') || request()->routeIs('client.properties.edit') ? 'is-active' : '' }}" href="{{ route('client.properties.index') }}">My Property Registry</a>
            </div>
        </details>

        <details class="client-group" {{ $servicesOpen ? 'open' : '' }}>
            <summary class="client-link {{ $servicesOpen ? 'is-active' : '' }}">
                <span class="client-summary-left">
                    <i class="mdi mdi-clipboard-check icon-info"></i>
                    <span>Stewardship Workflow</span>
                </span>
                <span class="client-arrow">▾</span>
            </summary>
            <div class="client-submenu">
                <a class="client-sublink {{ request()->routeIs('client.inspections.*') ? 'is-active' : '' }}" href="{{ route('client.inspections.index') }}">
                    <span class="client-sublabel">PHAR Assessments</span>
                    @if($findingsReadyCount > 0)
                        <span class="client-badge">{{ $findingsReadyCount }}</span>
                    @elseif($scheduledInspectionsCount > 0)
                        <span class="client-badge">{{ $scheduledInspectionsCount }}</span>
                    @endif
                </a>
                @if($findingsReadyCount > 0)
                    <a class="client-sublink {{ request()->routeIs('client.inspections.*') ? 'is-active' : '' }}" href="{{ route('client.inspections.index') }}">
                        <span class="client-sublabel">Findings to Review</span>
                        <span class="client-badge">{{ $findingsReadyCount }}</span>
                    </a>
                @endif
                    <a class="client-sublink {{ request()->routeIs('client.inspections.quotations') ? 'is-active' : '' }}" href="{{ route('client.inspections.quotations') }}">
                        <span class="client-sublabel">Remediation Proposals</span>
                        @if($quotationReadyCount > 0)
                            <span class="client-badge">{{ $quotationReadyCount }}</span>
                        @endif
                    </a>
                <a class="client-sublink {{ request()->fullUrlIs('*service-requests/create*type=addendum*') ? 'is-active' : '' }}" href="{{ route('client.service-requests.create', ['type' => 'addendum']) }}">
                    <span class="client-sublabel">Owner Update / Add Work</span>
                </a>
                <a class="client-sublink {{ request()->routeIs('client.projects.*') ? 'is-active' : '' }}" href="{{ route('client.projects.index') }}">
                    <span class="client-sublabel">Remediation Projects</span>
                    @if($activeProjectsCount > 0)
                        <span class="client-badge">{{ $activeProjectsCount }}</span>
                    @endif
                </a>
                <a class="client-sublink {{ request()->routeIs('client.service-requests.*') ? 'is-active' : '' }}" href="{{ route('client.service-requests.index') }}">
                    <span class="client-sublabel">Owner Requests</span>
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
        <button type="button" class="client-version-button" aria-label="Application version 1.0">
            <span class="client-version-label">Version</span>
            <span class="client-version-badge">1.0</span>
        </button>
    </div>
</nav>
