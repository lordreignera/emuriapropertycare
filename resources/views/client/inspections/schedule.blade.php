@extends('client.layout')

@section('title', 'Schedule Inspection')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto">
        <!-- Header Card with Gradient -->
        <div class="card shadow-lg border-0 mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body text-white p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle bg-white bg-opacity-25 p-3 me-3">
                        <i class="mdi mdi-calendar-check" style="font-size: 2rem;"></i>
                    </div>
                    <div>
                        <h3 class="mb-1 fw-bold">Schedule Property Inspection</h3>
                        <p class="mb-0 opacity-75">Book your comprehensive property assessment</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Main Form Section -->
            <div class="col-lg-7">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <!-- Property Info Banner -->
                        <div class="bg-light rounded-3 p-3 mb-4 border border-primary border-opacity-25">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1 fw-bold text-primary">{{ $property->property_name }}</h5>
                                    <div class="text-muted small">
                                        <i class="mdi mdi-pound me-1"></i>{{ $property->property_code }}
                                        <span class="mx-2">|</span>
                                        <i class="mdi mdi-map-marker me-1"></i>{{ $property->city }}, {{ $property->country }}
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="badge bg-success fs-5 px-3 py-2">
                                        ${{ number_format($inspectionFee, 2) }}
                                    </div>
                                    <div class="text-muted small mt-1">Inspection Fee</div>
                                </div>
                            </div>
                        </div>

                        <!-- Scheduling Form -->
                        <form id="payment-form">
                            @csrf
                            
                            <h6 class="fw-bold text-dark mb-3">
                                <i class="mdi mdi-calendar-clock text-primary me-2"></i>Select Date & Time
                            </h6>
                            
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Preferred Date <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="mdi mdi-calendar"></i>
                                        </span>
                                        <input type="date" name="preferred_date" id="preferred_date" class="form-control border-start-0" min="{{ now()->format('Y-m-d') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Preferred Time</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="mdi mdi-clock-outline"></i>
                                        </span>
                                        <input type="time" name="preferred_time" id="preferred_time" class="form-control border-start-0" value="09:00">
                                    </div>
                                </div>
                            </div>

                            <h6 class="fw-bold text-dark mb-3">
                                <i class="mdi mdi-note-text text-primary me-2"></i>Additional Information
                            </h6>
                            
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Special Notes or Instructions</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 align-items-start pt-2">
                                        <i class="mdi mdi-text"></i>
                                    </span>
                                    <textarea name="special_notes" id="special_notes" rows="4" class="form-control border-start-0" placeholder="Any specific areas of concern or special access instructions?"></textarea>
                                </div>
                                <small class="text-muted">Let us know about any areas that need special attention</small>
                            </div>

                            <!-- Payment Section -->
                            <h6 class="fw-bold text-dark mb-3">
                                <i class="mdi mdi-credit-card text-primary me-2"></i>Payment Information
                            </h6>

                            <!-- Test Card Info Alert -->
                            <div class="alert alert-info border-0 mb-3">
                                <div class="d-flex align-items-start">
                                    <i class="mdi mdi-information text-info me-2" style="font-size: 1.5rem;"></i>
                                    <div>
                                        <strong class="d-block mb-2">Test Mode - Use Test Cards</strong>
                                        <div class="small">
                                            <strong>Valid Test Card:</strong> <code class="bg-white px-2 py-1">4242 4242 4242 4242</code><br>
                                            <strong>Expiry:</strong> Any future date (e.g., 12/28)<br>
                                            <strong>CVC:</strong> Any 3 digits (e.g., 123)
                                        </div>
                                        <div class="mt-2 small">
                                            <strong>Other test cards:</strong><br>
                                            • Visa: <code class="bg-white px-1">4242 4242 4242 4242</code><br>
                                            • Mastercard: <code class="bg-white px-1">5555 5555 5555 4444</code><br>
                                            • Amex: <code class="bg-white px-1">3782 822463 10005</code>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Element Container -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Card Details <span class="text-danger">*</span></label>
                                <div id="card-element" class="form-control p-3" style="height: auto; min-height: 45px;"></div>
                                <div id="card-errors" class="text-danger small mt-2"></div>
                            </div>

                            <!-- Terms Agreement -->
                            <div class="border rounded-3 p-3 mb-4 bg-light">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="agree_terms" required>
                                    <label class="form-check-label fw-semibold" for="agree_terms">
                                        I agree to pay the <span class="text-success">${{ number_format($inspectionFee, 2) }}</span> inspection fee via secure payment.
                                    </label>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="{{ route('client.properties.index') }}" class="btn btn-light btn-lg px-4">
                                    <i class="mdi mdi-arrow-left me-1"></i> Cancel
                                </a>
                                <button type="submit" id="submit-button" class="btn btn-success btn-lg px-4 shadow">
                                    <span id="button-text">
                                        <i class="mdi mdi-credit-card-outline me-2"></i>Pay & Schedule
                                    </span>
                                    <span id="spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar Info Section -->
            <div class="col-lg-5">
                <!-- What's Included Card -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-dark mb-3">
                            <i class="mdi mdi-check-circle text-success me-2"></i>What's Included
                        </h6>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2 d-flex align-items-start">
                                <i class="mdi mdi-check-circle-outline text-success me-2 mt-1"></i>
                                <span><strong>Comprehensive Assessment</strong><br><small class="text-muted">Full property evaluation by certified professionals</small></span>
                            </li>
                            <li class="mb-2 d-flex align-items-start">
                                <i class="mdi mdi-check-circle-outline text-success me-2 mt-1"></i>
                                <span><strong>Photo Documentation</strong><br><small class="text-muted">Detailed images of all property areas</small></span>
                            </li>
                            <li class="mb-2 d-flex align-items-start">
                                <i class="mdi mdi-check-circle-outline text-success me-2 mt-1"></i>
                                <span><strong>Detailed Report</strong><br><small class="text-muted">Professional recommendations and findings</small></span>
                            </li>
                            <li class="d-flex align-items-start">
                                <i class="mdi mdi-check-circle-outline text-success me-2 mt-1"></i>
                                <span><strong>Follow-up Support</strong><br><small class="text-muted">Questions answered after inspection</small></span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Process Steps Card -->
                <div class="card shadow-sm border-0 bg-primary bg-opacity-10">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-dark mb-3">
                            <i class="mdi mdi-timeline-clock text-primary me-2"></i>Inspection Process
                        </h6>
                        <div class="d-flex mb-3">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; flex-shrink: 0;">
                                <strong>1</strong>
                            </div>
                            <div>
                                <strong class="d-block">Schedule & Pay</strong>
                                <small class="text-muted">Choose your preferred date and complete payment</small>
                            </div>
                        </div>
                        <div class="d-flex mb-3">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; flex-shrink: 0;">
                                <strong>2</strong>
                            </div>
                            <div>
                                <strong class="d-block">Inspector Assigned</strong>
                                <small class="text-muted">We'll assign a qualified inspector to your property</small>
                            </div>
                        </div>
                        <div class="d-flex mb-3">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; flex-shrink: 0;">
                                <strong>3</strong>
                            </div>
                            <div>
                                <strong class="d-block">On-Site Inspection</strong>
                                <small class="text-muted">Thorough assessment of your property</small>
                            </div>
                        </div>
                        <div class="d-flex">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; flex-shrink: 0;">
                                <strong>4</strong>
                            </div>
                            <div>
                                <strong class="d-block">Receive Report</strong>
                                <small class="text-muted">Get your detailed inspection report within 48 hours</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Notice -->
                <div class="alert alert-light border-0 mt-4 shadow-sm">
                    <div class="d-flex align-items-center">
                        <i class="mdi mdi-shield-check text-success me-2" style="font-size: 1.5rem;"></i>
                        <small class="text-muted mb-0">
                            <strong class="d-block text-dark">Secure Payment</strong>
                            All payments are processed securely through Stripe
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
    // Initialize Stripe
    const stripe = Stripe('{{ $stripeKey }}');
    
    // Create card element
    const elements = stripe.elements();
    const cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#32325d',
                fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                '::placeholder': {
                    color: '#aab7c4'
                }
            },
            invalid: {
                color: '#dc3545',
                iconColor: '#dc3545'
            }
        }
    });
    
    cardElement.mount('#card-element');
    
    // Handle card errors
    cardElement.on('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });
    
    // Handle form submission
    const form = document.getElementById('payment-form');
    const submitButton = document.getElementById('submit-button');
    const buttonText = document.getElementById('button-text');
    const spinner = document.getElementById('spinner');
    
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        
        // Check if terms are agreed
        if (!document.getElementById('agree_terms').checked) {
            alert('Please agree to the payment terms.');
            return;
        }
        
        // Disable submit button and show spinner
        submitButton.disabled = true;
        buttonText.classList.add('d-none');
        spinner.classList.remove('d-none');
        
        try {
            // Confirm the payment
            const {error, paymentIntent} = await stripe.confirmCardPayment(
                '{{ $clientSecret }}',
                {
                    payment_method: {
                        card: cardElement,
                        billing_details: {
                            name: '{{ Auth::user()->name }}',
                            email: '{{ Auth::user()->email }}'
                        }
                    }
                }
            );
            
            if (error) {
                // Show error to customer
                document.getElementById('card-errors').textContent = error.message;
                submitButton.disabled = false;
                buttonText.classList.remove('d-none');
                spinner.classList.add('d-none');
            } else if (paymentIntent.status === 'succeeded') {
                // Payment succeeded, submit the form
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('preferred_date', document.getElementById('preferred_date').value);
                formData.append('preferred_time', document.getElementById('preferred_time').value);
                formData.append('special_notes', document.getElementById('special_notes').value);
                formData.append('payment_intent_id', paymentIntent.id);
                
                const response = await fetch('{{ route('client.inspections.store-schedule', $property->id) }}', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Redirect to properties page with success message
                    window.location.href = data.redirect + '?success=1';
                } else {
                    alert(data.message || 'An error occurred. Please try again.');
                    submitButton.disabled = false;
                    buttonText.classList.remove('d-none');
                    spinner.classList.add('d-none');
                }
            }
        } catch (err) {
            console.error('Payment error:', err);
            alert('An unexpected error occurred. Please try again.');
            submitButton.disabled = false;
            buttonText.classList.remove('d-none');
            spinner.classList.add('d-none');
        }
    });
</script>
@endpush
