<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BuildingComponent;
use App\Models\BuildingSubsystem;
use App\Models\BuildingSystem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BuildingComponentController extends Controller
{
    public function index(Request $request)
    {
        $systems = BuildingSystem::query()->orderBy('sort_order')->orderBy('name')->get();
        $systemId = $request->integer('building_system_id') ?: null;
        $subsystemId = $request->integer('building_subsystem_id') ?: null;

        $subsystems = BuildingSubsystem::query()
            ->when($systemId, fn ($query) => $query->where('building_system_id', $systemId))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $components = BuildingComponent::query()
            ->with(['subsystem.system'])
            ->when($subsystemId, fn ($query) => $query->where('building_subsystem_id', $subsystemId))
            ->when($systemId && !$subsystemId, function ($query) use ($systemId) {
                $query->whereHas('subsystem', fn ($subquery) => $subquery->where('building_system_id', $systemId));
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('admin.pricing-system.components.index', compact(
            'components',
            'systems',
            'subsystems',
            'systemId',
            'subsystemId'
        ));
    }

    public function create(Request $request)
    {
        $systems = BuildingSystem::query()->orderBy('sort_order')->orderBy('name')->get();
        $selectedSystemId = $request->integer('building_system_id') ?: null;
        $selectedSubsystemId = $request->integer('building_subsystem_id') ?: null;
        $subsystems = BuildingSubsystem::query()
            ->when($selectedSystemId, fn ($query) => $query->where('building_system_id', $selectedSystemId))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $allSubsystems = BuildingSubsystem::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'building_system_id', 'name'])
            ->map(fn ($subsystem) => [
                'id' => $subsystem->id,
                'building_system_id' => $subsystem->building_system_id,
                'name' => $subsystem->name,
            ])
            ->values()
            ->all();

        return view('admin.pricing-system.components.create', compact(
            'systems',
            'subsystems',
            'allSubsystems',
            'selectedSystemId',
            'selectedSubsystemId'
        ));
    }

    public function store(Request $request)
    {
        $validated = $this->validateComponent($request);
        $payload = $this->normalizePayload($validated, $request);
        $this->ensureUniqueSlug($payload);
        BuildingComponent::create($payload);

        return redirect()->route('admin.components.index', [
            'building_subsystem_id' => $validated['building_subsystem_id'],
        ])->with('success', 'Building component created successfully.');
    }

    public function edit(BuildingComponent $component)
    {
        $component->load('subsystem.system');
        $systems = BuildingSystem::query()->orderBy('sort_order')->orderBy('name')->get();
        $selectedSystemId = $component->subsystem?->building_system_id;
        $subsystems = BuildingSubsystem::query()
            ->where('building_system_id', $selectedSystemId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $allSubsystems = BuildingSubsystem::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'building_system_id', 'name'])
            ->map(fn ($subsystem) => [
                'id' => $subsystem->id,
                'building_system_id' => $subsystem->building_system_id,
                'name' => $subsystem->name,
            ])
            ->values()
            ->all();

        return view('admin.pricing-system.components.edit', compact(
            'component',
            'systems',
            'subsystems',
            'allSubsystems',
            'selectedSystemId'
        ));
    }

    public function update(Request $request, BuildingComponent $component)
    {
        $validated = $this->validateComponent($request, $component);
        $payload = $this->normalizePayload($validated, $request, $component);
        $this->ensureUniqueSlug($payload, $component);
        $component->update($payload);

        return redirect()->route('admin.components.index', [
            'building_subsystem_id' => $validated['building_subsystem_id'],
        ])->with('success', 'Building component updated successfully.');
    }

    public function destroy(BuildingComponent $component)
    {
        $subsystemId = $component->building_subsystem_id;
        $component->delete();

        return redirect()->route('admin.components.index', [
            'building_subsystem_id' => $subsystemId,
        ])->with('success', 'Building component deleted successfully.');
    }

    private function validateComponent(Request $request, ?BuildingComponent $component = null): array
    {
        return $request->validate([
            'building_system_id' => 'required|exists:building_systems,id',
            'building_subsystem_id' => [
                'required',
                Rule::exists('building_subsystems', 'id')
                    ->where(fn ($query) => $query->where('building_system_id', $request->input('building_system_id'))),
            ],
            'code' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('building_components', 'code')->ignore($component?->id),
            ],
            'name' => 'required|string|max:180',
            'slug' => [
                'nullable',
                'string',
                'max:190',
                Rule::unique('building_components', 'slug')
                    ->where(fn ($query) => $query->where('building_subsystem_id', $request->input('building_subsystem_id')))
                    ->ignore($component?->id),
            ],
            'description' => 'nullable|string',
            'default_trade' => 'nullable|string|max:120',
            'aliases_text' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);
    }

    private function normalizePayload(array $validated, Request $request, ?BuildingComponent $component = null): array
    {
        $slug = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        $code = strtoupper($validated['code'] ?? ($component?->code ?: 'USR-CMP-' . Str::upper(Str::random(6))));

        unset($validated['building_system_id'], $validated['aliases_text']);

        return array_merge($validated, [
            'code' => $code,
            'slug' => $slug,
            'aliases' => $this->parseAliases($request->input('aliases_text')),
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);
    }

    private function parseAliases(?string $aliases): array
    {
        return collect(preg_split('/[\r\n,]+/', (string) $aliases))
            ->map(fn ($alias) => trim($alias))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function ensureUniqueSlug(array $payload, ?BuildingComponent $component = null): void
    {
        $query = BuildingComponent::query()
            ->where('building_subsystem_id', $payload['building_subsystem_id'])
            ->where('slug', $payload['slug']);

        if ($component) {
            $query->whereKeyNot($component->id);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'slug' => 'This building component slug is already in use for the selected subsystem.',
            ]);
        }
    }
}
