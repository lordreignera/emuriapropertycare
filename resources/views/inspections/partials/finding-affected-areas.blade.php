@php
    $affectedAreas = collect($f->affectedAreas ?? [])->filter(function ($area) {
        return $area->system || $area->subsystem || $area->component || $area->location || $area->impact_description;
    });
@endphp

@if($affectedAreas->isNotEmpty())
    @once
        <style>
            .finding-affected-list {
                display: grid;
                gap: 8px;
            }

            .finding-affected-item {
                border: 1px solid #f1d9a6;
                border-left: 4px solid #f59f00;
                border-radius: 8px;
                background: #fffaf0;
                padding: 10px 12px;
            }
        </style>
    @endonce

    <div class="finding-affected-areas mt-3">
        <div class="fw-bold small text-uppercase mb-2">Cascading / affected areas</div>
        <div class="finding-affected-list">
            @foreach($affectedAreas as $area)
                <div class="finding-affected-item">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                        <strong>
                            {{ $area->system?->name ?? 'Affected system' }}
                            @if($area->subsystem?->name)
                                / {{ $area->subsystem->name }}
                            @endif
                            @if($area->component?->name)
                                / {{ $area->component->name }}
                            @endif
                        </strong>
                        <span class="badge bg-light text-dark border">{{ ucwords($area->severity ?? 'moderate') }}</span>
                    </div>
                    @if($area->location)
                        <div class="text-muted small mt-1">{{ $area->location }}</div>
                    @endif
                    @if($area->impact_description)
                        <div class="small mt-1">{{ $area->impact_description }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
