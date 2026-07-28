@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
@php
    $user = auth()->user();
    $isStoreManager = $user->hasRole('Store Manager');
@endphp

<div class="ops-shell">
    <div class="ops-hero">
        <div>
            <h1>Welcome back, {{ $user->name }}</h1>
            <p>{{ $isStoreManager ? 'Tool stock, assignments and returns at a glance.' : 'Here is what is happening across ETOGO operations today.' }}</p>
        </div>
        <div class="ops-hero-actions">
            @if($isStoreManager)
                <a href="{{ route('admin.tool-settings.create') }}" class="ops-primary-action">
                    <i class="mdi mdi-plus"></i>
                    <span>Add Tool</span>
                </a>
            @else
                @can('create properties')
                    <a href="{{ route('properties.create') }}" class="ops-primary-action">
                        <i class="mdi mdi-plus"></i>
                        <span>Add Property</span>
                    </a>
                @endcan
            @endif
        </div>
    </div>

    @if($isStoreManager)
        <section class="ops-kpis">
            <article class="ops-kpi">
                <span class="ops-icon ops-icon-blue"><i class="mdi mdi-toolbox-outline"></i></span>
                <div><small>Total Tools</small><strong>{{ $totalTools ?? 0 }}</strong><span>Active tool records</span></div>
                <a href="{{ route('admin.tool-settings.index') }}">View tools <i class="mdi mdi-arrow-right"></i></a>
            </article>
            <article class="ops-kpi">
                <span class="ops-icon ops-icon-green"><i class="mdi mdi-wrench-outline"></i></span>
                <div><small>Tools In Use</small><strong>{{ $toolsInUse ?? 0 }}</strong><span>Units currently deployed</span></div>
                <a href="{{ route('tool-assignments.index') }}">Assignments <i class="mdi mdi-arrow-right"></i></a>
            </article>
            <article class="ops-kpi">
                <span class="ops-icon ops-icon-purple"><i class="mdi mdi-check-decagram-outline"></i></span>
                <div><small>Tools Owned</small><strong>{{ $toolsOwned ?? 0 }}</strong><span>Company-owned tools</span></div>
                <a href="{{ route('admin.tool-settings.index') }}?ownership_status=owned">View owned <i class="mdi mdi-arrow-right"></i></a>
            </article>
            <article class="ops-kpi">
                <span class="ops-icon ops-icon-orange"><i class="mdi mdi-handshake-outline"></i></span>
                <div><small>Tools Hired</small><strong>{{ $toolsHired ?? 0 }}</strong><span>Hired or rented tools</span></div>
                <a href="{{ route('admin.tool-settings.index') }}?ownership_status=hired">View hired <i class="mdi mdi-arrow-right"></i></a>
            </article>
        </section>

        <div class="ops-grid">
            <section class="ops-panel">
                <div class="ops-panel-head">
                    <div><h2>Tool Operations</h2><p>Queues that need store attention.</p></div>
                </div>
                <div class="ops-action-list">
                    <a href="{{ route('tool-assignments.index') }}">
                        <span class="ops-icon ops-icon-orange"><i class="mdi mdi-clock-alert-outline"></i></span>
                        <div><strong>Pending Tool Assignment</strong><small>{{ $pendingToolAssignment ?? 0 }} diagnosis job{{ ($pendingToolAssignment ?? 0) === 1 ? '' : 's' }} waiting</small></div>
                        <b>{{ $pendingToolAssignment ?? 0 }}</b>
                    </a>
                    <a href="{{ route('admin.tool-settings.index') }}?availability_status=available">
                        <span class="ops-icon ops-icon-green"><i class="mdi mdi-check-circle-outline"></i></span>
                        <div><strong>Available Tools</strong><small>Ready for assignment</small></div>
                        <b>{{ $availableTools ?? 0 }}</b>
                    </a>
                    <a href="{{ route('tool-assignments.index') }}">
                        <span class="ops-icon ops-icon-blue"><i class="mdi mdi-swap-horizontal"></i></span>
                        <div><strong>Unreturned Assignments</strong><small>Open return records</small></div>
                        <b>{{ $unreturnedRecords ?? 0 }}</b>
                    </a>
                </div>
            </section>

            <section class="ops-panel">
                <div class="ops-panel-head">
                    <div><h2>Quick Actions</h2><p>Common store workflows.</p></div>
                </div>
                <div class="ops-action-list">
                    <a href="{{ route('tool-assignments.index') }}"><span class="ops-icon ops-icon-blue"><i class="mdi mdi-toolbox-outline"></i></span><div><strong>Assign / Return Tools</strong><small>Manage active assignments</small></div><i class="mdi mdi-chevron-right"></i></a>
                    <a href="{{ route('admin.tool-settings.index') }}"><span class="ops-icon ops-icon-green"><i class="mdi mdi-cog-outline"></i></span><div><strong>Manage Tool Settings</strong><small>Stock, ownership and rates</small></div><i class="mdi mdi-chevron-right"></i></a>
                    <a href="{{ route('inspections.index') }}?view=pending-etogo"><span class="ops-icon ops-icon-purple"><i class="mdi mdi-format-list-checks"></i></span><div><strong>Awaiting Assignment</strong><small>Ready for tool planning</small></div><i class="mdi mdi-chevron-right"></i></a>
                </div>
            </section>

            <section class="ops-panel ops-panel-wide">
                <div class="ops-panel-head">
                    <div><h2>Recent Tool Assignments</h2><p>Latest movement from the tool room.</p></div>
                    <a href="{{ route('tool-assignments.index') }}" class="ops-small-button">View All <i class="mdi mdi-arrow-right"></i></a>
                </div>
                <div class="ops-table-wrap">
                    <table class="ops-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Tool</th>
                                <th>Property</th>
                                <th>Qty</th>
                                <th>Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentAssignments ?? [] as $assignment)
                                <tr>
                                    <td>{{ optional($assignment->created_at)->format('M d, Y') }}</td>
                                    <td><strong>{{ $assignment->tool_name ?? $assignment->toolSetting?->tool_name ?? 'N/A' }}</strong></td>
                                    <td>{{ $assignment->inspection?->property?->property_name ?? $assignment->inspection?->property?->property_code ?? 'N/A' }}</td>
                                    <td>{{ $assignment->quantity }}</td>
                                    <td><span class="ops-pill ops-pill-blue">{{ ucfirst($assignment->ownership_status ?? 'owned') }}</span></td>
                                    <td>
                                        @if($assignment->returned_at)
                                            <span class="ops-pill ops-pill-green">Returned</span>
                                        @else
                                            <span class="ops-pill ops-pill-orange">In Use</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><div class="ops-empty ops-empty-row">No tool assignments yet.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    @else
        <section class="ops-kpis">
            <article class="ops-kpi">
                <span class="ops-icon ops-icon-blue"><i class="mdi mdi-home-city-outline"></i></span>
                <div><small>Properties</small><strong>{{ $propertiesCount ?? 0 }}</strong><span>Registered properties</span></div>
                <a href="{{ route('properties.index') }}">View all <i class="mdi mdi-arrow-right"></i></a>
            </article>
            <article class="ops-kpi">
                <span class="ops-icon ops-icon-green"><i class="mdi mdi-clipboard-check-outline"></i></span>
                <div><small>Diagnoses</small><strong>{{ $inspectionsCount ?? 0 }}</strong><span>{{ $paidInspectionsCount ?? 0 }} paid, {{ $completedInspectionsCount ?? 0 }} completed</span></div>
                <a href="{{ route('inspections.index') }}">View diagnoses <i class="mdi mdi-arrow-right"></i></a>
            </article>
            <article class="ops-kpi">
                <span class="ops-icon ops-icon-purple"><i class="mdi mdi-briefcase-outline"></i></span>
                <div><small>Projects</small><strong>{{ $projectsCount ?? 0 }}</strong><span>Active remediation work</span></div>
                <a href="{{ route('maintenance-visit-logs.index') }}">View projects <i class="mdi mdi-arrow-right"></i></a>
            </article>
            <article class="ops-kpi">
                <span class="ops-icon ops-icon-orange"><i class="mdi mdi-file-document-outline"></i></span>
                <div><small>Invoices</small><strong>{{ $invoicesCount ?? 0 }}</strong><span>{{ $pendingInvoicesCount ?? 0 }} unpaid invoices</span></div>
                <a href="{{ route('invoices.index') }}">View invoices <i class="mdi mdi-arrow-right"></i></a>
            </article>
            <article class="ops-kpi">
                <span class="ops-icon ops-icon-cyan"><i class="mdi mdi-account-hard-hat-outline"></i></span>
                <div><small>Trade Partners</small><strong>{{ $approvedTradeApplicationsCount ?? 0 }}</strong><span>{{ $openTradeApplicationsCount ?? 0 }} open applications</span></div>
                @role('Super Admin|Administrator')
                    <a href="{{ route('admin.trade-applications.index') }}">Review trades <i class="mdi mdi-arrow-right"></i></a>
                @else
                    <span></span>
                @endrole
            </article>
        </section>

        <div class="ops-grid ops-grid-stacked">
            <div class="ops-column">
                <section class="ops-panel">
                    <div class="ops-panel-head">
                        <div><h2>Property Registry</h2><p>Recently added properties.</p></div>
                        <a href="{{ route('properties.index') }}" class="ops-small-button">View All <i class="mdi mdi-arrow-right"></i></a>
                    </div>
                    <div class="ops-table-wrap">
                        <table class="ops-table">
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
                                @forelse($recentProperties ?? [] as $property)
                                    @php
                                        $propertyTypeLabel = match($property->type) {
                                            'mixed_use' => 'Mixed Use',
                                            'residential' => 'Residential',
                                            'commercial' => 'Commercial',
                                            default => 'Not Set',
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="ops-property-cell">
                                                <span><i class="mdi mdi-domain"></i></span>
                                                <div><strong>{{ $property->property_name }}</strong><small>ID: {{ $property->property_code }}</small></div>
                                            </div>
                                        </td>
                                        <td><span class="ops-pill ops-pill-blue">{{ $propertyTypeLabel }}</span></td>
                                        <td><i class="mdi mdi-map-marker-outline text-muted"></i> {{ $property->city ?? 'N/A' }}, {{ $property->country ?? 'N/A' }}</td>
                                        <td><span class="ops-pill ops-pill-green">{{ ucfirst(str_replace('_', ' ', (string) ($property->status ?? 'registered'))) }}</span></td>
                                        <td class="text-end">
                                            <a href="{{ route('properties.show', $property->id) }}" class="ops-icon-button" title="View property">
                                                <i class="mdi mdi-eye-outline"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5"><div class="ops-empty ops-empty-row">No properties yet.</div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="ops-panel">
                    <div class="ops-panel-head">
                        <div><h2>Operations Queue</h2><p>Items that need attention.</p></div>
                    </div>
                    <div class="ops-action-list">
                        @role('Super Admin|Administrator')
                            <a href="{{ route('properties.index', ['status' => 'registered']) }}"><span class="ops-icon ops-icon-blue"><i class="mdi mdi-phone-in-talk-outline"></i></span><div><strong>Awaiting Contact</strong><small>Registered properties to call</small></div><b>{{ $awaitingContactCount ?? $newRegistrationsCount ?? 0 }}</b></a>
                            <a href="{{ route('properties.index', ['status' => 'awaiting_inspection']) }}"><span class="ops-icon ops-icon-purple"><i class="mdi mdi-cube-scan"></i></span><div><strong>Property Facts Pending</strong><small>Floor plan and twin capture needed</small></div><b>{{ $propertyFactsPendingCount ?? 0 }}</b></a>
                            <a href="{{ route('invoices.create') }}"><span class="ops-icon ops-icon-orange"><i class="mdi mdi-file-plus-outline"></i></span><div><strong>Invoice Needed</strong><small>Property facts or diagnosis bill to prepare</small></div><b>{{ $invoiceNeededCount ?? 0 }}</b></a>
                            <a href="{{ route('admin.users.index') }}"><span class="ops-icon ops-icon-cyan"><i class="mdi mdi-account-multiple-outline"></i></span><div><strong>Total Users</strong><small>Registered platform users</small></div><b>{{ $totalUsersCount ?? 0 }}</b></a>
                        @endrole
                        @if($user->hasRole(['Super Admin', 'Administrator', 'Project Manager', 'Inspector']) || $user->can('view-inspections'))
                            <a href="{{ route('inspections.index') }}"><span class="ops-icon ops-icon-green"><i class="mdi mdi-clipboard-pulse-outline"></i></span><div><strong>Diagnosis In Progress</strong><small>Active diagnosis and PHAR work</small></div><b>{{ $diagnosisInProgressCount ?? ($upcomingInspections ?? collect())->count() }}</b></a>
                        @endif
                        @can('view-invoices')
                            <a href="{{ route('invoices.index', ['status' => 'pending']) }}"><span class="ops-icon ops-icon-orange"><i class="mdi mdi-file-alert-outline"></i></span><div><strong>Unpaid Invoices</strong><small>Open billing queue</small></div><b>{{ $pendingInvoicesCount ?? 0 }}</b></a>
                        @endcan
                    </div>
                </section>

                <section class="ops-panel">
                    <div class="ops-panel-head">
                        <div><h2>Upcoming Diagnoses</h2><p>Next scheduled property diagnoses.</p></div>
                    </div>
                    @if(($upcomingInspections ?? collect())->isNotEmpty())
                        <div class="ops-mini-list">
                            @foreach($upcomingInspections as $inspection)
                                <a href="{{ route('inspections.show', $inspection->id) }}">
                                    <span class="ops-icon ops-icon-green"><i class="mdi mdi-calendar-clock-outline"></i></span>
                                    <div><strong>{{ $inspection->property?->property_name ?? $inspection->property_name ?? 'Diagnosis' }}</strong><small>{{ optional($inspection->scheduled_date)->format('M d, Y g:i A') }}</small></div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="ops-empty"><i class="mdi mdi-calendar-check-outline"></i><strong>No upcoming diagnoses</strong><span>The diagnosis queue is clear.</span></div>
                    @endif
                </section>
            </div>

            <div class="ops-column">
                <section class="ops-panel">
                    <div class="ops-panel-head">
                        <div><h2>Quick Actions</h2><p>Common admin shortcuts.</p></div>
                    </div>
                    <div class="ops-action-list">
                        @can('create properties')
                            <a href="{{ route('properties.create') }}"><span class="ops-icon ops-icon-blue"><i class="mdi mdi-home-plus-outline"></i></span><div><strong>Add New Property</strong><small>Register a new property</small></div><i class="mdi mdi-chevron-right"></i></a>
                        @endcan
                        @can('create inspections')
                            <a href="{{ route('properties.index') }}"><span class="ops-icon ops-icon-green"><i class="mdi mdi-calendar-check-outline"></i></span><div><strong>Start Diagnosis</strong><small>Open the property queue</small></div><i class="mdi mdi-chevron-right"></i></a>
                        @endcan
                        @can('view-invoices')
                            <a href="{{ route('invoices.index') }}"><span class="ops-icon ops-icon-orange"><i class="mdi mdi-file-document-outline"></i></span><div><strong>View Invoices</strong><small>Billing and payments</small></div><i class="mdi mdi-chevron-right"></i></a>
                        @endcan
                        @role('Super Admin|Administrator')
                            <a href="{{ route('admin.trade-applications.index') }}"><span class="ops-icon ops-icon-purple"><i class="mdi mdi-account-hard-hat-outline"></i></span><div><strong>Trade Applications</strong><small>Review partner onboarding</small></div><i class="mdi mdi-chevron-right"></i></a>
                        @endrole
                    </div>
                </section>

                <section class="ops-panel">
                    <div class="ops-panel-head">
                        <div><h2>Recent Activity</h2><p>Latest operational updates.</p></div>
                        <a href="{{ route('notifications.index') }}" class="ops-small-button">View All <i class="mdi mdi-arrow-right"></i></a>
                    </div>
                    <div class="ops-activity-list">
                        @forelse($recentActivities ?? [] as $activity)
                            <div class="ops-activity">
                                <span class="ops-icon ops-icon-{{ $activity->status_color ?? 'blue' }}"><i class="mdi mdi-bell-outline"></i></span>
                                <div><strong>{{ $activity->description }}</strong><small>{{ $activity->property->property_name ?? $activity->property->name ?? 'N/A' }}</small></div>
                                <time>{{ optional($activity->created_at)->diffForHumans() }}</time>
                            </div>
                        @empty
                            <div class="ops-empty"><i class="mdi mdi-bell-outline"></i><strong>No recent activity</strong><span>New updates will appear here.</span></div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    @endif
</div>

@include('shared.dashboard-design-system')
@endsection
