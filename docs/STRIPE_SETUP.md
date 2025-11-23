# Stripe Product Setup Guide

## Overview
This guide walks you through creating Stripe products for EMURIA Property Care's 5 membership tiers.

## Prerequisites
- Stripe account created
- Test API keys added to `.env` file
- Database migrated with Stripe price ID columns

## Step 1: Access Stripe Dashboard
1. Go to https://dashboard.stripe.com/test/products
2. Make sure you're in **TEST MODE** (toggle in top right corner)

## Step 2: Create Products for Each Tier

### Tier 1 - Basic Care
1. Click "Add Product"
2. Enter details:
   - **Name**: Basic Care - Tier 1
   - **Description**: Preventive Essentials - Annual inspection, basic repairs, maintenance guidance
   - **Image**: Upload tier logo (optional)
3. Add pricing:
   - **Monthly Price**: 
     - Price: $199
     - Billing period: Monthly
     - Currency: USD
     - After saving, copy the **Price ID** (starts with `price_`)
   
   - **Annual Price**:
     - Click "Add another price"
     - Price: $2,189
     - Billing period: Yearly
     - Currency: USD
     - After saving, copy the **Price ID**

4. Update database with Price IDs

### Tier 2 - Standard Care
- Monthly: $349
- Annual: $3,839
(Repeat steps above)

### Tier 3 - Premium Care
- Monthly: $549
- Annual: $6,039
(Repeat steps above)

### Tier 4 - Elite Care
- Monthly: $849
- Annual: $9,339
(Repeat steps above)

### Tier 5 - Platinum Care
- Monthly: $1,499
- Annual: $16,489
(Repeat steps above)

## Step 3: Update Database with Stripe Price IDs

### Option A: Using PHP Artisan Tinker (Recommended)
```bash
php artisan tinker

# Tier 1
$tier1 = \App\Models\Tier::where('slug', 'basic-care')->first();
$tier1->stripe_price_id_monthly = 'price_1xxxxxxxxxxxxx';  # Replace with actual ID
$tier1->stripe_price_id_annual = 'price_1yyyyyyyyyyyyyy';  # Replace with actual ID
$tier1->save();

# Tier 2
$tier2 = \App\Models\Tier::where('slug', 'standard-care')->first();
$tier2->stripe_price_id_monthly = 'price_2xxxxxxxxxxxxx';
$tier2->stripe_price_id_annual = 'price_2yyyyyyyyyyyyyy';
$tier2->save();

# Tier 3
$tier3 = \App\Models\Tier::where('slug', 'premium-care')->first();
$tier3->stripe_price_id_monthly = 'price_3xxxxxxxxxxxxx';
$tier3->stripe_price_id_annual = 'price_3yyyyyyyyyyyyyy';
$tier3->save();

# Tier 4
$tier4 = \App\Models\Tier::where('slug', 'elite-care')->first();
$tier4->stripe_price_id_monthly = 'price_4xxxxxxxxxxxxx';
$tier4->stripe_price_id_annual = 'price_4yyyyyyyyyyyyyy';
$tier4->save();

# Tier 5
$tier5 = \App\Models\Tier::where('slug', 'platinum-care')->first();
$tier5->stripe_price_id_monthly = 'price_5xxxxxxxxxxxxx';
$tier5->stripe_price_id_annual = 'price_5yyyyyyyyyyyyyy';
$tier5->save();

exit
```

### Option B: Direct Database Update
```sql
-- Connect to your database and run:
UPDATE tiers SET 
    stripe_price_id_monthly = 'price_1xxxxxxxxxxxxx',
    stripe_price_id_annual = 'price_1yyyyyyyyyyyyyy'
WHERE slug = 'basic-care';

-- Repeat for other tiers...
```

## Step 4: Testing the Payment Flow

### Test Card Numbers (No real charges)
- **Success**: 4242 4242 4242 4242
- **Requires authentication**: 4000 0025 0000 3155
- **Declined**: 4000 0000 0000 0002

### Test Flow
1. Go to http://localhost/tiers
2. Select a tier and billing cadence
3. Fill registration form
4. Use test card: 4242 4242 4242 4242
5. CVV: Any 3 digits
6. Expiry: Any future date
7. Complete payment
8. Verify success page appears
9. Check Stripe Dashboard for payment record

## Step 5: Webhook Configuration (Optional but Recommended)

1. Go to https://dashboard.stripe.com/test/webhooks
2. Click "Add endpoint"
3. Endpoint URL: `https://your-domain.com/stripe/webhook` (use ngrok for local testing)
4. Select events:
   - `checkout.session.completed`
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
5. Copy **Signing secret** (starts with `whsec_`)
6. Add to `.env`: `STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxx`

## Going Live Checklist

When ready for production:
1. Switch Stripe Dashboard to **LIVE MODE**
2. Create all 5 products again in live mode
3. Update database with live Price IDs
4. Update `.env` with live API keys:
   - `STRIPE_KEY=pk_live_...`
   - `STRIPE_SECRET=sk_live_...`
5. Configure live webhook endpoint
6. Test with small real payment
7. Set up Stripe customer portal for self-service

## Troubleshooting

### "No such price" error
- Ensure Price IDs in database match exactly with Stripe Dashboard
- Verify you're in correct mode (test vs live)

### Payment not processing
- Check browser console for JavaScript errors
- Verify API keys are correct in `.env`
- Ensure `php artisan config:clear` was run after updating `.env`

### Subscription not created in database
- Check Laravel logs: `storage/logs/laravel.log`
- Verify webhook is configured correctly
- Check session data is being stored

## Support Resources
- Stripe Documentation: https://stripe.com/docs
- Cashier Documentation: https://laravel.com/docs/billing
- Test Cards: https://stripe.com/docs/testing
