<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InspectionSubsystem;
use App\Models\InspectionSystem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubsystemController extends Controller
{
    public function index()
    {
        $systems = InspectionSystem::query()->orderBy('sort_order')->orderBy('name')->get();

        $query = InspectionSubsystem::query()
            ->with('system');

        $systemId = request()->integer('system_id');
        if ($systemId > 0) {
            $query->where('system_id', $systemId);
        }

        $subsystems = $query
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('admin.pricing-system.subsystems.index', compact('subsystems', 'systems', 'systemId'));
    }

    public function create()
    {
        $systems = InspectionSystem::query()->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.pricing-system.subsystems.create', compact('systems'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'system_id' => 'required|exists:systems,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:subsystems,slug',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['slug'] = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name'] . '-' . $validated['system_id']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', true);

        InspectionSubsystem::create($validated);

        return redirect()->route('admin.subsystems.index')
            ->with('success', 'Subsystem created successfully.');
    }

    public function edit(InspectionSubsystem $subsystem)
    {
        $systems = InspectionSystem::query()->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.pricing-system.subsystems.edit', compact('subsystem', 'systems'));
    }

    public function update(Request $request, InspectionSubsystem $subsystem)
    {
        $validated = $request->validate([
            'system_id' => 'required|exists:systems,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:subsystems,slug,' . $subsystem->id,
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['slug'] = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name'] . '-' . $validated['system_id']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');

        $subsystem->update($validated);

        return redirect()->route('admin.subsystems.index')
            ->with('success', 'Subsystem updated successfully.');
    }

    public function destroy(InspectionSubsystem $subsystem)
    {
        $subsystem->delete();

        return redirect()->route('admin.subsystems.index')
            ->with('success', 'Subsystem deleted successfully.');
    }
}
