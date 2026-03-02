@extends('client.layout')

@section('title', 'Pay to Start Work')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="mdi mdi-lock me-2"></i>Secure Payment - {{ ucfirst($cadence) }} Plan</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Property:</strong> {{ $inspection->property?->property_name }}<br>
                    <strong>Cadence:</strong> {{ ucfirst($cadence) }}<br>
                    <strong>Amount:</strong> <span class="fs-5 text-success">${{ number_format($amount, 2) }}</span>
                    @if($cadence === 'annual')
                        <br><small class="text-muted">(12 × ${{ number_format($monthlyBase, 2) }}/month)</small>
                    @endif
                </div>

                <form id="work-payment-form">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Card Details</label>
                        <div id="card-element" class="form-control p-3" style="height:auto;min-height:45px;"></div>
                        <div id="card-errors" class="text-danger small mt-2"></div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('client.inspections.report', $inspection->id) }}" class="btn btn-secondary">Back</a>
                        <button type="submit" id="submit-button" class="btn btn-success">
                            <span id="button-text">Pay & Start Work</span>
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
                cadence: '{{ $cadence }}'
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
