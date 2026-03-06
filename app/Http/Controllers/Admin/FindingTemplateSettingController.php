<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FindingTemplateSetting;
use Illuminate\Http\Request;

class FindingTemplateSettingController extends Controller
{
    public function index()
    {
        $findings = FindingTemplateSetting::query()->orderBy('sort_order')->orderBy('task_question')->get();
        return view('admin.pricing-system.finding-template-settings.index', compact('findings'));
    }

    public function create()
    {
        return view('admin.pricing-system.finding-template-settings.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'task_question' => 'required|string|max:255',
            'category' => 'nullable|string|max:120',
            'default_priority' => 'required|integer|in:1,2,3',
            'default_included' => 'nullable|boolean',
            'default_labour_hours' => 'required|numeric|min:0',
            'photo_reference' => 'nullable|string|max:50',
            'default_notes' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['default_included'] = $request->boolean('default_included', true);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        FindingTemplateSetting::create($validated);

        return redirect()->route('admin.finding-template-settings.index')
            ->with('success', 'Finding template setting created successfully.');
    }

    public function edit(FindingTemplateSetting $findingTemplateSetting)
    {
        return view('admin.pricing-system.finding-template-settings.edit', compact('findingTemplateSetting'));
    }

    public function update(Request $request, FindingTemplateSetting $findingTemplateSetting)
    {
        $validated = $request->validate([
            'task_question' => 'required|string|max:255',
            'category' => 'nullable|string|max:120',
            'default_priority' => 'required|integer|in:1,2,3',
            'default_included' => 'nullable|boolean',
            'default_labour_hours' => 'required|numeric|min:0',
            'photo_reference' => 'nullable|string|max:50',
            'default_notes' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['default_included'] = $request->boolean('default_included');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $findingTemplateSetting->update($validated);

        return redirect()->route('admin.finding-template-settings.index')
            ->with('success', 'Finding template setting updated successfully.');
    }

    public function destroy(FindingTemplateSetting $findingTemplateSetting)
    {
        $findingTemplateSetting->delete();

        return redirect()->route('admin.finding-template-settings.index')
            ->with('success', 'Finding template setting deleted successfully.');
    }

    public function reloadDefaults()
    {
        foreach (FindingTemplateSetting::defaults() as $row) {
            FindingTemplateSetting::updateOrCreate(
                ['task_question' => $row['task_question']],
                [
                    'category' => $row['category'],
                    'default_priority' => $row['default_priority'],
                    'default_included' => $row['default_included'],
                    'default_labour_hours' => $row['default_labour_hours'],
                    'photo_reference' => $row['photo_reference'],
                    'default_notes' => $row['default_notes'],
                    'sort_order' => $row['sort_order'],
                    'is_active' => true,
                ]
            );
        }

        return redirect()->route('admin.finding-template-settings.index')
            ->with('success', 'Finding template settings reloaded to defaults.');
    }
}
