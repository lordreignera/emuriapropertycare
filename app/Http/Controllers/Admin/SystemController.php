<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InspectionSystem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SystemController extends Controller
{
    public function index()
    {
        $systems = InspectionSystem::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.pricing-system.systems.index', compact('systems'));
    }

    public function create()
    {
        return view('admin.pricing-system.systems.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:systems,name',
            'slug' => 'nullable|string|max:255|unique:systems,slug',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'weight' => 'required|integer|min:0|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['slug'] = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', true);

        InspectionSystem::create($validated);

        return redirect()->route('admin.systems.index')
            ->with('success', 'System created successfully.');
    }

    public function edit(InspectionSystem $system)
    {
        return view('admin.pricing-system.systems.edit', compact('system'));
    }

    public function update(Request $request, InspectionSystem $system)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:systems,name,' . $system->id,
            'slug' => 'nullable|string|max:255|unique:systems,slug,' . $system->id,
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'weight' => 'required|integer|min:0|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['slug'] = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');

        $system->update($validated);

        return redirect()->route('admin.systems.index')
            ->with('success', 'System updated successfully.');
    }

    public function destroy(InspectionSystem $system)
    {
        $system->delete();

        return redirect()->route('admin.systems.index')
            ->with('success', 'System deleted successfully.');
    }
}
