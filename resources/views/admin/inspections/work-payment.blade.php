@extends('admin.layout')

@section('title', 'Work Payment')

@section('content')
<div class="content-wrapper">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="mdi mdi-credit-card me-2"></i>Pay to Start Work
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>Property:</strong> {{ $inspection->property?->property_name ?? 'N/A' }}
                        <br>
                        <strong>Inspection:</strong> #{{ $inspection->id }}
                        <br>
                        <strong>Amount to Pay:</strong> <span class="text-success fs-5">${{ number_format($workAmount, 2) }}</span>
                    </div>

                    <form id="work-payment-form">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Card Details</label>
                            <div id="card-element" class="form-control p-3" style="height: auto; min-height: 45px;"></div>
                            <div id="card-errors" class="text-danger small mt-2"></div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="agree_terms" required>
                            <label class="form-check-label" for="agree_terms">
                                I confirm payment of ${{ number_format($workAmount, 2) }} to start project work.
                            </label>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('inspections.index', ['status' => 'completed']) }}" class="btn btn-secondary">
                                <i class="mdi mdi-arrow-left me-1"></i>Back
                            </a>
                            <button type="submit" id="submit-button" class="btn btn-success">
                                <span id="button-text"><i class="mdi mdi-lock me-1"></i>Pay & Start Work</span>
                                <span id="spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            </button>
                        </div>
                    </form>
                </div>
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
        const displayError = document.getElementById('card-errors');
        displayError.textContent = event.error ? event.error.message : '';
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

        const {error, paymentIntent} = await stripe.confirmCardPayment('{{ $clientSecret }}', {
            payment_method: {
                card: cardElement,
            }
        });

        if (error) {
            document.getElementById('card-errors').textContent = error.message;
            submitButton.disabled = false;
            buttonText.classList.remove('d-none');
            spinner.classList.add('d-none');
            return;
        }

        const response = await fetch('{{ route('inspections.process-work-payment', $inspection->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                payment_intent_id: paymentIntent.id
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
