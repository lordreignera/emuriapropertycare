<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // Allow access if user is not authenticated (shouldn't happen with auth middleware)
        if (!$user) {
            return redirect()->route('login');
        }
        
        // NEW BUSINESS MODEL: FREE ACCESS FOR ALL AUTHENTICATED USERS
        // Subscription is only required AFTER inspection and custom product offer
        // This means:
        // - Clients can register for FREE
        // - Clients can add properties
        // - Clients can view their dashboard
        // - Staff can access the system
        // - Subscription check happens at payment stage, not at entry
        
        return $next($request);
    }
}
