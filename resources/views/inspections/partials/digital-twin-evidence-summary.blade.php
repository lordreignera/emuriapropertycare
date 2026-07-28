@php
    $spatialModels = $inspection->activeSpatialModels ?? collect();
    $issueMarkers = ($inspection->issueMarkers ?? collect())->sortByDesc('id');
    $hasDigitalTwinEvidence = $spatialModels->isNotEmpty() || $issueMarkers->isNotEmpty() || ($inspection->activeMatterportModel ?? null);
@endphp

@if($hasDigitalTwinEvidence)
    <section class="card mb-4 border">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">
                <i class="mdi mdi-cube-scan me-2 text-primary"></i>Digital Twin Evidence
            </h5>
            <span class="badge bg-primary">{{ $spatialModels->count() }} source{{ $spatialModels->count() === 1 ? '' : 's' }}</span>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                Spatial evidence attached to this report can come from Matterport, RESOLV, phone cameras, 360 cameras, drones, LiDAR, thermal scans, BIM/CAD files, or manual uploads.
            </p>

            @if($spatialModels->isNotEmpty())
                <div class="table-responsive mb-3">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Source</th>
                                <th>Layer</th>
                                <th>Format</th>
                                <th>Accuracy</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($spatialModels as $model)
                                <tr>
                                    <td>
                                        <strong>{{ $model->display_name ?: $model->provider_label }}</strong>
                                        <div class="text-muted small">{{ $model->captureSession?->device_name ?: $model->provider_label }}</div>
                                    </td>
                                    <td>{{ $model->source_type_label }}</td>
                                    <td>{{ $model->runtime_format ?: $model->original_format ?: 'Not recorded' }}</td>
                                    <td>{{ $model->accuracy_class ?: 'Not stated' }}</td>
                                    <td>
                                        <span class="badge {{ $model->processing_status === 'ready' ? 'bg-success' : 'bg-secondary' }}">
                                            {{ ucfirst(str_replace('_', ' ', $model->processing_status)) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif($inspection->activeMatterportModel ?? null)
                <div class="alert alert-light border mb-3">
                    Legacy Matterport evidence is attached and available through the digital twin viewer.
                </div>
            @endif

            @if($issueMarkers->isNotEmpty())
                <div class="fw-bold small text-uppercase text-muted mb-2">Spatial issue markers</div>
                <div class="row g-2">
                    @foreach($issueMarkers->take(6) as $marker)
                        <div class="col-md-6">
                            <div class="border rounded p-2 h-100">
                                <div class="d-flex justify-content-between gap-2">
                                    <strong>{{ $marker->title }}</strong>
                                    <span class="badge bg-light text-dark border">{{ ucfirst($marker->severity) }}</span>
                                </div>
                                <div class="small text-muted mt-1">
                                    {{ ucfirst(str_replace('_', ' ', $marker->source_provider)) }}
                                    @if($marker->room_name)
                                        . {{ $marker->room_name }}
                                    @endif
                                    @if($marker->surface_label)
                                        . {{ $marker->surface_label }}
                                    @endif
                                </div>
                                @if($marker->pharFinding)
                                    <div class="small mt-1">Linked finding #{{ $marker->pharFinding->id }}</div>
                                @endif
                                @if($marker->confidence !== null)
                                    <div class="small text-muted mt-1">{{ $marker->confidence }}% confidence</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($issueMarkers->count() > 6)
                    <div class="small text-muted mt-2">
                        {{ $issueMarkers->count() - 6 }} additional marker{{ $issueMarkers->count() - 6 === 1 ? '' : 's' }} available in the digital twin viewer.
                    </div>
                @endif
            @endif

            <div class="mt-3 no-print">
                <a href="{{ route('inspections.digital-twin', $inspection->id) }}" class="btn btn-outline-primary btn-sm">
                    <i class="mdi mdi-open-in-new me-1"></i>Open Digital Twin
                </a>
            </div>
        </div>
    </section>
@endif
