<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BDCSetting;
use App\Services\BDCCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BDCSettingsController extends Controller
{
    protected $bdcCalculator;

    public function __construct(BDCCalculator $bdcCalculator)
    {
        $this->bdcCalculator = $bdcCalculator;
    }

    /**
     * Display BDC settings page
     */
    public function index()
    {
        $settings = BDCSetting::getAllWithDetails();
        $calculation = $this->bdcCalculator->calculate();
        
        return view('admin.settings.bdc-settings', [
            'settings' => $settings,
            'calculation' => $calculation,
        ]);
    }

    /**
     * Update BDC settings
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.id' => 'required|exists:bdc_settings,id',
            'settings.*.setting_value' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            foreach ($request->settings as $settingData) {
                $setting = BDCSetting::find($settingData['id']);
                if ($setting) {
                    $setting->setting_value = $settingData['setting_value'];
                    $setting->save();
                }
            }

            return redirect()
                ->route('admin.settings.bdc')
                ->with('success', 'BDC settings updated successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to update settings: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Calculate preview with custom values (AJAX)
     */
    public function preview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loaded_hourly_rate' => 'required|numeric|min:0',
            'visits_per_year' => 'required|numeric|min:0',
            'hours_per_visit' => 'required|numeric|min:0',
            'infrastructure_percentage' => 'required|numeric|min:0|max:1',
            'administration_percentage' => 'required|numeric|min:0|max:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $calculation = $this->bdcCalculator->calculateWithParams($request->all());
            
            return response()->json([
                'success' => true,
                'calculation' => $calculation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset to default values
     */
    public function reset()
    {
        try {
            // Reset to default values from seeder
            $defaults = [
                'loaded_hourly_rate' => 165.00,
                'visits_per_year' => 8.00,
                'hours_per_visit' => 4.50,
                'infrastructure_percentage' => 0.30,
                'administration_percentage' => 0.12,
            ];

            foreach ($defaults as $key => $value) {
                BDCSetting::updateValue($key, $value);
            }

            return redirect()
                ->route('admin.settings.bdc')
                ->with('success', 'BDC settings reset to default values!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to reset settings: ' . $e->getMessage());
        }
    }
}
