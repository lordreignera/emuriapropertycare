@extends('client.layout')

@section('title', 'Pay to Start Work')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">

        {{-- Plan toggle --}}
        <div class="card mb-3">
            <div class="card-body py-3">
                <p class="text-center text-muted small mb-2">Total project cost: <strong>${{ number_format($arpTotal, 2) }}</strong> &bull; {{ $totalVisits }} visit(s) required</p>
                <div class="d-flex justify-content-center gap-2">
                    <a href="{{ route('client.inspections.work-payment', ['inspection' => $inspection->id, 'plan' => 'full']) }}"
                       class="btn {{ $plan === 'full' ? 'btn-success' : 'btn-outline-success' }} px-4">
                        <i class="mdi mdi-cash-check me-1"></i>
                        Pay in Full<br>
                        <small>${{ number_format($arpTotal, 2) }} once</small>
                    </a>
                    <a href="{{ route('client.inspections.work-payment', ['inspection' => $inspection->id, 'plan' => 'per_visit']) }}"
                       class="btn {{ $plan === 'per_visit' ? 'btn-primary' : 'btn-outline-primary' }} px-4">
                        <i class="mdi mdi-calendar-check me-1"></i>
                        Pay Per Visit<br>
                        <small>${{ number_format($perVisit, 2) }}/visit &times; {{ $totalVisits }}</small>
                    </a>
                    <a href="{{ route('client.inspections.work-payment', ['inspection' => $inspection->id, 'plan' => 'installment']) }}"
                       class="btn {{ $plan === 'installment' ? 'btn-warning text-dark' : 'btn-outline-warning' }} px-4">
                        <i class="mdi mdi-percent me-1"></i>
                        Pay 50% Deposit<br>
                        <small>${{ number_format($depositAmount, 2) }} now + later</small>
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header {{ $plan === 'full' ? 'bg-success' : ($plan === 'per_visit' ? 'bg-primary' : 'bg-warning text-dark') }} text-white">
                <h5 class="mb-0">
                    <i class="mdi mdi-lock me-2"></i>
                    {{ $plan === 'full' ? 'Pay in Full' : ($plan === 'per_visit' ? 'Per-Visit Payment Plan' : '50% Deposit Plan') }}
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Property:</strong> {{ $inspection->property?->property_name }}<br>
                    <strong>Total Project Cost:</strong> ${{ number_format($arpTotal, 2) }}<br>
                    <strong>Visits Required:</strong> {{ $totalVisits }}<br>
                    @if($plan === 'full')
                        <strong>Amount due now:</strong> <span class="fs-5 text-success">${{ number_format($chargeAmount, 2) }}</span>
                        <br><small class="text-muted">Full cost settled in one payment. Work starts immediately.</small>
                    @elseif($plan === 'per_visit')
                        <strong>Cost per visit (visit 1 of {{ $totalVisits }}):</strong>
                        <span class="fs-5 text-primary">${{ number_format($chargeAmount, 2) }}</span>
                        <br><small class="text-muted">Pay ${{ number_format($perVisit, 2) }} before each visit &times; {{ $totalVisits }} visits. Work starts after this first payment.</small>
                    @else
                        <strong>Deposit due now (50%):</strong>
                        <span class="fs-5 text-warning">${{ number_format($chargeAmount, 2) }}</span>
                        <br><small class="text-muted">Remaining 50% is due as the second installment. This option is available even when there is only one visit.</small>
                    @endif
                </div>

                @if(app()->environment('local', 'development'))
                <div class="alert alert-warning py-2 px-3" style="font-size:.85rem;">
                    <strong>Test Mode:</strong> Use card <code>4242 4242 4242 4242</code> &bull; Any future expiry &bull; Any 3-digit CVC
                </div>
                @endif

                <form id="work-payment-form">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Card Details</label>
                        <div id="card-element" class="form-control p-3" style="height:auto;min-height:45px;"></div>
                        <div id="card-errors" class="text-danger small mt-2"></div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('client.inspections.report', $inspection->id) }}" class="btn btn-secondary">Back</a>
                        <button type="submit" id="submit-button" class="btn {{ $plan === 'full' ? 'btn-success' : ($plan === 'per_visit' ? 'btn-primary' : 'btn-warning text-dark') }}">
                            <span id="button-text">
                                <i class="mdi mdi-lock me-1"></i>
                                {{ $plan === 'full'
                                    ? 'Pay $'.number_format($chargeAmount, 2).' & Start Work'
                                    : ($plan === 'per_visit'
                                        ? 'Pay $'.number_format($chargeAmount, 2).' (Visit 1 of '.$totalVisits.')'
                                        : 'Pay $'.number_format($chargeAmount, 2).' (50% Deposit)') }}
                            </span>
                            <span id="spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
    const stripe = Stripe('{{ $stripeKey }}');
    const elements = stripe.elements();
    const cardElement = elements.create('card');
    cardElement.mount('#card-element');

    cardElement.on('change', function(event) {
        document.getElementById('card-errors').textContent = event.error ? event.error.message : '';
    });

    const form = document.getElementById('work-payment-form');
    const submitButton = document.getElementById('submit-button');
    const buttonText = document.getElementById('button-text');
    const spinner = document.getElementById('spinner');

    form.addEventListener('submit', async function(event) {
        event.preventDefault();

        submitButton.disabled = true;
        buttonText.classList.add('d-none');
        spinner.classList.remove('d-none');

        const { error, paymentIntent } = await stripe.confirmCardPayment('{{ $clientSecret }}', {
            payment_method: { card: cardElement }
        });

        if (error) {
            document.getElementById('card-errors').textContent = error.message;
            submitButton.disabled = false;
            buttonText.classList.remove('d-none');
            spinner.classList.add('d-none');
            return;
        }

        const response = await fetch('{{ route('client.inspections.process-work-payment', $inspection->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                payment_intent_id: paymentIntent.id,
                plan: '{{ $plan }}'
            })
        });

        const data = await response.json();
        if (data.success) {
            window.location.href = data.redirect;
        } else {
            document.getElementById('card-errors').textContent = data.message || 'Payment verification failed.';
            submitButton.disabled = false;
            buttonText.classList.remove('d-none');
            spinner.classList.add('d-none');
        }
    });
</script>
@endpush
