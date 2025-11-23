<?php

namespace App\Http\Controllers;

use App\Models\Tier;
use Illuminate\Http\Request;

class TierController extends Controller
{
    /**
     * Display tier selection page
     */
    public function index()
    {
        $tiers = Tier::orderBy('price_monthly')->get();
        return view('tiers.index', compact('tiers'));
    }

    /**
     * Show registration form for selected tier
     */
    public function register($tierId, Request $request)
    {
        $tier = Tier::findOrFail($tierId);
        $cadence = $request->query('cadence', 'monthly'); // monthly or annual
        
        return view('tiers.register', compact('tier', 'cadence'));
    }
}
