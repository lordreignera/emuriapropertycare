# 🛠️ Adding Products in Stripe Dashboard - Complete Guide

## 📋 Table of Contents
1. [Overview](#overview)
2. [Accessing Stripe Dashboard](#accessing-stripe-dashboard)
3. [Creating New Products](#creating-new-products)
4. [Adding Prices to Products](#adding-prices)
5. [Connecting to Your Database](#connecting-to-database)
6. [Editing Existing Products](#editing-products)
7. [Managing Prices](#managing-prices)
8. [Best Practices](#best-practices)
9. [Troubleshooting](#troubleshooting)

---

## 🎯 Overview

### What Are Products and Prices?

**Products** = What you're selling
- Example: "Tier 3 - Premium Care"
- Contains: Name, description, images
- Like a menu item

**Prices** = How much the product costs
- Example: "$549/month" or "$6,039/year"
- One product can have multiple prices
- Like different sizes of the same coffee

### Why Use Stripe Dashboard?

✅ **Visual interface** - Easy to use, no code needed
✅ **Quick changes** - Update products instantly
✅ **Preview** - See how customers will see it
✅ **Flexible** - Add/edit/archive anytime
✅ **Safe** - Test mode won't charge real money

---

## 🚀 Accessing Stripe Dashboard

### Step 1: Login to Stripe

1. **Go to:** https://dashboard.stripe.com/
2. **Enter:**
   - Email: Your Stripe account email
   - Password: Your Stripe password
3. **Click:** "Sign in"

### Step 2: Switch to Test Mode

**IMPORTANT:** Always use test mode when learning!

```
Look at top right corner:
[ Test mode ] ← Click this toggle

Orange = Test Mode (safe, no real money)
Black = Live Mode (real money - be careful!)
```

### Step 3: Navigate to Products

**Option A:** Use sidebar
- Click **"Products"** in left menu

**Option B:** Direct link
- https://dashboard.stripe.com/test/products

You'll see your existing products (Tier 1-5).

---

## ➕ Creating New Products

### Step-by-Step: Add New Product

#### 1. Click "Add Product"

Find the blue button in top right corner:
```
[+ Add product]
```

#### 2. Fill in Product Details

**Product Information:**

```
Name: *
Tier 6 - Premium Plus Care

Description:
Our ultimate property care package with 24/7 support,
dedicated property manager, and unlimited consultations.
Perfect for large estates and commercial properties.
```

**Image (Optional but Recommended):**
```
Click: [Upload image]
Choose: Your tier logo or icon
Size: 1400x1400 pixels (recommended)
Format: PNG or JPG
```

**Statement Descriptor (Optional):**
```
EMURIA TIER6

This appears on customer's credit card statement.
Max 22 characters, no special symbols.
```

**Unit Label (Optional):**
```
Leave blank for subscriptions
Use for physical products (e.g., "box", "item")
```

#### 3. Configure Pricing Options

**Pricing Model:**
```
○ Standard pricing (Select this)
○ Package pricing
○ Graduated pricing
```

**Select:** Standard pricing (most common for subscriptions)

---

## 💰 Adding Prices to Products

### Price Configuration

#### For Monthly Subscription:

```
Price: 1999.00
Currency: USD $

Billing period:
[✓] Recurring

└─> Interval: Monthly ▼
    or
└─> Custom: 1 month(s)

Price description (optional):
Monthly subscription - billed every month
```

**Click:** "Add price"

#### For Annual Subscription:

After the product is created, add another price:

**Click:** "Add another price"

```
Price: 21989.00
Currency: USD $

Billing period:
[✓] Recurring

└─> Interval: Yearly ▼
    or
└─> Custom: 1 year(s)

Price description (optional):
Annual subscription - Save 8% (billed yearly)
```

**Click:** "Add price"

### Understanding Price Display

After creating both prices, you'll see:

```
┌─────────────────────────────────────────┐
│ Tier 6 - Premium Plus Care             │
├─────────────────────────────────────────┤
│                                         │
│ Monthly Price                           │
│ price_1ST3abc123xyz456                  │
│ $1,999.00 / month                       │
│ [Copy ID] [⋮]                          │
│                                         │
│ Annual Price                            │
│ price_1ST3def789uvw123                  │
│ $21,989.00 / year                       │
│ [Copy ID] [⋮]                          │
└─────────────────────────────────────────┘
```

### Copying Price IDs

**Important:** You'll need these IDs for your database!

1. **Hover** over the Price ID
2. **Click** the copy icon
3. **Save** somewhere safe (Notepad, etc.)

```
Monthly ID: price_1ST3abc123xyz456
Annual ID:  price_1ST3def789uvw123
```

---

## 💾 Connecting to Your Database

After creating the product in Stripe, add it to your Laravel database.

### Method 1: Using Tinker (Quick & Easy)

**Step 1:** Open Terminal
```bash
cd C:\wamp64\www\EMURIAREGENERATIVEPROPERTYCARE
php artisan tinker
```

**Step 2:** Create Tier in Database
```php
\App\Models\Tier::create([
    'name' => 'Tier 6',
    'slug' => 'premium-plus-care',
    'icon' => '👑',
    'experience' => 'Ultimate Care Experience',
    'description' => 'Our most comprehensive property care package with 24/7 priority support and dedicated property manager.',
    'features' => json_encode([
        'Everything in Tier 5',
        '24/7 Priority Emergency Response',
        'Dedicated Property Manager',
        'Quarterly Executive Reports',
        'VIP Customer Support Line',
        'Unlimited Consultations',
        'Custom Project Planning',
        'Annual Property Health Audit'
    ]),
    'monthly_price' => 1999.00,
    'annual_price' => 21989.00,
    'stripe_price_id_monthly' => 'price_1ST3abc123xyz456',  // Paste your actual ID
    'stripe_price_id_annual' => 'price_1ST3def789uvw123',   // Paste your actual ID
    'coverage_limit' => 100000.00,
    'designed_for' => 'Large estates, luxury properties, and commercial buildings requiring premium care',
    'is_active' => true,
    'sort_order' => 6
]);
```

**Step 3:** Verify Creation
```php
// Check if it was created
\App\Models\Tier::where('slug', 'premium-plus-care')->first();

// Count total tiers
\App\Models\Tier::count();  // Should be 6 now

exit
```

### Method 2: Using Database Seeder (Professional)

**Step 1:** Create Seeder
```bash
php artisan make:seeder Tier6Seeder
```

**Step 2:** Edit the Seeder

Open: `database/seeders/Tier6Seeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tier;

class Tier6Seeder extends Seeder
{
    /**
     * Seed Tier 6 - Premium Plus Care
     */
    public function run(): void
    {
        Tier::create([
            'name' => 'Tier 6',
            'slug' => 'premium-plus-care',
            'icon' => '👑',
            'experience' => 'Ultimate Care Experience',
            'description' => 'Our most comprehensive property care package with 24/7 priority support.',
            'features' => json_encode([
                'Everything in Tier 5',
                '24/7 Priority Emergency Response',
                'Dedicated Property Manager',
                'Quarterly Executive Reports',
                'VIP Customer Support Line',
                'Unlimited Consultations',
                'Custom Project Planning',
                'Annual Property Health Audit',
                'Priority Scheduling',
                'Concierge Service Access'
            ]),
            'monthly_price' => 1999.00,
            'annual_price' => 21989.00,
            'stripe_price_id_monthly' => 'price_YOUR_MONTHLY_ID_HERE',
            'stripe_price_id_annual' => 'price_YOUR_ANNUAL_ID_HERE',
            'coverage_limit' => 100000.00,
            'designed_for' => 'Large estates, luxury properties, and commercial buildings',
            'is_active' => true,
            'sort_order' => 6
        ]);

        $this->command->info('✅ Tier 6 created successfully!');
    }
}
```

**Step 3:** Replace Price IDs

Change these lines with your actual IDs from Stripe:
```php
'stripe_price_id_monthly' => 'price_1ST3abc123xyz456',  // Your monthly ID
'stripe_price_id_annual' => 'price_1ST3def789uvw123',   // Your annual ID
```

**Step 4:** Run the Seeder
```bash
php artisan db:seed --class=Tier6Seeder
```

**Output:**
```
INFO  Seeding database.
✅ Tier 6 created successfully!
```

### Method 3: Direct Database Query

**Using MySQL Command Line:**

```sql
INSERT INTO tiers (
    name, slug, icon, experience, description, features,
    monthly_price, annual_price,
    stripe_price_id_monthly, stripe_price_id_annual,
    coverage_limit, designed_for, is_active, sort_order,
    created_at, updated_at
) VALUES (
    'Tier 6',
    'premium-plus-care',
    '👑',
    'Ultimate Care Experience',
    'Our most comprehensive property care package',
    '["Everything in Tier 5","24/7 Priority Emergency Response","Dedicated Property Manager"]',
    1999.00,
    21989.00,
    'price_1ST3abc123xyz456',
    'price_1ST3def789uvw123',
    100000.00,
    'Large estates requiring premium care',
    1,
    6,
    NOW(),
    NOW()
);
```

---

## 🎨 Editing Existing Products

### Changing Product Details

#### 1. Find the Product

**Go to:** https://dashboard.stripe.com/test/products

**Click:** On the product name (e.g., "Tier 3")

#### 2. Edit Information

You can change:

```
✅ Product name
✅ Description
✅ Image
✅ Statement descriptor
✅ Metadata
```

**Click:** "Save product"

#### 3. What Can't Be Changed?

```
❌ Product ID (fixed forever)
❌ Existing prices (must create new ones)
❌ Past transactions (history is preserved)
```

### Updating Product Images

**Step 1:** Click product → "Product details"

**Step 2:** Click "Upload image" or drag & drop

**Best Practices:**
```
Size: 1400x1400 pixels
Format: PNG (transparent background) or JPG
Max file size: 10 MB
Aspect ratio: 1:1 (square)
```

### Adding Metadata

Metadata helps you organize and filter products.

**Example Metadata:**
```
Key: tier_level          Value: 6
Key: category           Value: premium
Key: features_count     Value: 15
Key: target_audience    Value: luxury-estates
Key: support_level      Value: 24/7-priority
```

**How to Add:**
1. Scroll to "Metadata" section
2. Click "Add metadata"
3. Enter key and value
4. Click "Save product"

---

## 💵 Managing Prices

### Important: Prices Are Immutable

Once a price is created, you **CANNOT** edit it.

**Why?** Existing subscribers are on that price, and Stripe keeps it for their billing.

### To "Change" a Price:

#### Step 1: Create New Price

1. Go to product
2. Click "Add another price"
3. Enter new amount
4. Save

#### Step 2: Copy New Price ID

```
New price created: price_1ST3new789xyz123
```

#### Step 3: Update Your Database

```bash
php artisan tinker
```

```php
$tier = \App\Models\Tier::find(6);
$tier->stripe_price_id_monthly = 'price_1ST3new789xyz123';
$tier->monthly_price = 2499.00;  // Update amount too
$tier->save();
```

#### Step 4: Archive Old Price

1. Go to old price
2. Click "⋮" (three dots)
3. Select "Archive"
4. Confirm

**Result:** New subscriptions use new price, existing ones keep old price.

### Setting Default Price

If you have multiple active prices:

1. Click on the price you want as default
2. Click "⋮" (three dots)
3. Select "Set as default"

New customers will see this price first.

---

## 📊 Product Organization

### Using Product Categories

While Stripe doesn't have built-in categories, use metadata:

```
Metadata:
category: tier
subcategory: premium
tier_level: 6
```

Then filter in your code:
```php
// In Stripe Dashboard, filter by metadata
// In your Laravel app:
Tier::where('metadata->category', 'premium')->get();
```

### Product Codes

Assign unique codes for easy reference:

```
Product code: EMURIA-T6
or
Product code: EPC-PREMIUM-PLUS
```

### Statement Descriptors

**What it is:** Text that appears on customer's credit card statement

**Examples:**
```
Good:
✅ EMURIA TIER6
✅ EMURIACARE T6
✅ PROPERTY CARE T6

Bad:
❌ TIER 6 PREMIUM PLUS CARE SUBSCRIPTION  (too long)
❌ T6-*&@#  (special characters)
```

**Rules:**
- Max 22 characters
- Letters, numbers, spaces only
- Avoid special symbols

---

## ✅ Best Practices

### 1. Naming Convention

**Consistent naming helps organization:**

```
Product names:
✅ Tier 1 - Basic Care
✅ Tier 2 - Standard Care
✅ Tier 3 - Premium Care

Price descriptions:
✅ Monthly subscription (billed monthly)
✅ Annual subscription - Save 8%
```

### 2. Always Add Both Prices

For every tier, create:
- Monthly price
- Annual price (with discount)

**Why?** Gives customers flexibility and increases conversions.

### 3. Use Metadata Extensively

```
For Products:
tier_level: 6
category: subscription
type: recurring
target: premium

For Prices:
cadence: monthly
discount_percentage: 0
billing_frequency: 1_month
```

### 4. Product Images Matter

**Use consistent branding:**
- Same style for all tiers
- Clear, professional images
- Represent tier level visually

### 5. Test Mode First

**Always:**
1. Create in test mode first
2. Test the subscription flow
3. Verify everything works
4. Then create in live mode

### 6. Document Everything

Keep a spreadsheet:

```
Tier | Product ID | Monthly Price ID | Annual Price ID | Updated
-----|------------|------------------|-----------------|----------
T1   | prod_xxx   | price_xxx        | price_yyy       | 2025-11-13
T2   | prod_yyy   | price_xxx        | price_yyy       | 2025-11-13
```

---

## 🔍 Finding Product Information

### Quick Reference Links

**All Products:**
```
https://dashboard.stripe.com/test/products
```

**Specific Product:**
```
https://dashboard.stripe.com/test/products/prod_TPtWaE4PXglU6M
                                            └─> Product ID
```

**All Subscriptions:**
```
https://dashboard.stripe.com/test/subscriptions
```

**Recent Payments:**
```
https://dashboard.stripe.com/test/payments
```

### Search Functionality

**Search for products:**
1. Click search bar (top of dashboard)
2. Type: Product name, customer email, or ID
3. Select from results

---

## 🛠️ Troubleshooting

### Issue: Can't Find "Add Product" Button

**Solution:**
1. Make sure you're logged in
2. Check you have correct permissions (Owner or Admin)
3. Verify you're in Products section
4. Refresh the page

### Issue: Price ID Not Working in Code

**Possible causes:**
1. **Copied wrong ID** - Verify it starts with `price_`
2. **Test/Live mismatch** - Test price with test key, live price with live key
3. **Archived price** - Can't use archived prices

**Solution:**
```bash
# Check database has correct ID
php artisan tinker
\App\Models\Tier::find(6)->stripe_price_id_monthly;

# Verify in Stripe Dashboard matches
```

### Issue: Product Created but Not Showing

**Solution:**
1. Hard refresh browser (Ctrl + Shift + R)
2. Check you're in correct mode (Test vs Live)
3. Verify product wasn't archived
4. Check search filters aren't active

### Issue: Can't Create Recurring Price

**Solution:**
1. Ensure "Recurring" is checked
2. Select interval (Month, Year, Week, Day)
3. If using custom, enter number (e.g., "3" for 3 months)
4. Currency must be supported for subscriptions

### Issue: New Tier Not Showing on Website

**Possible causes:**
1. Not added to database
2. `is_active = false`
3. Cache not cleared

**Solution:**
```bash
# Verify in database
php artisan tinker
\App\Models\Tier::where('slug', 'premium-plus-care')->first();

# Clear cache
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

---

## 📋 Checklist: Adding New Product

Use this checklist every time you add a new tier:

### In Stripe Dashboard:

- [ ] Login to Stripe
- [ ] Switch to Test Mode
- [ ] Click "Add product"
- [ ] Enter product name
- [ ] Add description
- [ ] Upload image
- [ ] Set statement descriptor
- [ ] Add monthly price
- [ ] Add annual price
- [ ] Copy monthly Price ID
- [ ] Copy annual Price ID

### In Your Database:

- [ ] Run `php artisan tinker` or create seeder
- [ ] Create new tier record
- [ ] Paste monthly Price ID
- [ ] Paste annual Price ID
- [ ] Set sort_order correctly
- [ ] Set is_active = true
- [ ] Verify tier was created

### Testing:

- [ ] Visit `/tiers` page
- [ ] New tier displays correctly
- [ ] Click "Select" button works
- [ ] Registration form shows correct price
- [ ] Can complete test subscription
- [ ] Success page displays
- [ ] Subscription appears in Stripe Dashboard

---

## 🎓 Advanced Tips

### Creating Promo Prices

For limited-time offers:

1. Create new price with discount
2. Add metadata: `promo: black-friday`
3. Use in code temporarily
4. Archive after promo ends

### Multi-Currency Support

For international customers:

1. Create same product
2. Add prices in different currencies:
   - USD: $1,999
   - EUR: €1,799
   - GBP: £1,599
   - UGX: 7,500,000

### Usage-Based Pricing

For pay-per-use features:

```
Pricing model: Package pricing
or
Pricing model: Graduated pricing
```

Example: $199/month + $10 per inspection beyond limit

---

## 📞 Getting Help

### Stripe Support

**Dashboard Help:**
- Click "?" icon (bottom right of dashboard)
- Live chat available 24/7

**Email:**
- support@stripe.com
- Response within 24 hours

**Documentation:**
- https://stripe.com/docs/products-prices/overview
- https://stripe.com/docs/billing

### Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| "Product limit reached" | Contact Stripe to increase limit |
| "Invalid price" | Check currency is supported |
| "Archive failed" | Check if price is in use by active subscriptions |
| "Can't delete product" | Archive instead, can't delete if used |

---

## ✅ Summary

### What You Learned:

1. ✅ **Access Stripe Dashboard** and switch to test mode
2. ✅ **Create products** with name, description, and images
3. ✅ **Add prices** for monthly and annual billing
4. ✅ **Copy Price IDs** and add to your database
5. ✅ **Edit products** and manage prices
6. ✅ **Best practices** for organization and naming

### Key Takeaways:

- **Products** = What you sell (Tier 1, Tier 2, etc.)
- **Prices** = How much it costs ($199/month)
- **Price IDs** = Connect Stripe to your database
- **Test mode** = Safe practice environment
- **Prices can't be edited** = Create new ones instead

### Quick Workflow:

```
1. Create product in Stripe Dashboard
   ↓
2. Add monthly price ($1,999)
   ↓
3. Add annual price ($21,989)
   ↓
4. Copy both Price IDs
   ↓
5. Add to Laravel database (Tinker or Seeder)
   ↓
6. Test on /tiers page
   ↓
7. Complete test subscription
   ↓
8. Done! 🎉
```

---

**Need to add a new tier?** Follow this guide step by step!

**Questions?** Check the troubleshooting section or contact Stripe support.

**Ready for production?** Remember to create products in **Live Mode** too!
