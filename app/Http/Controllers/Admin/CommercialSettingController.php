<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommercialSizeSetting;
use Illuminate\Http\Request;

class CommercialSettingController extends Controller
{
    public function index()
    {
        $settings = CommercialSizeSetting::orderBy('setting_name')->get();
        return view('admin.pricing-system.commercial-settings.index', compact('settings'));
    }

    public function create()
    {
        return view('admin.pricing-system.commercial-settings.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'setting_name' => 'required|string|max:100|unique:commercial_size_settings,setting_name',
            'setting_value' => 'nullable|numeric',
            'data_type' => 'required|string|max:20',
            'description' => 'nullable|string',
        ]);

        CommercialSizeSetting::create($validated);

        return redirect()->route('admin.commercial-settings.index')
            ->with('success', 'Commercial size setting created successfully.');
    }

    public function edit(CommercialSizeSetting $commercialSetting)
    {
        return view('admin.pricing-system.commercial-settings.edit', compact('commercialSetting'));
    }

    public function update(Request $request, CommercialSizeSetting $commercialSetting)
    {
        $validated = $request->validate([
            'setting_name' => 'required|string|max:100|unique:commercial_size_settings,setting_name,' . $commercialSetting->id,
            'setting_value' => 'nullable|numeric',
            'data_type' => 'required|string|max:20',
            'description' => 'nullable|string',
        ]);

        $commercialSetting->update($validated);

        return redirect()->route('admin.commercial-settings.index')
            ->with('success', 'Commercial size setting updated successfully.');
    }

    public function destroy(CommercialSizeSetting $commercialSetting)
    {
        $commercialSetting->delete();

        return redirect()->route('admin.commercial-settings.index')
            ->with('success', 'Commercial size setting deleted successfully.');
    }
}
