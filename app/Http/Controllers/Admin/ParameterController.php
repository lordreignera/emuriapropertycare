<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Parameter;
use Illuminate\Http\Request;

class ParameterController extends Controller
{
    public function index()
    {
        $parameters = Parameter::query()
            ->orderBy('group_name')
            ->orderBy('parameter_key')
            ->paginate(25);

        return view('admin.pricing-system.parameters.index', compact('parameters'));
    }

    public function create()
    {
        return view('admin.pricing-system.parameters.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'parameter_key' => 'required|string|max:255|unique:parameters,parameter_key',
            'parameter_value' => 'required|numeric',
            'group_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['parameter_key'] = strtoupper(trim($validated['parameter_key']));
        $validated['group_name'] = $validated['group_name'] ?: 'base_service_pricing';
        $validated['is_active'] = $request->boolean('is_active', true);

        Parameter::create($validated);

        return redirect()->route('admin.parameters.index')
            ->with('success', 'Parameter created successfully.');
    }

    public function edit(Parameter $parameter)
    {
        return view('admin.pricing-system.parameters.edit', compact('parameter'));
    }

    public function update(Request $request, Parameter $parameter)
    {
        $validated = $request->validate([
            'parameter_key' => 'required|string|max:255|unique:parameters,parameter_key,' . $parameter->id,
            'parameter_value' => 'required|numeric',
            'group_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['parameter_key'] = strtoupper(trim($validated['parameter_key']));
        $validated['group_name'] = $validated['group_name'] ?: 'base_service_pricing';
        $validated['is_active'] = $request->boolean('is_active');

        $parameter->update($validated);

        return redirect()->route('admin.parameters.index')
            ->with('success', 'Parameter updated successfully.');
    }

    public function destroy(Parameter $parameter)
    {
        $parameter->delete();

        return redirect()->route('admin.parameters.index')
            ->with('success', 'Parameter deleted successfully.');
    }

    public function reloadDefaults()
    {
        foreach (Parameter::defaultBaseServiceParameters() as $default) {
            Parameter::updateOrCreate(
                ['parameter_key' => $default['parameter_key']],
                [
                    'parameter_value' => $default['parameter_value'],
                    'group_name' => 'base_service_pricing',
                    'description' => $default['description'],
                    'is_active' => true,
                ]
            );
        }

        return redirect()->route('admin.parameters.index')
            ->with('success', 'Parameters reloaded to default values.');
    }
}
