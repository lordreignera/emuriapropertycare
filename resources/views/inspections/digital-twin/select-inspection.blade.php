@extends($layout)

@section('title', 'Select Diagnosis for Property Twin')
@section('header', 'Property Digital Twin')

@section('content')
<style>
    .twin-select-page {
        color: #102033;
    }

    .twin-select-panel,
    .twin-select-card {
        background: #fff;
        border: 1px solid #dbe4f0;
        border-radius: 8px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
    }

    .twin-select-panel {
        padding: 22px;
    }

    .twin-select-title {
        margin: 0;
        color: #06143a;
        font-size: 22px;
        font-weight: 700;
    }

    .twin-select-copy {
        margin: 8px 0 0;
        max-width: 780px;
        color: #52627a;
        line-height: 1.55;
    }

    .twin-select-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 14px;
        margin-top: 18px;
    }

    .twin-select-card {
        padding: 16px;
        box-shadow: none;
    }

    .twin-select-card h3 {
        margin: 0 0 8px;
        color: #0f172a;
        font-size: 16px;
        font-weight: 700;
    }

    .twin-select-meta {
        display: grid;
        gap: 6px;
        margin-bottom: 14px;
        color: #64748b;
        font-size: 13px;
    }
</style>

<div class="twin-select-page">
    <section class="twin-select-panel">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h2 class="twin-select-title">{{ $property->property_name ?: 'Property' }}</h2>
                @if($inspections->isEmpty())
                    <p class="twin-select-copy">
                        This property does not have a diagnosis inspection yet. Start or schedule a diagnosis first, then attach the property twin source to that inspection.
                    </p>
                @else
                    <p class="twin-select-copy">
                        This property has more than one diagnosis inspection. Choose the exact diagnosis that should own the property twin source and spatial findings.
                    </p>
                @endif
            </div>

            <a href="{{ $backUrl }}" class="btn btn-outline-secondary btn-sm">
                <i class="mdi mdi-arrow-left me-1"></i> Back
            </a>
        </div>

        @if($inspections->isNotEmpty())
            <div class="twin-select-grid">
                @foreach($inspections as $inspection)
                    @php
                        $scheduledDate = $inspection->scheduled_date
                            ? \Illuminate\Support\Carbon::parse($inspection->scheduled_date)->format('M j, Y')
                            : 'Not scheduled';
                    @endphp
                    <article class="twin-select-card">
                        <h3>Diagnosis #{{ $inspection->id }}</h3>
                        <div class="twin-select-meta">
                            <span>Status: {{ ucfirst(str_replace('_', ' ', $inspection->status)) }}</span>
                            <span>Scheduled: {{ $scheduledDate }}</span>
                            <span>Inspector: {{ $inspection->inspector?->name ?: 'Not assigned' }}</span>
                        </div>
                        <a href="{{ route('properties.digital-twin', [$property, 'inspection_id' => $inspection->id]) }}" class="btn btn-primary btn-sm">
                            Use this diagnosis
                        </a>
                    </article>
                @endforeach
            </div>
        @elseif($canStartInspection)
            <div class="mt-4">
                <a href="{{ $startInspectionUrl }}" class="btn btn-primary">
                    <i class="mdi mdi-clipboard-plus-outline me-1"></i> Start diagnosis
                </a>
            </div>
        @endif
    </section>
</div>
@endsection
