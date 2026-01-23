<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingSystemConfig;
use Illuminate\Http\Request;

class PricingConfigController extends Controller
{
    public function index()
    {
        $configs = PricingSystemConfig::orderBy('config_key')->get();
        return view('admin.pricing-system.pricing-config.index', compact('configs'));
    }

    public function create()
    {
        return view('admin.pricing-system.pricing-config.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'config_key' => 'required|string|max:255|unique:pricing_system_config,config_key',
            'config_value' => 'required|string',
            'value_type' => 'required|in:string,integer,decimal,boolean',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        PricingSystemConfig::create($validated);

        return redirect()->route('admin.pricing-config.index')
            ->with('success', 'Pricing system configuration created successfully.');
    }

    public function edit(PricingSystemConfig $pricingConfig)
    {
        return view('admin.pricing-system.pricing-config.edit', compact('pricingConfig'));
    }

    public function update(Request $request, PricingSystemConfig $pricingConfig)
    {
        $validated = $request->validate([
            'config_key' => 'required|string|max:255|unique:pricing_system_config,config_key,' . $pricingConfig->id,
            'config_value' => 'required|string',
            'value_type' => 'required|in:string,integer,decimal,boolean',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $pricingConfig->update($validated);

        return redirect()->route('admin.pricing-config.index')
            ->with('success', 'Pricing system configuration updated successfully.');
    }

    public function destroy(PricingSystemConfig $pricingConfig)
    {
        $pricingConfig->delete();

        return redirect()->route('admin.pricing-config.index')
            ->with('success', 'Pricing system configuration deleted successfully.');
    }
}
