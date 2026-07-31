<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BuildingSubsystem;
use App\Models\BuildingSystem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class SubsystemController extends Controller
{
    public function index()
    {
        $systems = BuildingSystem::query()->orderBy('sort_order')->orderBy('name')->get();

        $query = BuildingSubsystem::query()
            ->with('system')
            ->withCount('components');

        $systemId = request()->integer('building_system_id');
        if ($systemId > 0) {
            $query->where('building_system_id', $systemId);
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
        $systems = BuildingSystem::query()->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.pricing-system.subsystems.create', compact('systems'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'building_system_id' => 'required|exists:building_systems,id',
            'code' => 'nullable|string|max:30|unique:building_subsystems,code',
            'name' => 'required|string|max:150',
            'slug' => [
                'nullable',
                'string',
                'max:160',
                Rule::unique('building_subsystems', 'slug')
                    ->where(fn ($query) => $query->where('building_system_id', $request->input('building_system_id'))),
            ],
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['code'] = strtoupper($validated['code'] ?? ('USR-SUB-' . Str::upper(Str::random(6))));
        $validated['slug'] = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', true);

        if (BuildingSubsystem::query()
            ->where('building_system_id', $validated['building_system_id'])
            ->where('slug', $validated['slug'])
            ->exists()) {
            throw ValidationException::withMessages([
                'slug' => 'This building subsystem slug is already in use for the selected system.',
            ]);
        }

        BuildingSubsystem::create($validated);

        return redirect()->route('admin.subsystems.index')
            ->with('success', 'Subsystem created successfully.');
    }

    public function edit(BuildingSubsystem $subsystem)
    {
        $systems = BuildingSystem::query()->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.pricing-system.subsystems.edit', compact('subsystem', 'systems'));
    }

    public function update(Request $request, BuildingSubsystem $subsystem)
    {
        $validated = $request->validate([
            'building_system_id' => 'required|exists:building_systems,id',
            'code' => 'nullable|string|max:30|unique:building_subsystems,code,' . $subsystem->id,
            'name' => 'required|string|max:150',
            'slug' => [
                'nullable',
                'string',
                'max:160',
                Rule::unique('building_subsystems', 'slug')
                    ->where(fn ($query) => $query->where('building_system_id', $request->input('building_system_id')))
                    ->ignore($subsystem->id),
            ],
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['code'] = strtoupper($validated['code'] ?? $subsystem->code);
        $validated['slug'] = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');

        if (BuildingSubsystem::query()
            ->where('building_system_id', $validated['building_system_id'])
            ->where('slug', $validated['slug'])
            ->whereKeyNot($subsystem->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'slug' => 'This building subsystem slug is already in use for the selected system.',
            ]);
        }

        $subsystem->update($validated);

        return redirect()->route('admin.subsystems.index')
            ->with('success', 'Subsystem updated successfully.');
    }

    public function destroy(BuildingSubsystem $subsystem)
    {
        $subsystem->delete();

        return redirect()->route('admin.subsystems.index')
            ->with('success', 'Subsystem deleted successfully.');
    }
}
