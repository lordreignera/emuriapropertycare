<?php

namespace App\Http\Controllers;

use App\Models\Tier;
use App\Models\User;
use App\Models\Client;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    /**
     * Process registration and redirect to Stripe Checkout
     */
    public function processCheckout(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|max:20',
            'tier_id' => 'required|exists:tiers,id',
            'cadence' => 'required|in:monthly,annual',
        ]);

        try {
            DB::beginTransaction();

            // Create user account
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            // Assign client role
            $user->assignRole('Client');

            // Create client profile
            $client = Client::create([
                'user_id' => $user->id,
                'company_name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'status' => 'active',
            ]);

            // Log the user in
            Auth::login($user);

            // Get the tier
            $tier = Tier::findOrFail($validated['tier_id']);
            
            // Calculate price based on cadence
            $price = $validated['cadence'] === 'monthly' 
                ? $tier->monthly_price 
                : $tier->annual_price;

            // Create Stripe Checkout Session
            // Note: You need to create products in Stripe Dashboard first
            // and add stripe_price_id_monthly and stripe_price_id_annual to tiers table
            $checkout = $user->newSubscription('default', $this->getStripePriceId($tier, $validated['cadence']))
                ->checkout([
                    'success_url' => route('checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('checkout.cancel'),
                ]);

            // Store pending subscription data in session
            session([
                'pending_subscription' => [
                    'tier_id' => $tier->id,
                    'cadence' => $validated['cadence'],
                    'price' => $price,
                ]
            ]);

            DB::commit();

            return redirect($checkout->url);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Registration failed: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Handle successful checkout
     */
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');
        
        if (!$sessionId) {
            return redirect('/')->with('error', 'Invalid checkout session');
        }

        // Retrieve pending subscription data
        $pendingSubscription = session('pending_subscription');
        
        if ($pendingSubscription) {
            $user = auth()->user();
            $client = $user->client;
            
            // Create subscription record in database
            Subscription::create([
                'user_id' => $user->id,
                'client_id' => $client ? $client->id : null,
                'tier_id' => $pendingSubscription['tier_id'],
                'payment_cadence' => $pendingSubscription['cadence'],
                'status' => 'active',
                'start_date' => now(),
                'next_billing_date' => $pendingSubscription['cadence'] === 'monthly' 
                    ? now()->addMonth() 
                    : now()->addYear(),
                'amount' => $pendingSubscription['price'],
                'auto_renew' => true,
            ]);

            // Clear session data
            session()->forget('pending_subscription');
        }

        return view('checkout.success');
    }

    /**
     * Handle cancelled checkout
     */
    public function cancel()
    {
        return view('checkout.cancel');
    }

    /**
     * Get Stripe Price ID for tier (placeholder - needs actual Stripe Price IDs)
     */
    private function getStripePriceId(Tier $tier, string $cadence): string
    {
        // TODO: Add stripe_price_id_monthly and stripe_price_id_annual columns to tiers table
        // For now, return a placeholder. You'll need to create products in Stripe Dashboard
        // and update this method to return the actual price IDs
        
        if ($cadence === 'monthly') {
            return $tier->stripe_price_id_monthly ?? 'price_monthly_placeholder';
        } else {
            return $tier->stripe_price_id_annual ?? 'price_annual_placeholder';
        }
    }
}
