# Stripe Payment Integration Documentation

## Overview
This application uses **Stripe** for processing inspection fee payments. The integration is built using **Laravel Cashier** and **Stripe.js v3** for secure, PCI-compliant payment processing.

---

## Configuration

### 1. Stripe Package
**Laravel Cashier** is installed via Composer:
```json
"laravel/cashier": "^16.0"
```

### 2. Environment Variables (.env)
```env
STRIPE_KEY=pk_test_51SSnwi5Yg6BUm7TZ...
STRIPE_SECRET=sk_test_51SSnwi5Yg6BUm7TZ...
STRIPE_WEBHOOK_SECRET=
```

**Key Types:**
- `STRIPE_KEY` (Publishable Key): Used in frontend JavaScript - Safe to expose publicly
- `STRIPE_SECRET` (Secret Key): Used in backend PHP - Must remain private
- Keys starting with `pk_test_` and `sk_test_` indicate **TEST MODE**

### 3. Config File (config/cashier.php)
```php
'key' => env('STRIPE_KEY'),        // Frontend key
'secret' => env('STRIPE_SECRET'),  // Backend key
```

---

## How Payments Work

### Payment Flow
1. **Client schedules inspection** → Opens schedule form
2. **Payment Intent created** → Backend creates Stripe Payment Intent
3. **Card details entered** → Client enters card via Stripe Elements
4. **Payment processed** → Stripe validates and charges card
5. **Inspection created** → Database records inspection as "paid"
6. **Redirect to properties** → Success message displayed

### Architecture
```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│   Browser   │────>│   Laravel    │────>│   Stripe    │
│ (Stripe.js) │     │  Controller  │     │  (Payment   │
│             │<────│              │<────│   Intent)   │
└─────────────┘     └──────────────┘     └─────────────┘
```

---

## Implementation Details

### Backend (InspectionController.php)

#### Creating Payment Intent
```php
public function scheduleCreate(Property $property)
{
    $stripe = new \Stripe\StripeClient(config('cashier.secret'));
    
    $paymentIntent = $stripe->paymentIntents->create([
        'amount' => 29900,  // $299.00 in cents
        'currency' => 'usd',
        'metadata' => [
            'property_id' => $property->id,
            'user_id' => Auth::id(),
        ],
    ]);

    return view('client.inspections.schedule', [
        'clientSecret' => $paymentIntent->client_secret,
        'stripeKey' => config('cashier.key'),
    ]);
}
```

#### Processing Payment
```php
public function scheduleStore(Request $request, Property $property)
{
    $stripe = new \Stripe\StripeClient(config('cashier.secret'));
    $paymentIntent = $stripe->paymentIntents->retrieve($request->payment_intent_id);

    if ($paymentIntent->status !== 'succeeded') {
        throw new \Exception('Payment not completed');
    }

    // Create inspection with paid status
    Inspection::create([
        'property_id' => $property->id,
        'inspection_fee_amount' => 299.00,
        'inspection_fee_status' => 'paid',
        'inspection_fee_paid_at' => now(),
    ]);
}
```

### Frontend (schedule.blade.php)

#### Stripe.js Integration
```html
<script src="https://js.stripe.com/v3/"></script>
<script>
    const stripe = Stripe('{{ $stripeKey }}');
    const elements = stripe.elements();
    const cardElement = elements.create('card');
    cardElement.mount('#card-element');
</script>
```

#### Payment Confirmation
```javascript
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

if (paymentIntent.status === 'succeeded') {
    // Submit inspection details to backend
}
```

---

## Test Mode

### Test Card Numbers
Use these cards in test mode (dev/staging):

| Card Type   | Number              | Expiry     | CVC  |
|-------------|---------------------|------------|------|
| Visa        | 4242 4242 4242 4242 | Any future | Any  |
| Mastercard  | 5555 5555 5555 4444 | Any future | Any  |
| Amex        | 3782 822463 10005   | Any future | Any  |
| Decline     | 4000 0000 0000 0002 | Any future | Any  |

**Important:** Real cards won't work in test mode!

### Testing Payments
1. Visit: `http://localhost/client/inspections/{property_id}/schedule`
2. Fill inspection details
3. Use test card: `4242 4242 4242 4242`
4. Expiry: `12/28`, CVC: `123`
5. Click "Pay & Schedule"

View test payments at: https://dashboard.stripe.com/test/payments

---

## Production Setup

### Switching to Live Mode
1. Get live API keys from Stripe Dashboard
2. Update `.env`:
```env
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
```
3. Test with real cards (small amounts first!)
4. Set up webhook endpoint for payment confirmations

### Webhook Configuration (Optional)
```env
STRIPE_WEBHOOK_SECRET=whsec_...
```

Register webhook at: https://dashboard.stripe.com/webhooks
Endpoint URL: `https://yourdomain.com/stripe/webhook`

Events to listen for:
- `payment_intent.succeeded`
- `payment_intent.payment_failed`
- `charge.refunded`

---

## Security Features

### PCI Compliance
✅ Card details never touch your server
✅ Stripe Elements handles tokenization
✅ PCI DSS Level 1 compliant (via Stripe)
✅ HTTPS required in production

### Data Protection
- Secret key stored in `.env` (never commit to Git)
- Payment Intent used for 3D Secure authentication
- Client Secret is single-use per payment

---

## Fees & Pricing

### Inspection Fee
- Amount: **$299.00** (defined in `InspectionController`)
- Sent to Stripe as: `29900` cents

### Stripe Fees (Test Mode = Free)
- Live Mode: 2.9% + $0.30 per successful charge
- Example: $299.00 charge = $8.97 + $0.30 = $9.27 fee

---

## Routes

```php
// Client inspection routes
Route::middleware(['auth', 'role:client'])->group(function () {
    Route::get('/inspections/{property}/schedule', 
        [InspectionController::class, 'scheduleCreate'])
        ->name('client.inspections.schedule');
    
    Route::post('/inspections/{property}/schedule', 
        [InspectionController::class, 'scheduleStore'])
        ->name('client.inspections.store-schedule');
});
```

---

## Database Schema

### Inspection Fee Fields
```php
$table->decimal('inspection_fee_amount', 10, 2)->nullable();
$table->enum('inspection_fee_status', ['pending', 'paid', 'failed'])->default('pending');
$table->timestamp('inspection_fee_paid_at')->nullable();
```

### Query Paid Inspections
```php
$paidInspection = $property->inspections()
    ->where('inspection_fee_status', 'paid')
    ->first();
```

---

## Troubleshooting

### Common Errors

#### "Card declined" with real card
**Problem:** Using real card in test mode  
**Solution:** Use test card numbers (4242 4242 4242 4242)

#### "Invalid API key"
**Problem:** Wrong key or missing from .env  
**Solution:** Check `STRIPE_SECRET` in .env file

#### Card element not showing
**Problem:** Stripe.js not loaded or wrong publishable key  
**Solution:** Verify `STRIPE_KEY` and check browser console

#### Payment succeeds but inspection not created
**Problem:** Database transaction failed  
**Solution:** Check Laravel logs: `storage/logs/laravel.log`

---

## Resources

- **Stripe Dashboard:** https://dashboard.stripe.com
- **Test Payments:** https://dashboard.stripe.com/test/payments
- **API Docs:** https://stripe.com/docs/api
- **Test Cards:** https://stripe.com/docs/testing
- **Laravel Cashier:** https://laravel.com/docs/billing
- **Stripe.js Reference:** https://stripe.com/docs/js

---

## Support Contacts

- **Stripe Support:** https://support.stripe.com
- **Laravel Cashier Issues:** https://github.com/laravel/cashier-stripe
- **Development Team:** [Your team contact]

---

*Last Updated: January 23, 2026*
