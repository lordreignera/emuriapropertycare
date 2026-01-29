@extends('admin.layout')

@section('content')
<div class="row">
    <div class="col-lg-10 mx-auto grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">CPI Domain #{{ $cpiDomain->domain_number }}: {{ $cpiDomain->domain_name }}</h4>
                    <div>
                        <a href="{{ route('admin.cpi-domains.edit', $cpiDomain) }}" class="btn btn-warning btn-sm">
                            <i class="mdi mdi-pencil"></i> Edit Domain
                        </a>
                        <a href="{{ route('admin.cpi-domains.index') }}" class="btn btn-light btn-sm">
                            <i class="mdi mdi-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <p><strong>Domain Code:</strong> <code>{{ $cpiDomain->domain_code }}</code></p>
                        <p><strong>Max Possible Points:</strong> <span class="badge badge-primary">{{ $cpiDomain->max_possible_points }} pts</span></p>
                        <p><strong>Calculation Method:</strong> <span class="badge badge-info">{{ ucfirst($cpiDomain->calculation_method) }}</span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Sort Order:</strong> {{ $cpiDomain->sort_order }}</p>
                        <p><strong>Status:</strong> 
                            @if($cpiDomain->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-secondary">Inactive</span>
                            @endif
                        </p>
                        <p><strong>Total Factors:</strong> {{ $cpiDomain->activeFactors->count() }}</p>
                    </div>
                </div>

                @if($cpiDomain->description)
                <div class="alert alert-info">
                    <strong>Description:</strong><br>
                    {{ $cpiDomain->description }}
                </div>
                @endif

                <hr>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Scoring Factors</h5>
                    <a href="{{ route('admin.cpi-domains.factors.create', $cpiDomain) }}" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-plus"></i> Add Factor
                    </a>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">#</th>
                                <th width="25%">Factor Code</th>
                                <th width="30%">Label</th>
                                <th width="10%">Field Type</th>
                                <th width="10%">Max Points</th>
                                <th width="10%">Lookup Table</th>
                                <th width="5%">Required</th>
                                <th width="5%">Status</th>
                                <th width="10%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cpiDomain->activeFactors as $factor)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><code>{{ $factor->factor_code }}</code></td>
                                    <td>
                                        {{ $factor->factor_label }}
                                        @if($factor->help_text)
                                            <br><small class="text-muted">{{ $factor->help_text }}</small>
                                        @endif
                                    </td>
                                    <td><span class="badge badge-secondary">{{ $factor->field_type }}</span></td>
                                    <td><strong>{{ $factor->max_points }}</strong></td>
                                    <td>
                                        @if($factor->lookup_table)
                                            <code class="text-primary">{{ $factor->lookup_table }}</code>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($factor->is_required)
                                            <span class="badge badge-danger">Yes</span>
                                        @else
                                            <span class="badge badge-light">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($factor->is_active)
                                            <i class="mdi mdi-check-circle text-success"></i>
                                        @else
                                            <i class="mdi mdi-close-circle text-muted"></i>
                                        @endif                                    </td>
                                    <td>
                                        <a href="{{ route('admin.cpi-domains.factors.edit', [$cpiDomain, $factor]) }}" class="btn btn-sm btn-warning">
                                            <i class="mdi mdi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.cpi-domains.factors.destroy', [$cpiDomain, $factor]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this factor?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-3">
                                        <p class="text-muted mb-0">No factors defined for this domain yet.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($cpiDomain->activeFactors->count() > 0)
                <div class="mt-3">
                    <p class="text-muted">
                        <strong>Total Points from Factors:</strong> 
                        {{ $cpiDomain->activeFactors->sum('max_points') }} / 
                        {{ $cpiDomain->max_possible_points }} max possible
                    </p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
