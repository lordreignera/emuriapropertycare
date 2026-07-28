@extends('client.layout')

@section('title', 'Client Dashboard')

@section('content')
@php
    $userName = Auth::user()->name ?? 'Client';
    $underDiagnosis = max((int) ($paidPendingInspectionsCount ?? 0), (int) ($pendingInspections ?? 0));
    $activeProperties = max((int) $propertiesCount - $underDiagnosis, 0);
    $inactiveProperties = 0;
    $statusTotal = max((int) $propertiesCount, 1);
    $activePct = round(($activeProperties / $statusTotal) * 100);
    $underDiagnosisPct = round(($underDiagnosis / $statusTotal) * 100);
    $inactivePct = max(0, 100 - $activePct - $underDiagnosisPct);
@endphp

<div class="dash-shell">
    <div class="dash-hero">
        <div>
            <h1>Welcome back, {{ $userName }}</h1>
            <p>Here is what is happening with your properties today.</p>
        </div>
        <a href="{{ route('client.properties.create') }}" class="dash-primary-action">
            <i class="mdi mdi-plus"></i>
            <span>Add Property</span>
        </a>
    </div>

    @if(!empty($findingsReadyInspections) && $findingsReadyInspections->isNotEmpty())
        <section class="dash-alert dash-alert-primary">
            <i class="mdi mdi-clipboard-text-search-outline"></i>
            <div>
                <strong>PHAR Findings Ready for Review</strong>
                <span>{{ $findingsReadyInspections->count() }} assessment{{ $findingsReadyInspections->count() === 1 ? '' : 's' }} need your decision.</span>
            </div>
            <a href="{{ route('client.inspections.findings-report', $findingsReadyInspections->first()) }}">Review PHAR Findings</a>
        </section>
    @endif

    @if(!empty($completedWithBalance) && $completedWithBalance->isNotEmpty())
        <section class="dash-alert dash-alert-warning">
            <i class="mdi mdi-alert-circle-outline"></i>
            <div>
                <strong>Outstanding balance</strong>
                <span>{{ $completedWithBalance->count() }} completed project{{ $completedWithBalance->count() === 1 ? ' has' : 's have' }} a remaining balance.</span>
            </div>
            <a href="{{ route('client.projects.index') }}">View Projects</a>
        </section>
    @endif

    <section class="dash-kpis">
        <article class="dash-kpi">
            <span class="dash-icon dash-icon-blue"><i class="mdi mdi-home-city-outline"></i></span>
            <div>
                <small>My Properties</small>
                <strong>{{ $propertiesCount }}</strong>
                <span>Total properties</span>
            </div>
            <a href="{{ route('client.properties.index') }}">View all <i class="mdi mdi-arrow-right"></i></a>
        </article>

        <article class="dash-kpi">
            <span class="dash-icon dash-icon-green"><i class="mdi mdi-clipboard-check-outline"></i></span>
            <div>
                <small>Diagnoses</small>
                <strong>{{ $paidInspectionsCount ?? 0 }}</strong>
                <span>{{ $paidPendingInspectionsCount ?? 0 }} scheduled, {{ $inspectionsCount }} completed</span>
            </div>
            <a href="{{ route('client.inspections.index') }}">View diagnoses <i class="mdi mdi-arrow-right"></i></a>
        </article>

        <article class="dash-kpi">
            <span class="dash-icon dash-icon-purple"><i class="mdi mdi-briefcase-outline"></i></span>
            <div>
                <small>Projects</small>
                <strong>{{ $projectsCount }}</strong>
                <span>Ongoing projects</span>
            </div>
            <a href="{{ route('client.projects.index') }}">View projects <i class="mdi mdi-arrow-right"></i></a>
        </article>

        <article class="dash-kpi">
            <span class="dash-icon dash-icon-orange"><i class="mdi mdi-file-document-outline"></i></span>
            <div>
                <small>Invoices</small>
                <strong>{{ $invoicesCount }}</strong>
                <span>{{ $unpaidInvoices }} unpaid invoices</span>
            </div>
            <a href="{{ route('client.invoices.index') }}">View invoices <i class="mdi mdi-arrow-right"></i></a>
        </article>

        <article class="dash-kpi">
            <span class="dash-icon dash-icon-cyan"><i class="mdi mdi-credit-card-outline"></i></span>
            <div>
                <small>Payments</small>
                <strong>{{ $unpaidInvoices }}</strong>
                <span>Due payments</span>
            </div>
            <a href="{{ route('client.invoices.index') }}">View payments <i class="mdi mdi-arrow-right"></i></a>
        </article>
    </section>

    <div class="dash-grid dash-grid-stacked">
        <div class="dash-column">
            <section class="dash-panel">
                <div class="dash-panel-head">
                    <div>
                        <h2>My Properties</h2>
                        <p>Overview of your registered properties.</p>
                    </div>
                    <a href="{{ route('client.properties.index') }}" class="dash-small-button">View All <i class="mdi mdi-arrow-right"></i></a>
                </div>

                @if($recentProperties->count() > 0)
                    <div class="dash-table-wrap">
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Type</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentProperties as $property)
                                    @php
                                        $propertyTypeLabel = match($property->type) {
                                            'mixed_use' => 'Mixed Use',
                                            'residential' => 'Residential',
                                            'commercial' => 'Commercial',
                                            default => 'Not Set',
                                        };
                                        $propertyStatus = ucfirst(str_replace('_', ' ', (string) ($property->status ?? 'registered')));
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="dash-property-cell">
                                                <span><i class="mdi mdi-domain"></i></span>
                                                <div>
                                                    <strong>{{ $property->property_name }}</strong>
                                                    <small>ID: {{ $property->property_code }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="dash-pill dash-pill-blue">{{ $propertyTypeLabel }}</span></td>
                                        <td><i class="mdi mdi-map-marker-outline text-muted"></i> {{ $property->city ?? 'N/A' }}, {{ $property->country ?? 'N/A' }}</td>
                                        <td><span class="dash-pill dash-pill-green">{{ $propertyStatus }}</span></td>
                                        <td class="text-end">
                                            <a href="{{ route('client.properties.show', $property->id) }}" class="dash-icon-button" title="View property">
                                                <i class="mdi mdi-eye-outline"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="dash-empty">
                        <i class="mdi mdi-home-plus-outline"></i>
                        <strong>No properties yet</strong>
                        <span>Register your first property to begin.</span>
                    </div>
                @endif
            </section>

            <section class="dash-panel">
                <div class="dash-panel-head">
                    <div>
                        <h2>Property Status Overview</h2>
                        <p>Distribution of your properties by status.</p>
                    </div>
                </div>
                <div class="dash-donut-row">
                    <div class="dash-donut" style="--active: {{ $activePct }}; --inspect: {{ $underDiagnosisPct }};">
                        <strong>{{ $propertiesCount }}</strong>
                        <span>Total</span>
                    </div>
                    <div class="dash-legend">
                        <span><i class="legend-dot legend-blue"></i>Active <strong>{{ $activeProperties }} ({{ $activePct }}%)</strong></span>
                        <span><i class="legend-dot legend-orange"></i>Under Diagnosis <strong>{{ $underDiagnosis }} ({{ $underDiagnosisPct }}%)</strong></span>
                        <span><i class="legend-dot legend-gray"></i>Inactive <strong>{{ $inactiveProperties }} ({{ $inactivePct }}%)</strong></span>
                    </div>
                </div>
            </section>

            <section class="dash-panel">
                <div class="dash-panel-head">
                    <div>
                        <h2>Recent Activity</h2>
                        <p>Latest updates and activities.</p>
                    </div>
                    <a href="{{ route('client.notifications.index') }}" class="dash-small-button">View All <i class="mdi mdi-arrow-right"></i></a>
                </div>
                <div class="dash-activity-list">
                    @forelse(($recentClientActivities ?? collect()) as $activity)
                        <div class="dash-activity">
                            <span class="dash-icon dash-icon-{{ $activity->tone ?? 'blue' }}"><i class="mdi {{ $activity->icon ?? 'mdi-bell-outline' }}"></i></span>
                            <div>
                                <strong>{{ $activity->title }}</strong>
                                <small>{{ $activity->description }}</small>
                            </div>
                            <time>{{ optional($activity->created_at)->diffForHumans() }}</time>
                        </div>
                    @empty
                        <div class="dash-empty dash-empty-small">
                            <i class="mdi mdi-bell-outline"></i>
                            <strong>No recent activity</strong>
                            <span>New updates will appear here.</span>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="dash-column">
            <section class="dash-panel">
                <div class="dash-panel-head">
                    <div>
                        <h2>Quick Actions</h2>
                        <p>Common tasks and shortcuts.</p>
                    </div>
                </div>
                <div class="dash-action-list">
                    <a href="{{ route('client.properties.create') }}">
                        <span class="dash-icon dash-icon-blue"><i class="mdi mdi-home-plus-outline"></i></span>
                        <div><strong>Add New Property</strong><small>Register a new property</small></div>
                        <i class="mdi mdi-chevron-right"></i>
                    </a>
                    <a href="{{ route('client.properties.index') }}">
                        <span class="dash-icon dash-icon-green"><i class="mdi mdi-cube-scan"></i></span>
                        <div><strong>Property Facts</strong><small>Track onboarding and diagnosis</small></div>
                        <i class="mdi mdi-chevron-right"></i>
                    </a>
                    <a href="{{ route('client.invoices.index') }}">
                        <span class="dash-icon dash-icon-orange"><i class="mdi mdi-file-document-outline"></i></span>
                        <div><strong>View Invoices</strong><small>Check billing and payments</small></div>
                        <i class="mdi mdi-chevron-right"></i>
                    </a>
                    <a href="{{ route('client.service-requests.create') }}">
                        <span class="dash-icon dash-icon-purple"><i class="mdi mdi-alert-circle-outline"></i></span>
                        <div><strong>Report Issue</strong><small>Submit a repair request</small></div>
                        <i class="mdi mdi-chevron-right"></i>
                    </a>
                </div>
            </section>

            <section class="dash-panel">
                <div class="dash-panel-head">
                    <div>
                        <h2>Upcoming Diagnoses</h2>
                        <p>Your next scheduled property diagnoses.</p>
                    </div>
                </div>
                @if(($upcomingInspections ?? collect())->isNotEmpty())
                    <div class="dash-mini-list">
                        @foreach($upcomingInspections as $inspection)
                            <a href="{{ route('client.inspections.index') }}">
                                <span class="dash-icon dash-icon-green"><i class="mdi mdi-calendar-clock-outline"></i></span>
                                <div>
                                    <strong>{{ $inspection->property?->property_name ?? $inspection->property_name ?? 'Diagnosis' }}</strong>
                                    <small>{{ optional($inspection->scheduled_date)->format('M d, Y g:i A') }}</small>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="dash-empty dash-empty-small">
                        <i class="mdi mdi-calendar-check-outline"></i>
                        <strong>No upcoming diagnoses</strong>
                        <span>You are all caught up.</span>
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>

@include('shared.dashboard-design-system')
@endsection
