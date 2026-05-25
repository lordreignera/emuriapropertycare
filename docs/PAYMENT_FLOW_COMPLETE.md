# Complete Payment Flow Documentation

## Overview
The EMURIAREGENERATIVEPROPERTYCARE system handles three types of payments:
1. **Inspection Fee** - Charged when scheduling inspection
2. **Work Payment** - Charged when starting remedial work (multiple plan options)
3. **Installment/Per-Visit Payments** - Charged per visit or as installments

All payments use Stripe's PaymentIntents API with client confirmation, pre-flight validation, and server-side webhook verification.

---

## Phase 1: Property Setup (Creates Surcharge Basis)

### Files Involved
- [database/migrations/2026_05_22_000001_add_inspection_surcharge_fields_to_properties_table.php](../database/migrations/2026_05_22_000001_add_inspection_surcharge_fields_to_properties_table.php)
- [app/Models/Property.php](../app/Models/Property.php)
- [app/Http/Controllers/Client/PropertyController.php](../app/Http/Controllers/Client/PropertyController.php)
- [resources/views/client/properties/create.blade.php](../resources/views/client/properties/create.blade.php)

### Process
1. **Client registers property** with:
   - Property type (residential, commercial, mixed_use)
   - Number of residential units (required for residential/mixed-use)
   - Has high-pitched roof? (boolean, +$75 surcharge)
   - Has crawl spaces? (boolean, +$50 surcharge)

2. **Database Schema** (migration):
   ```sql
   ALTER TABLE properties ADD COLUMN has_high_pitched_roof BOOLEAN DEFAULT FALSE;
   ALTER TABLE properties ADD COLUMN has_crawl_space BOOLEAN DEFAULT FALSE;
   ```

3. **Property Model** casts these as booleans:
   ```php
   protected $casts = [
       'has_high_pitched_roof' => 'boolean',
       'has_crawl_space' => 'boolean',
   ];
   ```

4. **PropertyController** validates and normalizes:
   - `residential_units`: required|integer|min:1 (for residential/mixed-use types)
   - Booleans default to false if not provided

5. **Live Fee Calculator** (frontend):
   - Shows real-time fee estimate as user enters data
   - Formula: `($units × $299) + ($75 if roof) + ($50 if crawl) = Total`

---

## Phase 2: Inspection Fee Payment

### Files Involved
- [app/Http/Controllers/Client/InspectionController.php](../app/Http/Controllers/Client/InspectionController.php) - `scheduleCreate()`, `scheduleStore()`
- [resources/views/client/inspections/schedule.blade.php](../resources/views/client/inspections/schedule.blade.php)
- [app/Models/Inspection.php](../app/Models/Inspection.php)

### Step-by-Step Flow

#### Step 1: Client Views Inspection Schedule Page
```
GET /inspections/schedule/{property}
→ InspectionController::scheduleCreate()
  ├─ Load property with surcharge data
  ├─ Calculate fee: calculateInspectionFee(property)
  │  └─ Returns: {units, base_fee, roof_surcharge, crawl_surcharge, total_dollars, charge_cents}
  ├─ Create PaymentIntent with Stripe:
  │  ├─ Amount: $1.00 (test mode, will be live total later)
  │  ├─ Currency: USD
  │  ├─ Metadata: {payment_type: 'inspection_fee', property_id, user_id}
  │  └─ Customer: Auth::user()->stripe_id (Cashier customer)
  └─ Pass feeData & clientSecret to view
```

**Code in InspectionController::scheduleCreate():**
```php
$property = Property::with('user')->findOrFail($property);
$feeData = $this->calculateInspectionFee($property);

// Create Stripe PaymentIntent
$paymentIntent = auth()->user()->createPaymentIntent(
    (int)($feeData['charge_cents']),  // Always $1.00 test for now
    [
        'currency' => 'usd',
        'metadata' => [
            'payment_type' => 'inspection_fee',
            'property_id' => $property->id,
            'user_id' => auth()->id(),
        ],
    ]
);

return view('client.inspections.schedule', [
    'property' => $property,
    'feeData' => $feeData,
    'clientSecret' => $paymentIntent->client_secret,
]);
```

#### Step 2: Client Enters Card Details
```
View: resources/views/client/inspections/schedule.blade.php

1. Stripe.js initializes card element:
   - stripe = Stripe('pk_live_...')
   - elements = stripe.elements()
   - cardElement = elements.create('card')
   - cardElement.mount('#card-element')

2. Real-time validation feedback:
   - cardElement.on('change', event => {
     if (event.error) show error
     else clear error
   })

3. Fee breakdown displayed:
   - X unit(s) × $299 = $X
   - High-pitched roof surcharge: +$75 (if checked)
   - Crawl space surcharge: +$50 (if checked)
   - Total: $X
```

#### Step 3: Client Submits Payment Form
```
Form Submit Handler (schedule.blade.php):

1. PRE-FLIGHT VALIDATION (NEW):
   stripe.createPaymentMethod({
       type: 'card',
       card: cardElement
   })
   ├─ If validation fails → Show error immediately (NO CHARGE)
   └─ If validation passes → Continue to charging

2. CHARGE CARD:
   stripe.confirmCardPayment(clientSecret, {
       payment_method: { card: cardElement }
   })
   ├─ Return: {error, paymentIntent}
   ├─ If error → Show error, re-enable form (NO CHARGE)
   └─ If succeeded → POST payment_intent_id to server

3. SERVER-SIDE PROCESSING:
   POST /inspections/schedule
   → InspectionController::scheduleStore()
```

#### Step 4: Server Verifies & Creates Inspection
```
InspectionController::scheduleStore():

1. Verify PaymentIntent status:
   $paymentIntent = getPaymentIntent($request->payment_intent_id)
   if ($paymentIntent->status !== 'succeeded') abort(400)

2. Create Inspection record:
   Inspection::create([
       'property_id' => $property->id,
       'user_id' => auth()->id(),
       'scheduled_date' => $request->scheduled_date,
       'scheduled_time' => $request->scheduled_time,
       'status' => 'payment_confirmed',
       'payment_intent_id' => $paymentIntent->id,
   ])

3. Send notification:
   auth()->user()->notify(new InspectionFeePaidNotification($inspection))

4. Redirect to success page with inspection details
```

#### Step 5: Stripe Webhook Confirms Payment (Server-Side Verification)
```
ASYNC: Stripe sends webhook event (payment_intent.succeeded)

Route: POST /stripe/webhook
→ StripeWebhookController::handle()
  ├─ Verify signature with whsec_bF29freh5ecPu6tM1J8oFCg5ITNaEEQk
  ├─ Extract event data
  └─ Route by payment_type in metadata:
     ├─ inspection_fee → handlePaymentIntentSucceeded()
     ├─ work_start → handlePaymentIntentSucceeded()
     └─ per_visit → handlePaymentIntentSucceeded()

handlePaymentIntentSucceeded():
1. Find inspection by payment_intent_id
2. Update status: 'payment_confirmed' → 'inspection_scheduled'
3. Send InspectionFeePaidNotification to staff (Super Admin, Administrator, Project Manager)
   └─ Includes: property code, address, owner, inspector assignment link
4. Log transaction for audit trail
```

#### Step 6: Notification Sent to Client & Staff
```
InspectionFeePaidNotification:
- Via: database, mail
- Subject: "💳 Inspection Fee Paid — {propertyCode}"
- Recipients:
  ├─ Client (auth()->user())
  └─ Staff (Super Admin, Administrator, Project Manager roles)
- Content:
  ├─ Property details (code, address, owner)
  ├─ Inspection scheduled date/time
  ├─ Fee breakdown
  └─ Link to assign inspector (for staff)
```

---

## Phase 3: Work Payment (After Inspection Complete)

### Files Involved
- [app/Http/Controllers/Client/InspectionController.php](../app/Http/Controllers/Client/InspectionController.php) - `workPaymentCreate()`, `processWorkPayment()`
- [resources/views/client/inspections/work-payment.blade.php](../resources/views/client/inspections/work-payment.blade.php)

### Process

#### Step 1: Client Selects Payment Plan
```
GET /inspections/{inspection}/work-payment
→ InspectionController::workPaymentCreate()
  ├─ Load inspection with property/calculations
  ├─ Calculate ARP (estimated work cost): $X,XXX
  ├─ Show three payment plan options:
  │  ├─ FULL: Pay entire ARP now
  │  ├─ 50% DEPOSIT: Pay 50% now, rest on completion
  │  └─ PER-VISIT: Pay after each visit
  └─ For selected plan, create PaymentIntent:
     ├─ Amount: Plan-dependent (full/50%/first-visit amount)
     ├─ Metadata: {payment_type: 'work_start', inspection_id, plan_type, visits_total}
     └─ Return clientSecret to view
```

#### Step 2: Card Entry & Validation
```
Same as Inspection Fee (schedule.blade.php pattern):
- Pre-flight validation: createPaymentMethod()
- If invalid: Show error, NO CHARGE
- If valid: Confirm payment: confirmCardPayment()
```

#### Step 3: Server Processes Payment
```
POST /inspections/{inspection}/work-payment
→ InspectionController::processWorkPayment()
  ├─ Verify PaymentIntent succeeded
  ├─ Update inspection: status = 'work_in_progress'
  ├─ Create WorkPayment record with plan details
  ├─ If PER_VISIT plan: Initialize visit tracking (1 of N)
  └─ Send WorkPaymentReceivedNotification to staff
```

#### Step 4: Webhook Verification
```
Stripe webhook (payment_intent.succeeded):
- Metadata: payment_type = 'work_start'
- Handler: StripeWebhookController::handlePaymentIntentSucceeded()
  └─ Verify in database, send notification to staff
```

---

## Phase 4: Installment/Per-Visit Payments

### Files Involved
- [app/Http/Controllers/Client/InspectionController.php](../app/Http/Controllers/Client/InspectionController.php) - `payInstallmentCreate()`, `processInstallment()`
- [resources/views/client/inspections/pay-installment.blade.php](../resources/views/client/inspections/pay-installment.blade.php)

### Process

#### Step 1: Client Views Payment Due
```
GET /inspections/{inspection}/pay-installment
→ InspectionController::payInstallmentCreate()
  ├─ Determine payment type:
  │  ├─ PER_VISIT: Amount for current visit (e.g., 1 of 5)
  │  ├─ 50% DEPOSIT: Remaining balance due
  │  └─ INSTALLMENT: Next scheduled payment
  ├─ Show progress bar: "Visit 1 of 5 paid | 4 remaining"
  └─ Create PaymentIntent for installment amount
     └─ Metadata: {payment_type: 'per_visit', inspection_id, visit_number, visits_total}
```

#### Step 2: Card Entry (Same pattern)
```
Pre-flight validation → Charge only if valid → POST to server
```

#### Step 3: Server Records Payment
```
POST /inspections/{inspection}/pay-installment
→ InspectionController::processInstallment()
  ├─ Verify PaymentIntent
  ├─ Create InstallmentPayment record:
  │  ├─ amount_paid
  │  ├─ visit_number (if per-visit)
  │  ├─ payment_intent_id
  │  └─ status = 'completed'
  ├─ Check if all visits/installments paid:
  │  ├─ If YES: Update inspection status = 'completed'
  │  └─ If NO: Inspection remains 'work_in_progress'
  └─ Send InstallmentPaymentReceivedNotification
```

#### Step 4: Webhook Verification
```
Stripe webhook (payment_intent.succeeded):
- Metadata: payment_type = 'per_visit'
- Handler: StripeWebhookController::handlePaymentIntentSucceeded()
  ├─ Verify payment in database
  ├─ Update visit count if applicable
  └─ Send notification (different subject based on visit number)
```

---

## Error Handling & Failure Scenarios

### Client-Side Validation Failures
```
Scenario: User enters invalid card
1. cardElement.on('change') detects error
2. stripe.createPaymentMethod() fails
3. Error displayed in #card-errors div
4. Submit button remains disabled
5. NO STRIPE CHARGE ATTEMPTED
```

### Payment Failure
```
Scenario: Card declined or Stripe returns error
1. confirmCardPayment() returns error object
2. Error displayed: "Card was declined: [reason]"
3. Form re-enables for retry
4. NO DATABASE ENTRY CREATED
5. Staff NOT notified (silent failure on client)
```

### Webhook Failure
```
Files: app/Http/Controllers/StripeWebhookController.php

Scenario: Webhook attempt fails on first try
1. Stripe retries webhook delivery
2. If continued failure: Email admin
3. Manual webhook replay from Stripe Dashboard available

Scenario: Signature verification fails
1. Log security event
2. Return 403 (Forbidden)
3. Email security team
```

### Payment Failed After Charge
```
Scenario: Card charged but inspection creation fails
1. PaymentIntent succeeded, money taken from customer
2. Exception thrown during inspection record creation
3. Transaction rolls back BUT STRIPE CHARGE ALREADY PROCESSED
4. Webhook still arrives, confirms payment
5. Staff receives PaymentFailedNotification with error details
6. Manual intervention required to reconcile

Mitigation: All payments created via Stripe Webhooks as source of truth
```

---

## Notification Flow

### All Notifications
```
Classes (app/Notifications/):
- InspectionFeePaidNotification.php
- WorkPaymentReceivedNotification.php
- InstallmentPaymentReceivedNotification.php
- PaymentFailedNotification.php

Channels:
- Database: Stored in notifications table, accessible via user dashboard
- Mail: Sent via Gmail SMTP (MAIL_HOST=smtp.gmail.com)

Recipients:
- Client: Always receives notification
- Staff: Super Admin, Administrator, Project Manager (via roles)
```

### Notification Content Examples
```
InspectionFeePaidNotification:
Subject: "💳 Inspection Fee Paid — PROP-001"
Body:
  Property: 123 Main St, Springfield
  Owner: John Doe
  Inspection Fee: $449 (1 unit × $299 + $75 roof + $75 crawl)
  Scheduled: May 26, 2026 10:00 AM
  [Assign Inspector Button]

WorkPaymentReceivedNotification:
Subject: "💰 Work Payment Received — PROP-001"
Body:
  Property: 123 Main St
  Plan: Full Payment ($4,250)
  Amount Received: $4,250.00
  Status: Work can begin

InstallmentPaymentReceivedNotification:
Subject: "✅ Visit 1 of 5 Payment Received — PROP-001"
Body:
  Property: 123 Main St
  Visit: 1 of 5
  Amount: $850.00
  Remaining Balance: $3,400.00
  Next Visit: May 28, 2026

PaymentFailedNotification:
Subject: "⚠️ Payment Failed — 123 Main St"
Body:
  Reason: Card declined (insufficient funds)
  Amount Attempted: $449
  Action Required: Contact customer to retry payment
```

---

## Database Records Created

### Tables Involved
```
1. stripe_customers
   └─ Link: users.stripe_id

2. inspections
   ├─ payment_intent_id (from Stripe)
   ├─ status (payment_confirmed → inspection_scheduled → work_in_progress → completed)
   ├─ property_id
   └─ user_id (client)

3. work_payments (if applicable)
   ├─ inspection_id
   ├─ plan_type (full, fifty_fifty, per_visit)
   ├─ total_amount
   ├─ payment_intent_id
   └─ status

4. installment_payments (if per-visit/installment)
   ├─ inspection_id
   ├─ visit_number
   ├─ amount_paid
   ├─ payment_intent_id
   └─ status

5. notifications (via Laravel notifications)
   ├─ notifiable_id (user_id)
   ├─ data (JSON with details)
   ├─ read_at (null = unread)
   └─ created_at
```

---

## Stripe Integration Points

### Configuration
```
File: .env
STRIPE_KEY=pk_live_[configured in .env]
STRIPE_SECRET=sk_live_[configured in .env]
STRIPE_WEBHOOK_SECRET=[whsec_... configured in .env]
PHAR_STRIPE_PRODUCT_ID=prod_TotU9dBLf3Lvo8

File: config/cashier.php
- Configured with live keys from .env
- Webhook secret configured from .env
- PHAR product ID configured from .env
```

⚠️ Never commit .env file containing credentials to version control.
Use GitHub Secrets or environment variables for sensitive data.

### Stripe.js Methods Used
```
1. Stripe(publishableKey)
   - Initialize Stripe client

2. stripe.elements()
   - Create Elements instance

3. elements.create('card')
   - Create card element

4. cardElement.mount(selector)
   - Mount to DOM

5. stripe.createPaymentMethod(data)
   - PRE-FLIGHT VALIDATION without charging
   - Detects: card number, expiry, CVC issues

6. stripe.confirmCardPayment(clientSecret, data)
   - Confirm and charge

7. cardElement.on('change', callback)
   - Real-time validation feedback
```

### API Calls
```
Backend → Stripe:
1. POST /v1/payment_intents
   Create PaymentIntent with metadata

2. POST /v1/payment_intents/{id}/confirm
   Confirm payment (via confirmCardPayment on client)

3. GET /v1/payment_intents/{id}
   Verify status (in webhook handler)

Stripe → Backend:
1. POST /stripe/webhook
   Webhook delivery (signed with STRIPE_WEBHOOK_SECRET)
```

---

## Security Measures

### Client-Side
1. **Card Data**: Handled only by Stripe.js, never touches server
2. **Real-time Validation**: Prevents invalid card submission
3. **HTTPS**: All communication encrypted (etogo.laravel.cloud)
4. **CSRF Protection**: Disabled only for webhook route (Stripe signature verifies instead)

### Server-Side
1. **Webhook Signature Verification**: `Stripe\Webhook::constructEvent()` verifies `Stripe-Signature` header
2. **Payment Intent Verification**: Server verifies status before creating records
3. **Metadata Validation**: Checks payment_type matches expected type
4. **Database Transactions**: Atomic operations, rollback on failure
5. **Audit Logging**: All payments logged with metadata for forensics

### Stripe Account
1. **Live Keys**: Using production keys (pk_live_, sk_live_)
2. **Webhook Secret**: Rotated and stored in .env
3. **Testing**: $1.00 charge mode currently (can be toggled to full amounts)
4. **Customer Records**: Each user linked to Stripe customer via stripe_id

---

## Testing Payment Flow

### Local Testing
```
1. Set APP_ENV=local in .env
2. Use test card: 4242 4242 4242 4242
3. Any future expiry date
4. Any 3-digit CVC
5. Charge will be $1.00 (TEST_CHARGE_CENTS=100)
6. Webhook events visible in Stripe Dashboard test event log
```

### Production Testing
```
1. APP_ENV=production
2. Use actual test card or real card (charge is real $1.00)
3. Webhook events delivered to https://etogo.laravel.cloud/stripe/webhook
4. Email notifications sent to MAIL_FROM_ADDRESS
5. Database records created in production database (erpc)
```

### Verification Checklist
```
□ Property surcharge fields saved (has_high_pitched_roof, has_crawl_space)
□ Fee calculation correct (units × 299 + surcharges)
□ PaymentIntent created with correct metadata
□ Card validation prevents invalid submissions
□ Stripe charge processed
□ Webhook received and verified
□ Database records updated
□ Notifications sent to staff
□ Client sees success message
```

---

## Summary Diagram

```
PROPERTY REGISTRATION
    ↓
    ├─ Surcharge fields set (roof, crawl)
    ├─ Units recorded
    └─ Ready for inspection booking

INSPECTION FEE PAYMENT
    ├─ View: schedule.blade.php
    ├─ Calculate fee based on property surcharges
    ├─ Client enters card
    ├─ Pre-flight validation (createPaymentMethod)
    ├─ Confirm payment (confirmCardPayment)
    ├─ POST payment_intent_id to server
    ├─ Server verifies & creates inspection
    ├─ Webhook confirms from Stripe
    └─ Notification sent to staff

WORK PAYMENT (After inspection complete)
    ├─ View: work-payment.blade.php
    ├─ Show 3 plan options (Full, 50%, Per-Visit)
    ├─ Create PaymentIntent for selected plan
    ├─ Same validation & confirmation flow
    ├─ Webhook confirms payment
    └─ Update status to work_in_progress

INSTALLMENT PAYMENTS (Per-visit or remainder)
    ├─ View: pay-installment.blade.php
    ├─ Show progress bar (X of N visits paid)
    ├─ Create PaymentIntent for next payment
    ├─ Same validation & confirmation flow
    ├─ Webhook confirms payment
    ├─ Update visit count
    └─ Complete inspection when all paid

FAILURE SCENARIOS
    ├─ Invalid card → Show error, NO CHARGE
    ├─ Card declined → Show error, NO CHARGE
    ├─ DB error → Webhook retries, manual intervention
    └─ Lost webhook → Manual replay from Stripe Dashboard
```

---

## Related Documentation
- [STRIPE_COMPLETE_GUIDE.md](./STRIPE_COMPLETE_GUIDE.md) - Stripe configuration details
- [PAYMENT_SYSTEM_COMPLETE.md](./PAYMENT_SYSTEM_COMPLETE.md) - System architecture
- [CPI_PRICING_DATABASE_DESIGN.md](./CPI_PRICING_DATABASE_DESIGN.md) - Pricing structure
