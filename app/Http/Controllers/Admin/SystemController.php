<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BuildingSystem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SystemController extends Controller
{
    public function index()
    {
        $systems = BuildingSystem::query()
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
            'code' => 'nullable|string|max:20|unique:building_systems,code',
            'name' => 'required|string|max:150',
            'slug' => 'nullable|string|max:160|unique:building_systems,slug',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'weight' => 'nullable|integer|min:0|max:1000',
            'is_core' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['code'] = strtoupper($validated['code'] ?? ('USR-' . Str::upper(Str::random(6))));
        $validated['slug'] = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_core'] = $request->boolean('is_core');
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['metadata'] = ['cpi_weight' => (int) ($validated['weight'] ?? 10)];
        unset($validated['weight']);

        if (BuildingSystem::query()->where('slug', $validated['slug'])->exists()) {
            throw ValidationException::withMessages([
                'slug' => 'This building system slug is already in use.',
            ]);
        }

        BuildingSystem::create($validated);

        return redirect()->route('admin.systems.index')
            ->with('success', 'System created successfully.');
    }

    public function edit(BuildingSystem $system)
    {
        return view('admin.pricing-system.systems.edit', compact('system'));
    }

    public function update(Request $request, BuildingSystem $system)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:20|unique:building_systems,code,' . $system->id,
            'name' => 'required|string|max:150',
            'slug' => 'nullable|string|max:160|unique:building_systems,slug,' . $system->id,
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'weight' => 'nullable|integer|min:0|max:1000',
            'is_core' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['code'] = strtoupper($validated['code'] ?? $system->code);
        $validated['slug'] = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_core'] = $request->boolean('is_core');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['metadata'] = array_merge($system->metadata ?? [], [
            'cpi_weight' => (int) ($validated['weight'] ?? $system->weight),
        ]);
        unset($validated['weight']);

        if (BuildingSystem::query()
            ->where('slug', $validated['slug'])
            ->whereKeyNot($system->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'slug' => 'This building system slug is already in use.',
            ]);
        }

        $system->update($validated);

        return redirect()->route('admin.systems.index')
            ->with('success', 'System updated successfully.');
    }

    public function destroy(BuildingSystem $system)
    {
        $system->delete();

        return redirect()->route('admin.systems.index')
            ->with('success', 'System deleted successfully.');
    }
}
