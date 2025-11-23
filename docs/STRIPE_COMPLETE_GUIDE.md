# 🏦 Stripe Payment System - Complete Guide for EMURIA Property Care

## 📚 Table of Contents
1. [Understanding Stripe (Simple Explanation)](#understanding-stripe)
2. [How the System Works](#how-the-system-works)
3. [Test Mode Setup (Current)](#test-mode-setup)
4. [Going Live - Real Payments](#going-live)
5. [Adding Your Bank Account](#adding-bank-account)
6. [Receiving Money](#receiving-money)
7. [Troubleshooting](#troubleshooting)

---

## 🎯 Understanding Stripe (Simple Explanation)

### Think of Stripe as Your "Payment Cashier"

Imagine you own a coffee shop:

- **You (EMURIA)** = The business owner
- **Your Menu** = Your 5 membership tiers ($199, $349, $549, $849, $1,499)
- **Stripe** = The cashier who handles all money transactions
- **Customer's Card** = How they pay

### How It Works Step-by-Step

#### **Step 1: You Tell Stripe What You're Selling**

Just like putting items on your menu, you need to tell Stripe:
- "I'm selling Tier 1 membership for $199/month"
- "I'm also selling it for $1,990/year"

In Stripe, these are called:
- **Products** = What you're selling (Tier 1, Tier 2, etc.)
- **Prices** = How much it costs ($199/month or $1,990/year)

#### **Step 2: Customer Wants to Buy**

When someone clicks "Get Started" on your website:
1. They choose a tier (like choosing coffee size: small, medium, large)
2. They fill in their details (name, email)
3. They click "Continue to Payment"

#### **Step 3: Stripe Takes Over**

Your website says to Stripe:
> "Hey Stripe! This customer wants to buy Tier 3 monthly. Handle the payment for me!"

Stripe then:
1. Shows a secure payment form
2. Customer enters card details
3. Stripe processes the payment
4. Stripe tells your website "✅ Payment successful!" or "❌ Payment failed"

#### **Step 4: You Get the Money**

- Stripe holds the money for a few days (2-7 days)
- Then deposits it into your bank account
- They take a small fee (about 2.9% + 30¢ per transaction)

---

## 🔄 How the System Works

### Complete Customer Flow

```
┌─────────────┐
│   CUSTOMER  │  1. Visits emuriapropertycare.com
└──────┬──────┘
       │
       ▼
┌─────────────────────┐
│   YOUR WEBSITE      │  2. Clicks "Get Started"
│  (Laravel/EMURIA)   │  3. Selects Tier 3 - $549/month
│                     │  4. Fills registration form
│  Database:          │  5. Clicks "Continue to Payment"
│  - Users            │
│  - Tiers            │
│  - Subscriptions    │
└──────┬──────────────┘
       │
       │ 6. "Charge price_1ST3ju5Yg6BUm7TZWwwQPvnl"
       ▼
┌─────────────────────┐
│      STRIPE         │  7. Shows secure payment form
│                     │  8. Customer enters card: 4242...
│  - Validates card   │  9. Processes payment
│  - Creates charge   │  10. Returns success/fail
│  - Manages refunds  │
└──────┬──────────────┘
       │
       │ 11. "Payment successful! $549 received"
       ▼
┌─────────────────────┐
│   YOUR WEBSITE      │  12. Creates subscription in database
│                     │  13. Assigns Client role
│  Subscription {     │  14. Shows success page
│    user_id: 5       │  15. Sends welcome email
│    tier_id: 3       │
│    status: active   │
│    amount: 549      │
│  }                  │
└─────────────────────┘
       │
       │ 15. Customer can now access dashboard
       ▼
┌─────────────────────┐
│  CLIENT DASHBOARD   │  - Add properties
│                     │  - Schedule inspections
│                     │  - View invoices
└─────────────────────┘
```

### Where Products Come From

#### Your Database (Laravel):
```sql
-- Tiers table
id | name    | monthly_price | stripe_price_id_monthly
1  | Tier 1  | 199.00       | price_1ST3jr5Yg6BUm7TZUEMesGrB
2  | Tier 2  | 349.00       | price_1ST3jt5Yg6BUm7TZuHjAJILD
3  | Tier 3  | 549.00       | price_1ST3ju5Yg6BUm7TZWwwQPvnl
```

Created by:
1. `TierSeeder` - Creates tiers in YOUR database
2. `StripeProductSeeder` - Creates products in STRIPE and links them

#### Stripe's System:
```
Product: Tier 1 - Basic Care
├── Price 1: price_1ST3jr5Yg6BUm7TZUEMesGrB ($199/month)
└── Price 2: price_1ST3jr5Yg6BUm7TZkhceJ75q ($2,189/year)

Product: Tier 2 - Standard Care
├── Price 1: price_1ST3jt5Yg6BUm7TZuHjAJILD ($349/month)
└── Price 2: price_1ST3jt5Yg6BUm7TZohEnPbTW ($3,839/year)

...and so on
```

### The Connection

```php
// When customer clicks "Subscribe to Tier 3 Monthly"

// 1. Your website finds the tier
$tier = Tier::find(3); // Tier 3

// 2. Gets the Stripe price ID
$stripePriceId = $tier->stripe_price_id_monthly; 
// "price_1ST3ju5Yg6BUm7TZWwwQPvnl"

// 3. Sends customer to Stripe Checkout
$checkout = $user->newSubscription('default', $stripePriceId)
    ->checkout([
        'success_url' => '/checkout/success',
        'cancel_url' => '/checkout/cancel',
    ]);

// 4. Stripe handles the payment
// 5. Redirects back to your success page
```

---

## 🧪 Test Mode Setup (Current)

### What is Test Mode?

Test mode is like a **practice environment** where:
- ✅ Everything works exactly like real payments
- ✅ But NO real money is involved
- ✅ You can test unlimited times for free
- ✅ Use fake credit card numbers

### Your Current Setup

#### API Keys in `.env`:
```env
STRIPE_KEY=pk_test_YOUR_PUBLISHABLE_KEY_HERE
STRIPE_SECRET=sk_test_YOUR_SECRET_KEY_HERE
```

Notice: Both start with `pk_test_` and `sk_test_` = TEST MODE

**Important:** Get your actual keys from your Stripe Dashboard at https://dashboard.stripe.com/test/apikeys

### Test Credit Cards

Use these cards in test mode (they won't charge real money):

| Card Number         | Result                    | Use For                  |
|---------------------|---------------------------|--------------------------|
| 4242 4242 4242 4242 | ✅ Success                | Normal successful payment |
| 4000 0025 0000 3155 | 🔐 Requires Authentication| Test 3D Secure (2FA)     |
| 4000 0000 0000 0002 | ❌ Declined               | Test card decline        |
| 4000 0000 0000 9995 | ❌ Insufficient Funds     | Test low balance         |
| 4000 0000 0000 9987 | ❌ Lost Card              | Test stolen card         |

**For ALL test cards:**
- **Expiry Date:** Any future date (e.g., 12/28)
- **CVC:** Any 3 digits (e.g., 123)
- **ZIP Code:** Any 5 digits (e.g., 12345)
- **Name:** Any name

### Testing Your System

1. **Start your server:**
   ```bash
   # Make sure WAMP Apache is running, or:
   php artisan serve
   ```

2. **Visit:** `http://localhost/`

3. **Click:** "Get Started" button

4. **Select:** Any tier (e.g., Tier 3)

5. **Choose:** Monthly or Annual billing

6. **Fill form:**
   - Name: Test User
   - Email: test@example.com
   - Phone: +256708356505
   - Password: password123

7. **Payment:**
   - Card: `4242 4242 4242 4242`
   - Expiry: `12/28`
   - CVC: `123`

8. **Success!** You'll see the success page

9. **View in Stripe:**
   - Go to: https://dashboard.stripe.com/test/payments
   - See your test payment

---

## 🚀 Going Live - Real Payments

### Prerequisites Before Going Live

- [ ] Business registered and legal
- [ ] Bank account ready
- [ ] Tested thoroughly in test mode
- [ ] Terms of service and privacy policy on website
- [ ] Refund policy documented
- [ ] Customer support contact available

### Step 1: Complete Stripe Account Setup

#### 1.1 Log into Stripe Dashboard
Visit: https://dashboard.stripe.com/

#### 1.2 Activate Your Account
Click: **"Activate your account"** in the top banner

#### 1.3 Provide Business Information

**Business Details:**
```
Business Name: EMURIA Property Care
Business Type: Choose one:
  - Individual/Sole Proprietor
  - Company/Corporation
  - Non-profit
  
Country: Uganda (or your country)
```

**Personal Information:**
```
Legal Name: Your full legal name
Date of Birth: DD/MM/YYYY
Phone: +256708356505
Address: Your physical business address
```

**Business Description:**
```
What does your business do?
"Property maintenance and regeneration services with tiered 
membership plans. We provide inspections, repairs, and ongoing 
property care for residential and commercial properties."

Website: https://your-domain.com
```

#### 1.4 Identity Verification

Stripe will ask for documents:
- **Government ID** (Passport, National ID, or Driver's License)
- **Business Documents** (if company):
  - Business registration certificate
  - Tax ID/TIN
- **Photo:** Take a selfie or upload photo

**Why?** This prevents fraud and ensures legitimate businesses.

---

### Step 2: Add Your Bank Account

#### Option A: Add Bank Account in Dashboard

1. **Go to:** https://dashboard.stripe.com/settings/payouts

2. **Click:** "Add bank account"

3. **Enter Bank Details:**

   **For Uganda (Mobile Money):**
   ```
   Currency: UGX (Ugandan Shilling)
   Mobile Money Provider: MTN or Airtel
   Phone Number: +256708356505
   Account Name: Your Name
   ```

   **For Uganda (Bank Account):**
   ```
   Currency: UGX or USD
   Bank Name: Select your bank
   Account Number: Your account number
   Account Holder Name: Your name (must match ID)
   Branch: Your bank branch
   ```

   **For International Bank:**
   ```
   Country: Your bank's country
   Currency: USD, EUR, etc.
   Routing Number: Your bank's routing number
   Account Number: Your account number
   Account Holder Name: Your name
   SWIFT/BIC Code: For international transfers
   ```

4. **Verify:** Stripe will send small test deposits (like 0.32 and 0.45)

5. **Confirm:** Enter the exact amounts to verify ownership

#### Option B: Add via Mobile Money (East Africa)

```
Provider: MTN Mobile Money or Airtel Money
Number: +256708356505
Name: Your registered name
```

---

### Step 3: Get Live API Keys

#### 3.1 Switch to Live Mode

1. **Go to:** https://dashboard.stripe.com/
2. **Toggle:** Switch from "Test Mode" to "Live Mode" (top right)
3. **Notice:** Interface turns from orange to black

#### 3.2 Get Your Live API Keys

1. **Go to:** https://dashboard.stripe.com/apikeys
2. **Copy:** Your live keys:

```
Publishable key: pk_live_xxxxxxxxxxxxxxxxxxxxx
Secret key: sk_live_xxxxxxxxxxxxxxxxxxxxx (Click "Reveal live key")
```

⚠️ **IMPORTANT:** 
- NEVER share your secret key
- NEVER commit it to Git
- NEVER show it publicly

---

### Step 4: Create Live Products

You have 2 options:

#### Option A: Run Seeder (Recommended)

1. **Update `.env` with live keys:**
```env
STRIPE_KEY=pk_live_xxxxxxxxxxxxxxxxxxxxx
STRIPE_SECRET=sk_live_xxxxxxxxxxxxxxxxxxxxx
```

2. **Clear config cache:**
```bash
php artisan config:clear
```

3. **Run seeder in live mode:**
```bash
php artisan db:seed --class=StripeProductSeeder
```

This creates all 5 products in **LIVE MODE** automatically!

#### Option B: Manual Creation

1. Go to: https://dashboard.stripe.com/products
2. Create each product manually (same as test mode)
3. Copy price IDs and update database

---

### Step 5: Update Your Application

#### 5.1 Update `.env` File

```env
# Change from test keys to live keys
STRIPE_KEY=pk_live_xxxxxxxxxxxxxxxxxxxxx  # Changed!
STRIPE_SECRET=sk_live_xxxxxxxxxxxxxxxxxxxxx  # Changed!

# Update webhook secret (after setting up webhook)
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxx

# Set environment to production
APP_ENV=production
APP_DEBUG=false  # IMPORTANT: Never true in production!
```

#### 5.2 Clear All Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

#### 5.3 Update Database with Live Price IDs

If you ran the seeder, this is automatic. Otherwise:

```bash
php artisan tinker

# Update each tier with live price IDs
$tier1 = \App\Models\Tier::find(1);
$tier1->stripe_price_id_monthly = 'price_xxxxx';  # Live price ID
$tier1->stripe_price_id_annual = 'price_yyyyy';   # Live price ID
$tier1->save();

# Repeat for all tiers...
```

---

### Step 6: Set Up Webhooks (Important!)

Webhooks notify your site when payments succeed/fail.

#### 6.1 Create Webhook Endpoint

1. **Go to:** https://dashboard.stripe.com/webhooks
2. **Click:** "Add endpoint"
3. **Endpoint URL:** 
   ```
   https://your-domain.com/stripe/webhook
   ```
   
   Note: Laravel Cashier automatically creates this route

4. **Select Events to Listen:**
   ```
   ✅ checkout.session.completed
   ✅ customer.subscription.created
   ✅ customer.subscription.updated
   ✅ customer.subscription.deleted
   ✅ invoice.payment_succeeded
   ✅ invoice.payment_failed
   ✅ customer.subscription.trial_will_end
   ```

5. **Click:** "Add endpoint"

6. **Copy:** Signing secret (starts with `whsec_`)

#### 6.2 Add Webhook Secret to `.env`

```env
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxxx
```

#### 6.3 Test Webhook

```bash
# In Stripe Dashboard, click "Send test webhook"
# Check your logs: storage/logs/laravel.log
```

---

### Step 7: Final Testing Before Launch

#### Test with Small Real Payment

1. Use your own card
2. Subscribe to cheapest tier ($199)
3. Complete full flow
4. Verify:
   - Payment appears in Stripe Dashboard
   - Subscription created in your database
   - You received confirmation email
   - Dashboard access works

#### Test Cancellation

1. Cancel the test subscription
2. Verify it stops charging

#### Test Refund

1. Issue a refund from Stripe Dashboard
2. Verify money returns to card

---

## 💰 Receiving Money

### How Stripe Payouts Work

#### Payout Schedule

**Default Schedule:**
- **Frequency:** Daily (once per day)
- **Delay:** 2-7 business days after charge
- **Time:** Around same time each day

**Example Timeline:**
```
Monday 2pm: Customer pays $549
↓
Tuesday 2pm: Payout initiated by Stripe
↓
Wednesday-Friday: Bank processing
↓
Friday: Money appears in your bank account
```

#### Payout Amount

```
Customer Payment:     $549.00
Stripe Fee (2.9%):    -$15.92
Stripe Fixed Fee:     -$0.30
─────────────────────────────
Your Payout:          $532.78
```

**Stripe Fees (Standard):**
- 2.9% + $0.30 per successful card charge
- No setup fees
- No monthly fees
- Only pay when you get paid

### Viewing Payouts

#### In Stripe Dashboard

1. **Go to:** https://dashboard.stripe.com/balance/overview
2. **See:**
   - **Available balance:** Ready to payout
   - **Pending balance:** Being processed
   - **Total volume:** All-time revenue

3. **Click:** "Payouts" tab to see:
   - Date of payout
   - Amount paid
   - Bank account used
   - Status (paid, in transit, failed)

#### Payout Status

- ✅ **Paid:** Money sent to your bank
- 🔄 **In Transit:** On the way (1-3 days)
- ⏳ **Pending:** Waiting for payout schedule
- ❌ **Failed:** Issue with bank account (update details)

### Changing Payout Settings

1. **Go to:** https://dashboard.stripe.com/settings/payouts

2. **Change Frequency:**
   - Daily (default)
   - Weekly (every Monday, Tuesday, etc.)
   - Monthly (1st, 15th, etc.)
   - Manual (you control when)

3. **Change Bank Account:**
   - Add new account
   - Set as default
   - Delete old account

---

## 📊 Understanding Your Stripe Dashboard

### Key Pages

#### Balance Overview
https://dashboard.stripe.com/balance/overview
- See money coming in
- Track pending payouts
- View payout history

#### Payments
https://dashboard.stripe.com/payments
- All customer payments
- Filter by date, amount, status
- Issue refunds
- View customer details

#### Customers
https://dashboard.stripe.com/customers
- All your subscribers
- Customer payment history
- Subscription status
- Contact information

#### Subscriptions
https://dashboard.stripe.com/subscriptions
- Active subscriptions
- Cancelled subscriptions
- Upcoming renewals
- Change subscription plans

#### Products
https://dashboard.stripe.com/products
- Your 5 membership tiers
- Pricing information
- Edit prices (creates new version)

---

## 🔐 Security Best Practices

### Protect Your Secret Key

```bash
# ✅ GOOD: In .env file (not committed to Git)
STRIPE_SECRET=sk_live_xxxxx

# ❌ BAD: Hardcoded in code
$stripe = new \Stripe\StripeClient('sk_live_xxxxx'); // DON'T DO THIS!

# ✅ GOOD: Use config
$stripe = new \Stripe\StripeClient(config('cashier.secret'));
```

### Git Security

Add to `.gitignore`:
```
.env
.env.backup
.env.production
```

### Never Log Secret Keys

```php
// ❌ BAD
Log::info('Stripe key: ' . config('cashier.secret'));

// ✅ GOOD
Log::info('Stripe payment processed');
```

---

## 🛠️ Troubleshooting

### Issue: "No such price"

**Cause:** Price ID doesn't exist in Stripe

**Solution:**
```bash
# Check your database has correct price IDs
php artisan tinker
\App\Models\Tier::select('name', 'stripe_price_id_monthly')->get();

# Verify in Stripe Dashboard
# Go to: Products → Click product → See price IDs
```

### Issue: Payouts Not Coming

**Possible Causes:**
1. Bank account not verified
2. Account under review
3. Minimum payout not reached
4. Identity verification pending

**Solution:**
1. Check: https://dashboard.stripe.com/settings/payouts
2. Verify bank account
3. Complete identity verification
4. Contact Stripe support

### Issue: "Account Restricted"

**Cause:** Stripe detected unusual activity

**Solution:**
1. Check email from Stripe
2. Provide requested documents
3. Contact support: support@stripe.com
4. Usually resolved in 1-2 days

### Issue: Customer Can't Pay

**Common Reasons:**
1. Card declined (insufficient funds)
2. 3D Secure failed (wrong password)
3. Card expired
4. International card blocked

**Solution:**
1. Ask customer to try different card
2. Check if 3D Secure is enabled
3. Verify card is not expired

---

## 📞 Support & Resources

### Stripe Support

**Email:** support@stripe.com

**Live Chat:** 
- Go to: https://dashboard.stripe.com/
- Click: "?" icon (bottom right)
- Available 24/7

**Phone:** 
- US: +1 (888) 926-2289
- International: Check dashboard for your country

### Documentation

- **Stripe Docs:** https://stripe.com/docs
- **Laravel Cashier:** https://laravel.com/docs/billing
- **Stripe API:** https://stripe.com/docs/api
- **Testing:** https://stripe.com/docs/testing

### Community

- **Stripe Reddit:** r/stripe
- **Laravel Forums:** https://laracasts.com/discuss
- **Stack Overflow:** Tag with `stripe` and `laravel-cashier`

---

## ✅ Pre-Launch Checklist

Before accepting real payments:

### Technical
- [ ] Switched to live API keys in `.env`
- [ ] Ran `StripeProductSeeder` in live mode
- [ ] Set up webhook endpoint
- [ ] Tested with real card (small amount)
- [ ] Verified subscription creation works
- [ ] Checked dashboard access for new customers
- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] All caches cleared

### Business
- [ ] Stripe account fully activated
- [ ] Bank account added and verified
- [ ] Business information complete
- [ ] Identity verification approved
- [ ] Terms of service on website
- [ ] Privacy policy published
- [ ] Refund policy documented
- [ ] Customer support email set up

### Testing
- [ ] Made test purchase with real card
- [ ] Verified money appears in Stripe balance
- [ ] Tested cancellation flow
- [ ] Tested refund process
- [ ] Checked email notifications work
- [ ] Verified webhook events received

---

## 🎉 Summary

### What You Have Now (Test Mode)
1. ✅ 5 membership tiers in your database
2. ✅ 5 products in Stripe (test mode)
3. ✅ 10 prices (5 monthly + 5 annual) in Stripe
4. ✅ Complete payment flow working
5. ✅ Customer can subscribe and pay
6. ✅ Test cards work perfectly

### To Go Live
1. **Activate Stripe Account** (provide business info)
2. **Add Bank Account** (for receiving money)
3. **Get Live API Keys** (replace test keys)
4. **Create Live Products** (run seeder again)
5. **Set Up Webhooks** (get notifications)
6. **Test with Real Card** (small amount)
7. **Launch!** 🚀

### Money Flow
```
Customer pays $549
    ↓
Stripe processes (instantly)
    ↓
Stripe takes 2.9% + $0.30 = $16.22
    ↓
You get $532.78
    ↓
Payout in 2-7 days
    ↓
Money in your bank account! 💰
```

---

**Need Help?** 
- Check Stripe Dashboard for issues
- Email: support@stripe.com
- Review Laravel logs: `storage/logs/laravel.log`

**Ready to Launch?**
Follow the "Going Live" section step by step!
