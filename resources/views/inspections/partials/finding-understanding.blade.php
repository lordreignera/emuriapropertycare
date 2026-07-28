{{--
    Client-facing finding explanation.

    This deliberately reads as a natural, plain-language story rather than a
    questionnaire. Driven by what the inspector actually recorded, the layout is
    structured so the client implicitly learns: what it is (from the system /
    subsystem heading rendered by the parent), what we found, why it matters /
    what's at risk, and what we recommend next — without ever being asked.

    Expects: $f (\App\Models\PHARFinding)
--}}
@php
    $cleanFindingText = function ($value): string {
        $text = trim((string) $value);
        if ($text === '') {
            return '';
        }

        $lower = strtolower($text);
        foreach ([
            'an error occurred while processing your inspection request',
            'please try again',
            'payment verification failed',
            'callback',
            'stripe',
        ] as $fragment) {
            if (str_contains($lower, $fragment)) {
                return '';
            }
        }

        return $text;
    };

    $definition  = $cleanFindingText($f->plain_language_definition ?? '');   // optional context ("what is it")
    $observed    = $cleanFindingText($f->observed_condition ?? '');          // what we found
    $whyMatters  = $cleanFindingText($f->why_it_matters ?? '');              // optional, legacy
    $meaning     = $cleanFindingText($f->plain_language_meaning ?? '');      // optional, legacy
    $risk        = $cleanFindingText($f->consequence_if_ignored ?? '');      // why it matters / what's at risk
    $recommend   = $cleanFindingText($f->remediation_strategy ?? '');        // our recommendation
    $hasAny = $definition || $observed || $whyMatters || $meaning || $risk || $recommend;
@endphp

<div class="etogo-understanding-block">
    @if($definition)
        <p class="text-muted small mb-2">{{ $definition }}</p>
    @endif

    @if($observed)
        <div class="mb-2">
            <div class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.04em;">What we found</div>
            <div>{{ $observed }}</div>
        </div>
    @endif

    @if($whyMatters)
        <div class="mb-2">
            <div class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.04em;">Why this is important</div>
            <div>{{ $whyMatters }}</div>
        </div>
    @endif

    @if($meaning)
        <div class="p-3 rounded mb-2" style="background:#eef6ff;border-left:4px solid #0d6efd;">
            <div class="fw-bold small text-uppercase mb-1" style="color:#0d6efd;letter-spacing:.04em;">
                <i class="mdi mdi-account-heart-outline me-1"></i>What this means for you
            </div>
            <div>{{ $meaning }}</div>
        </div>
    @endif

    @if($risk)
        <div class="p-2 px-3 rounded mb-2" style="background:#fff5f5;border-left:4px solid #dc3545;">
            <div class="fw-bold small text-uppercase mb-1" style="color:#dc3545;letter-spacing:.04em;">
                <i class="mdi mdi-alert-outline me-1"></i>Why this matters &mdash; what&rsquo;s at risk
            </div>
            <div>{{ $risk }}</div>
        </div>
    @endif

    @if($recommend)
        <div class="p-3 rounded mb-1" style="background:#f0fff4;border-left:4px solid #198754;">
            <div class="fw-bold small text-uppercase mb-1" style="color:#198754;letter-spacing:.04em;">
                <i class="mdi mdi-arrow-right-bold-circle-outline me-1"></i>What we recommend
            </div>
            <div>{{ $recommend }}</div>
        </div>
    @endif

    @unless($hasAny)
        <p class="text-muted small mb-0"><em>Details for this finding are being finalised.</em></p>
    @endunless
</div>
