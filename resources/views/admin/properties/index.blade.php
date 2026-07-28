@extends('admin.layout')

@section('title', 'Property Registry')

@section('content')
<div class="content-wrapper">
    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="card-title mb-0">Property Registry</h4>
                            <p class="text-muted small mb-0">Manage client properties, inspections, reports, and digital twin sources</p>
                        </div>
                    </div>

                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    <!-- Filter Tabs -->
                    <ul class="nav nav-pills mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'not_inspected' ? 'active' : '' }}" 
                               href="{{ route('properties.index', ['status' => 'not_inspected']) }}">
                                <i class="mdi mdi-home-alert"></i> Not Yet Assessed
                                <span class="badge bg-warning ms-1">
                                    {{ \App\Models\Property::whereDoesntHave('inspections', function($query) {
                                            $query->where('status', 'completed');
                                        })
                                        ->count() }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('status') == 'inspected_completed' ? 'active' : '' }}" 
                               href="{{ route('properties.index', ['status' => 'inspected_completed']) }}">
                                <i class="mdi mdi-check-decagram"></i> Assessed & Completed
                                <span class="badge bg-success ms-1">
                                    {{ \App\Models\Property::whereHas('inspections', function($query) {
                                            $query->where('status', 'completed');
                                        })
                                        ->count() }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ !request('status') ? 'active' : '' }}" 
                               href="{{ route('properties.index') }}">
                                <i class="mdi mdi-view-list"></i> Property Registry
                            </a>
                        </li>
                    </ul>

                    <!-- Search Form -->
                    <form method="GET" action="{{ route('properties.index') }}" class="mb-3">
                        <input type="hidden" name="status" value="{{ request('status') }}">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search by property name, code, city, or address..." 
                                   value="{{ request('search') }}">
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-magnify"></i> Search
                            </button>
                            @if(request('search'))
                            <a href="{{ route('properties.index', ['status' => request('status')]) }}" class="btn btn-secondary">
                                <i class="mdi mdi-close"></i> Clear
                            </a>
                            @endif
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table id="propertiesTable" class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Owner</th>
                                    <th>Location</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($properties as $property)
                                <tr>
                                    <td>
                                        <code>{{ $property->property_code }}</code><br>
                                        <strong>{{ $property->property_name }}</strong>
                                        @if($property->property_brand)
                                        <br><small class="text-muted">Brand: {{ $property->property_brand }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $property->owner_first_name }}</div>
                                        <small class="text-muted">{{ $property->owner_email }}</small>
                                    </td>
                                    <td>
                                        {{ $property->city }}, {{ $property->province }}<br>
                                        <small class="text-muted">{{ $property->country }}</small>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            {{ ucfirst(str_replace('_', ' ', $property->type)) }}
                                        </span>
                                        @if($property->has_tenants)
                                        <br><span class="badge badge-primary mt-1">
                                            <i class="mdi mdi-account-group"></i> Multi-Tenant
                                        </span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $latestInspection = $property->inspections->first();
                                        @endphp

                                        @if($latestInspection)
                                            @if($latestInspection->status === 'completed')
                                                <span class="badge badge-success">
                                                    <i class="mdi mdi-check-circle"></i> Assessed & Completed
                                                </span>
                                                <br><small class="text-muted">{{ optional($latestInspection->completed_date)->format('M d, Y') ?? 'Completion date unavailable' }}</small>
                                            @else
                                                <span class="badge badge-warning">
                                                    <i class="mdi mdi-clock-outline"></i> Not Yet Assessed
                                                </span>
                                                <br><small class="text-muted">Inspection status: {{ ucfirst(str_replace('_', ' ', $latestInspection->status)) }}</small>
                                            @endif

                                            <br>
                                            @if($latestInspection->inspection_fee_status === 'paid')
                                                <small class="text-success"><i class="mdi mdi-cash-check"></i> Diagnosis invoice paid</small>
                                            @else
                                                <small class="text-muted"><i class="mdi mdi-receipt-clock-outline"></i> Invoice pending / unpaid</small>
                                            @endif
                                        @else
                                            <span class="badge badge-warning">
                                                <i class="mdi mdi-alert-circle-outline"></i> Not Yet Assessed
                                            </span>
                                            <br><small class="text-muted"><i class="mdi mdi-phone-in-talk-outline"></i> Awaiting client call</small>
                                        @endif
                                    </td>
                                    <td>{{ $property->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            @php
                                                $completedInspection = $property->inspections->firstWhere('status', 'completed');
                                                $projectInvoice = null;
                                                if ($completedInspection?->project_id) {
                                                    $projectInvoice = \App\Models\Invoice::where('project_id', $completedInspection->project_id)
                                                        ->latest('id')
                                                        ->first();
                                                }
                                                $agreementFullySigned = $completedInspection
                                                    && !empty($completedInspection->client_signature)
                                                    && !empty($completedInspection->etogo_signed_at);
                                            @endphp

                                            @php
                                                $diagnosisPricing = $diagnosisPricingByPropertyId[$property->id] ?? ['invoice_dollars' => 0];
                                            @endphp

                                            @if(!$completedInspection)
                                                <a href="{{ route('properties.digital-twin', $property) }}"
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="Open Property Facts & Digital Twin">
                                                    <i class="mdi mdi-cube-scan me-1"></i>Twin
                                                </a>
                                                <button type="button"
                                                        class="btn btn-sm btn-warning text-dark"
                                                        data-action-url="{{ route('properties.diagnosis-invoice.store', $property) }}"
                                                        data-property-name="{{ $property->property_name }}"
                                                        data-diagnosis-amount="{{ number_format((float) ($diagnosisPricing['invoice_dollars'] ?? 0), 2, '.', '') }}"
                                                        onclick="openDiagnosisInvoiceModal(this)"
                                                        title="Prepare Property Facts & Diagnosis Invoice">
                                                    <i class="mdi mdi-receipt-text-plus me-1"></i>Invoice
                                                </button>
                                            @endif

                                            @if($completedInspection?->activeSpatialModels?->isNotEmpty() || $completedInspection?->activeMatterportModel)
                                                <a href="{{ route('inspections.digital-twin', $completedInspection->id) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Open Digital Twin">
                                                    <i class="mdi mdi-cube-scan me-1"></i>Twin
                                                </a>
                                            @endif

                                            @if($completedInspection)
                                                <a href="{{ route('inspections.preview-report', $completedInspection->id) }}"
                                                   class="btn btn-sm btn-success" title="Open Report">
                                                    <i class="mdi mdi-file-document-outline me-1"></i>Report
                                                </a>
                                                <a href="{{ route('inspections.preview-agreement', $completedInspection->id) }}"
                                                   class="btn btn-sm btn-primary"
                                                   title="{{ $agreementFullySigned ? 'View Signed Agreement' : 'View Agreement' }}"
                                                   target="_blank">
                                                    <i class="mdi mdi-file-sign me-1"></i>Agreement
                                                </a>
                                                @if($projectInvoice)
                                                    <a href="{{ route('invoices.show', $projectInvoice->id) }}"
                                                       class="btn btn-sm btn-warning text-dark" title="View Invoice">
                                                        <i class="mdi mdi-receipt me-1"></i>Invoice
                                                    </a>
                                                @endif
                                            @endif

                                            @if(!$completedInspection)
                                                <a href="{{ route('properties.show', $property->id) }}" 
                                                   class="btn btn-sm btn-info" title="View Details">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>
                                            @endif
                                            
                                            @php
                                                $openDiagnosis = $property->inspections()
                                                    ->whereIn('status', ['scheduled', 'in_progress'])
                                                    ->first();
                                                $hasInspectorAssigned = $openDiagnosis && $openDiagnosis->inspector_id;
                                                $startableInspection = $property->inspections->first(function ($inspection) {
                                                    return in_array($inspection->status, ['scheduled', 'in_progress'], true);
                                                });
                                                $canInspectorStart = $startableInspection
                                                    && auth()->user()->hasRole('Inspector')
                                                    && (
                                                        (int) ($startableInspection->inspector_id ?? 0) === (int) auth()->id()
                                                        || (int) ($property->inspector_id ?? 0) === (int) auth()->id()
                                                        || auth()->user()->can('create inspections')
                                                    );
                                                $canAdminStart = !$completedInspection
                                                    && !auth()->user()->hasRole('Inspector')
                                                    && (
                                                        auth()->user()->can('create inspections')
                                                        || auth()->user()->hasRole(['Super Admin', 'Super Administrator', 'Administrator', 'Project Manager'])
                                                    );
                                            @endphp

                                            @if($canInspectorStart || $canAdminStart)
                                                <a href="{{ route('inspections.create', ['property_id' => $property->id]) }}"
                                                   class="btn btn-sm btn-success fw-bold"
                                                   title="Start Diagnosis">
                                                    <i class="mdi mdi-clipboard-check me-1"></i> Start Diagnosis
                                                </a>
                                            @endif
                                            
                                            @if(!$completedInspection && !$hasInspectorAssigned)
                                                <button type="button" class="btn btn-sm btn-primary" 
                                                        onclick="assignStaff({{ $property->id }})" 
                                                        title="Assign Diagnosis Team">
                                                    <i class="mdi mdi-account-plus"></i>
                                                </button>
                                            @endif
                                            
                                            @if(!auth()->user()->hasRole('Inspector') && !$completedInspection)
                                                <form action="{{ route('properties.destroy', $property->id) }}" 
                                                      method="POST" 
                                                      onsubmit="return confirm('Are you sure you want to delete this property?');"
                                                      class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-dark" title="Delete">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="mdi mdi-home-outline" style="font-size: 3rem; color: #ddd;"></i>
                                        <p class="text-muted mt-2">No properties found</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if($properties->hasPages())
                    <div class="mt-3">
                        {{ $properties->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assign Project Manager & Inspector Modal -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background-color: #ffffff !important;">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="mdi mdi-account-multiple-plus me-2"></i>Assign Project Manager & Inspector
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="assignForm" method="POST">
                @csrf
                <div class="modal-body" style="background-color: #ffffff !important; color: #000000 !important;">
                    <div class="alert alert-info" style="background-color: #e7f3ff !important; border-color: #b3d9ff !important; color: #004085 !important;">
                        <i class="mdi mdi-information"></i> 
                        Assign the team after the client registers the property. Payment is handled later through the property facts and diagnosis invoice.
                    </div>

                    <!-- Display scheduled date from client -->
                    <div id="inspectionDetails" class="bg-light p-3 rounded mb-3" style="display: none;">
                        <p class="mb-2"><strong>Inspection Details:</strong></p>
                        <p class="mb-1"><i class="mdi mdi-calendar"></i> <span id="scheduledDate"></span></p>
                        <p class="mb-1"><i class="mdi mdi-cash"></i> <span id="feePaid"></span></p>
                        <p class="mb-0" id="notesSection" style="display: none;"><i class="mdi mdi-note-text"></i> <span id="inspectionNotes"></span></p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="project_manager_id" style="color: #000000 !important;">Project Manager <span class="text-danger">*</span></label>
                                <select name="project_manager_id" id="project_manager_id" class="form-control" required style="background-color: #ffffff !important; color: #000000 !important;">
                                    <option value="">-- Select Project Manager --</option>
                                    @foreach(\App\Models\User::role('Project Manager')->get() as $pm)
                                    <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted" style="color: #666666 !important;">PM supervises the inspection process</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="inspector_id" style="color: #000000 !important;">Inspector <span class="text-danger">*</span></label>
                                <select name="inspector_id" id="inspector_id" class="form-control" required style="background-color: #ffffff !important; color: #000000 !important;">
                                    <option value="">-- Choose Inspector --</option>
                                    @foreach(\App\Models\User::role('Inspector')->get() as $inspector)
                                    <option value="{{ $inspector->id }}">{{ $inspector->name }} ({{ $inspector->email }})</option>
                                    @endforeach
                                </select>
                                <small class="text-muted" style="color: #666666 !important;">Assign an inspector to conduct the assessment</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-check me-2"></i>Assign Staff
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Property Facts & Diagnosis Invoice Modal -->
<div class="modal fade" id="diagnosisInvoiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background-color: #ffffff !important;">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">
                    <i class="mdi mdi-receipt-text-plus me-2"></i>Prepare Invoice
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="diagnosisInvoiceForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-muted mb-3">
                        Create a client invoice after property facts/site scope are clear.
                        The diagnosis amount uses the existing computed pricing formula.
                    </p>
                    <div class="mb-3">
                        <label class="form-label">Property</label>
                        <input type="text" id="diagnosisInvoicePropertyName" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="property_facts_amount" class="form-label">Property Facts / Digital Twin Amount</label>
                        <input type="number" min="0" step="0.01" name="property_facts_amount" id="property_facts_amount" class="form-control" value="0.00">
                        <small class="text-muted">Use this for floor plan, capture session, digital twin/model preparation, or onsite property facts work.</small>
                    </div>
                    <div class="mb-3">
                        <label for="diagnosis_amount" class="form-label">Diagnosis Amount</label>
                        <input type="number" min="0" step="0.01" name="diagnosis_amount" id="diagnosis_amount" class="form-control" required>
                        <small class="text-muted">Defaults to the former inspection fee calculation.</small>
                    </div>
                    <div class="mb-0">
                        <label for="diagnosis_due_date" class="form-label">Due Date</label>
                        <input type="date" name="due_date" id="diagnosis_due_date" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark">
                        <i class="mdi mdi-send me-1"></i>Create Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openDiagnosisInvoiceModal(button) {
    const form = document.getElementById('diagnosisInvoiceForm');
    const propertyName = document.getElementById('diagnosisInvoicePropertyName');
    const diagnosisAmount = document.getElementById('diagnosis_amount');
    const dueDate = document.getElementById('diagnosis_due_date');

    form.action = button.getAttribute('data-action-url');
    propertyName.value = button.getAttribute('data-property-name') || 'Property';
    diagnosisAmount.value = button.getAttribute('data-diagnosis-amount') || '0.00';

    const defaultDue = new Date();
    defaultDue.setDate(defaultDue.getDate() + 14);
    dueDate.value = defaultDue.toISOString().slice(0, 10);

    const modal = new bootstrap.Modal(document.getElementById('diagnosisInvoiceModal'));
    modal.show();
}

function assignStaff(propertyId) {
    // Fetch property inspection details via AJAX
    fetch(`/api/properties/${propertyId}/inspection-details`)
        .then(response => response.json())
        .then(data => {
            if (data.inspection) {
                // Show inspection details
                document.getElementById('inspectionDetails').style.display = 'block';
                document.getElementById('scheduledDate').textContent = 'Scheduled: ' + data.inspection.scheduled_date;
                document.getElementById('feePaid').textContent = 'Diagnosis amount: $' + data.inspection.fee_amount;
                
                if (data.inspection.notes) {
                    document.getElementById('notesSection').style.display = 'block';
                    document.getElementById('inspectionNotes').textContent = 'Notes: ' + data.inspection.notes;
                } else {
                    document.getElementById('notesSection').style.display = 'none';
                }
            }
        })
        .catch(error => {
            console.error('Error fetching inspection details:', error);
        });
    
    const form = document.getElementById('assignForm');
    form.action = '/properties/' + propertyId + '/assign';
    const modal = new bootstrap.Modal(document.getElementById('assignModal'));
    modal.show();
}

$(document).ready(function() {
    @if($properties->count() > 0)
    $('#propertiesTable').DataTable({
        "pageLength": 15,
        "lengthMenu": [[15, 25, 50, -1], [15, 25, 50, "All"]],
        "order": [[5, "desc"]],
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ properties",
            "info": "Showing _START_ to _END_ of _TOTAL_ properties"
        },
        "columnDefs": [
            { "orderable": false, "targets": [6] }
        ]
    });
    @endif
});
</script>
@endpush
