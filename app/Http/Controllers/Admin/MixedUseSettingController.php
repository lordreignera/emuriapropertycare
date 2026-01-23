<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MixedUseCalculationSetting;
use Illuminate\Http\Request;

class MixedUseSettingController extends Controller
{
    public function index()
    {
        $settings = MixedUseCalculationSetting::orderBy('setting_name')->get();
        return view('admin.pricing-system.mixed-use-settings.index', compact('settings'));
    }

    public function create()
    {
        return view('admin.pricing-system.mixed-use-settings.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'setting_name' => 'required|string|max:255',
            'setting_key' => 'required|string|max:255|unique:mixed_use_calculation_settings,setting_key',
            'setting_value' => 'required|numeric',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        MixedUseCalculationSetting::create($validated);

        return redirect()->route('admin.mixed-use-settings.index')
            ->with('success', 'Mixed-use setting created successfully.');
    }

    public function edit(MixedUseCalculationSetting $mixedUseSetting)
    {
        return view('admin.pricing-system.mixed-use-settings.edit', compact('mixedUseSetting'));
    }

    public function update(Request $request, MixedUseCalculationSetting $mixedUseSetting)
    {
        $validated = $request->validate([
            'setting_name' => 'required|string|max:255',
            'setting_key' => 'required|string|max:255|unique:mixed_use_calculation_settings,setting_key,' . $mixedUseSetting->id,
            'setting_value' => 'required|numeric',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $mixedUseSetting->update($validated);

        return redirect()->route('admin.mixed-use-settings.index')
            ->with('success', 'Mixed-use setting updated successfully.');
    }

    public function destroy(MixedUseCalculationSetting $mixedUseSetting)
    {
        $mixedUseSetting->delete();

        return redirect()->route('admin.mixed-use-settings.index')
            ->with('success', 'Mixed-use setting deleted successfully.');
    }
}
