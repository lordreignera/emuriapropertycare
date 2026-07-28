@extends('client.layout')

@section('title', 'Pay Invoice')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-body py-3">
                <p class="text-center text-muted small mb-2">
                    Invoice total: <strong>${{ number_format($total, 2) }}</strong>
                    &bull; Balance: <strong>${{ number_format($balance, 2) }}</strong>
                </p>
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <a href="{{ route('client.invoices.payment', ['invoice' => $invoice->id, 'plan' => '30']) }}"
                       class="btn {{ $plan === '30' ? 'btn-primary' : 'btn-outline-primary' }} px-4">
                        <i class="mdi mdi-percent me-1"></i>
                        Pay 30%<br>
                        <small>${{ number_format(min($balance, $total * 0.30), 2) }}</small>
                    </a>
                    <a href="{{ route('client.invoices.payment', ['invoice' => $invoice->id, 'plan' => '50']) }}"
                       class="btn {{ $plan === '50' ? 'btn-warning text-dark' : 'btn-outline-warning' }} px-4">
                        <i class="mdi mdi-percent-outline me-1"></i>
                        Pay 50%<br>
                        <small>${{ number_format(min($balance, $total * 0.50), 2) }}</small>
                    </a>
                    <a href="{{ route('client.invoices.payment', ['invoice' => $invoice->id, 'plan' => 'full']) }}"
                       class="btn {{ $plan === 'full' ? 'btn-success' : 'btn-outline-success' }} px-4">
                        <i class="mdi mdi-cash-check me-1"></i>
                        Pay Balance<br>
                        <small>${{ number_format($balance, 2) }}</small>
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header {{ $plan === 'full' ? 'bg-success' : ($plan === '50' ? 'bg-warning text-dark' : 'bg-primary') }} text-white">
                <h5 class="mb-0">
                    <i class="mdi mdi-lock-outline me-2"></i>
                    Pay Invoice {{ $invoice->invoice_number }}
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Property:</strong> {{ $invoice->project?->property?->property_name ?? 'N/A' }}<br>
                    <strong>Amount due now:</strong> <span class="fs-5">${{ number_format($chargeAmount, 2) }}</span><br>
                    <small class="text-muted">Partial payments update the invoice balance immediately after Stripe confirms the payment.</small>
                </div>

                <form id="invoice-payment-form">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Card Details</label>
                        <div id="card-element" class="form-control p-3" style="height:auto;min-height:45px;"></div>
                        <div id="card-errors" class="text-danger small mt-2"></div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('client.invoices.show', $invoice) }}" class="btn btn-secondary">Back</a>
                        <button type="submit" id="submit-button" class="btn {{ $plan === 'full' ? 'btn-success' : ($plan === '50' ? 'btn-warning text-dark' : 'btn-primary') }}">
                            <span id="button-text">
                                <i class="mdi mdi-lock me-1"></i>
                                Pay ${{ number_format($chargeAmount, 2) }}
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

    const form = document.getElementById('invoice-payment-form');
    const submitButton = document.getElementById('submit-button');
    const buttonText = document.getElementById('button-text');
    const spinner = document.getElementById('spinner');

    form.addEventListener('submit', async function(event) {
        event.preventDefault();

        submitButton.disabled = true;
        buttonText.classList.add('d-none');
        spinner.classList.remove('d-none');

        const { error: validationError } = await stripe.createPaymentMethod({
            type: 'card',
            card: cardElement
        });

        if (validationError) {
            document.getElementById('card-errors').textContent = 'Card validation failed: ' + validationError.message;
            submitButton.disabled = false;
            buttonText.classList.remove('d-none');
            spinner.classList.add('d-none');
            return;
        }

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

        const response = await fetch('{{ route('client.invoices.process-payment', $invoice) }}', {
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
