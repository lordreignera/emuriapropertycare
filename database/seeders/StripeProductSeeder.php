<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tier;
use Stripe\Stripe;
use Stripe\Product;
use Stripe\Price;

class StripeProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This will create Stripe products and prices for all tiers
     */
    public function run(): void
    {
        // Set Stripe API key
        Stripe::setApiKey(config('cashier.secret'));

        $this->command->info('🚀 Creating Stripe products and prices...');

        // Get all tiers from database
        $tiers = Tier::orderBy('sort_order')->get();

        foreach ($tiers as $tier) {
            $this->command->info("Creating product for: {$tier->name}");

            try {
                // Create Stripe Product
                $product = Product::create([
                    'name' => $tier->name,
                    'description' => $tier->description,
                    'metadata' => [
                        'tier_id' => $tier->id,
                        'slug' => $tier->slug,
                        'experience' => $tier->experience,
                    ],
                ]);

                $this->command->info("✅ Product created: {$product->id}");

                // Create Monthly Price
                $monthlyPrice = Price::create([
                    'product' => $product->id,
                    'unit_amount' => $tier->monthly_price * 100, // Convert to cents
                    'currency' => 'usd',
                    'recurring' => [
                        'interval' => 'month',
                    ],
                    'metadata' => [
                        'tier_id' => $tier->id,
                        'cadence' => 'monthly',
                    ],
                ]);

                $this->command->info("✅ Monthly price created: {$monthlyPrice->id} (\${$tier->monthly_price}/month)");

                // Create Annual Price
                $annualPrice = Price::create([
                    'product' => $product->id,
                    'unit_amount' => $tier->annual_price * 100, // Convert to cents
                    'currency' => 'usd',
                    'recurring' => [
                        'interval' => 'year',
                    ],
                    'metadata' => [
                        'tier_id' => $tier->id,
                        'cadence' => 'annual',
                    ],
                ]);

                $this->command->info("✅ Annual price created: {$annualPrice->id} (\${$tier->annual_price}/year)");

                // Update tier with Stripe Price IDs
                $tier->update([
                    'stripe_price_id_monthly' => $monthlyPrice->id,
                    'stripe_price_id_annual' => $annualPrice->id,
                ]);

                $this->command->info("✅ Tier updated with Stripe price IDs\n");

            } catch (\Exception $e) {
                $this->command->error("❌ Error creating product for {$tier->name}: {$e->getMessage()}\n");
            }
        }

        $this->command->info('🎉 All Stripe products and prices created successfully!');
        $this->command->info('You can view them at: https://dashboard.stripe.com/test/products');
    }
}
