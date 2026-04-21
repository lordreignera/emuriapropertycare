@extends('client.layout')

@section('title', 'Pay for Visit {{ $installmentNumber }}')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="mdi mdi-lock me-2"></i>
                    Visit {{ $installmentNumber }} of {{ $totalInstallments }} — Payment
                </h5>
            </div>
            <div class="card-body">

                {{-- Visit Progress Bar --}}
                @php
                    $progressPct = round((($installmentNumber - 1) / $totalInstallments) * 100);
                @endphp
                <div class="mb-4">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>{{ $installmentNumber - 1 }} of {{ $totalInstallments }} visits paid</span>
                        <span>${{ number_format($amountPaidSoFar, 2) }} / ${{ number_format($arpTotal, 2) }}</span>
                    </div>
                    <div class="progress" style="height:10px;">
                        <div class="progress-bar bg-primary" style="width:{{ $progressPct }}%;"></div>
                    </div>
                </div>

                <div class="alert alert-info">
                    <strong>Property:</strong> {{ $inspection->property?->property_name }}<br>
                    <strong>Total Project Cost:</strong> ${{ number_format($arpTotal, 2) }}<br>
                    <strong>This visit (#{{ $installmentNumber }}) cost:</strong>
                    <span class="fs-5 text-primary">${{ number_format($installAmount, 2) }}</span><br>
                    <small class="text-muted">
                        Remaining after this payment:
                        ${{ number_format(max(0, $arpTotal - $amountPaidSoFar - $installAmount), 2) }}
                        ({{ $totalInstallments - $installmentNumber }} visit(s) left)
                    </small>
                </div>

                @if(app()->environment('local', 'development'))
                <div class="alert alert-warning py-2 px-3" style="font-size:.85rem;">
                    <strong>Test Mode:</strong> Use card <code>4242 4242 4242 4242</code> &bull; Any future expiry &bull; Any 3-digit CVC
                </div>
                @endif

                <form id="installment-payment-form">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Card Details</label>
                        <div id="card-element" class="form-control p-3" style="height:auto;min-height:45px;"></div>
                        <div id="card-errors" class="text-danger small mt-2"></div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('client.inspections.report', $inspection->id) }}" class="btn btn-secondary">Back</a>
                        <button type="submit" id="submit-button" class="btn btn-primary">
                            <span id="button-text">
                                <i class="mdi mdi-lock me-1"></i>
                                Pay ${{ number_format($installAmount, 2) }} (Visit {{ $installmentNumber }})
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

    const form = document.getElementById('installment-payment-form');
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

        const response = await fetch('{{ route('client.inspections.process-installment', $inspection->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ payment_intent_id: paymentIntent.id })
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
